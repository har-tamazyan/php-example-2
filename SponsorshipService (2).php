<?php


namespace App\Service;


use App\Models\Dish;
use App\Models\Event;
use App\Models\Sponsorship;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;

class SponsorshipService
{
    private Sponsorship $model;
    private ReturnService $returnService;
    private $transaction;

    const TRANSACTION_OJC = 'ojc';

    /**
     * SponsorshipService constructor.
     * @param Sponsorship $model
     * @param ReturnService $returnService
     */
    public function __construct(Sponsorship $model, ReturnService $returnService)
    {
        $this->model = $model;
        $this->returnService = $returnService;
    }

    /**
     * @param $allData
     * @return \Illuminate\Http\JsonResponse
     */
    public function create($allData): \Illuminate\Http\JsonResponse
    {
        $sponsorships = [];
        $totalAmount = 0;
        $returnArr = [];
        foreach ($allData['data'] as $data){
            if ($this->isPast($data['date'])) {
                return $this->returnService->errorResponse(['date' => ['The Date is past!']]);
            }

            $checkIfDateIsNotReservedEvent = Event::where('date', $data['date'])->get()->toArray();
            if (!empty($checkIfDateIsNotReservedEvent)) {
                return $this->returnService->errorResponse(['date' => ['In this date already have event']]);
            }

            $amount = 200.00;
            if (isset($data['dish_id'])) {
                if (!$res = Dish::find($data['dish_id'])) {
                    return $this->returnService->errorResponse(['dish' => ['There is no dish with mentioned ID']]);
                }
                $amount += $res->price;
            }
            $data['amount'] = $amount;
            $totalAmount += $amount;

            $sponsorships[] = $data;
        }

        if ($totalAmount !== 0) {
            switch ($allData['transaction']['name']){
                case self::TRANSACTION_OJC:
                    $this->transaction = new OJCService(new Transaction, new ReturnService);
                    break;
            }

            unset($allData['transaction']['name']);

            $transactionArr = array_merge([
                'amount' => $totalAmount,
            ], $allData['transaction']);

            try {
                $ifDoneTransaction = $this->transaction->create($transactionArr);

                if (isset($ifDoneTransaction) && !is_null($ifDoneTransaction->getStatusCode()) && $ifDoneTransaction->getStatusCode() === 200) {
                    $lastTransaction = json_decode($ifDoneTransaction->getContent());
                    $lastTransactionId = $lastTransaction->id;

                    foreach ($sponsorships as $sponsorship) {
                        $this->model->user_id = Auth::id();
                        $this->model->dish_id = $sponsorship['dish_id'] ?? null;
                        $this->model->title = $sponsorship['title'];
                        $this->model->amount = $sponsorship['amount'];
                        $this->model->currency = $sponsorship['currency'] ?? "USD";
                        $this->model->date = $sponsorship['date'];
                        $this->model->attribution = $sponsorship['attribution'];
                        $this->model->relationship = $sponsorship['relationship'];
                        if (isset($sponsorship['note']) && !empty(trim($sponsorship['note'])))
                            $this->model->note = trim($sponsorship['note']);
                        if (isset($sponsorship['upgradable']))
                            $this->model->upgradable = $sponsorship['upgradable'];
                        if (isset($sponsorship['anonymous']))
                            $this->model->anonymous = $sponsorship['anonymous'];
                        $this->model->transaction_id = $lastTransactionId;
                        $this->model->status = $lastTransaction->status;

                        if (!$this->model->save()) {
                            return $this->returnService->errorResponse(['message' => 'Failed to save sponsorship']);
                        }

                        $returnArr[] = $this->model->refresh()->toArray();
                        $this->resetModel();
                    }
                } else {
                    return $ifDoneTransaction;
                }
            } catch (\Exception $e) {
                return $this->returnService->errorResponse(["transaction" => [$e->getMessage()]], $e->getCode());
            }
        }

        return $this->returnService->successResponse($returnArr);
    }

    /**
     * @param Sponsorship $sponsorship
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel(Sponsorship $sponsorship): \Illuminate\Http\JsonResponse
    {

        if ($sponsorship->delete()) {
            return $this->returnService->successResponse();
        } else {
            return $this->returnService->errorResponse(['message' => 'Failed to cancel sponsorship']);
        }
    }

    /**
     * @param Sponsorship $sponsorship
     * @param array $data
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Sponsorship $sponsorship, array $data): \Illuminate\Http\JsonResponse
    {
        if (isset($data['status']) && (isset($sponsorship->status) && $sponsorship->status === "pending")) {
            return $this->returnService->errorResponse(['status' => ["The sponsorship is still in pending status you can not change it to refunded"]]);
        }

        if (isset($data['date']) && $this->isPast($data['date'])) {
            return $this->returnService->errorResponse(['date' => ['The Date is past!']]);
        }

        if (isset($data['date']) && isset($sponsorship->date) && $data['date'] !== $sponsorship->date){
            $checkIfDateIsNotReservedSponsorship = $this->model->where('date', $data['date'])->get()->toArray();
            if (!empty($checkIfDateIsNotReservedSponsorship)) {
                return $this->returnService->errorResponse(['date' => ['In this date already have sponsorship']]);
            }

            $checkIfDateIsNotReservedEvent = Event::where('date', $data['date'])->get()->toArray();
            if (!empty($checkIfDateIsNotReservedEvent)) {
                return $this->returnService->errorResponse(['date' => ['In this date already have event']]);
            }
        }

        if (isset($data['dish_id'])) {
            if (!$res = Dish::find($data['dish_id'])) {
                return $this->returnService->errorResponse(['dish' => ['There is no dish with mentioned ID']]);
            }
            $data['amount'] = $sponsorship->amount + $res->price;
        }

        if ($sponsorship->update($data)) {
            return $this->returnService->successResponse($sponsorship->refresh()->toArray());
        } else {
            return $this->returnService->errorResponse(['message' => 'Something went wrong during user info update']);
        }
    }

    /**
     * @return array
     */
    public function getUpcoming(): array
    {
        $returnData = [];
        $res = $this->model->where('user_id', Auth::id())->where('date' , '>=', Carbon::now())->get();
        if (!empty($res->toArray())) {
            $returnData['data'] = $res->toArray();
            $sum = $this->model->selectRaw('SUM(amount) as totalUpcomingAmount')->where('user_id', Auth::id())->where('date' , '>=', Carbon::now())->first();
            $returnData['totalUpcomingAmount'] = $sum->totalUpcomingAmount;
        }

        return $returnData;
    }

    /**
     * @return array
     */
    public function getCompleted(): array
    {
        $returnData = [];
        $res = $this->model->where('user_id', Auth::id())->where('date' , '<', Carbon::now())->get();
        if (!empty($res->toArray())) {
            $returnData['data'] = $res->toArray();
            $sum = $this->model->selectRaw('SUM(amount) as totalCompletedAmount')->where('user_id', Auth::id())->where('date' , '<', Carbon::now())->first();
            $returnData['totalCompletedAmount'] = $sum->totalCompletedAmount;
        }

        return $returnData;
    }

    /**
     * @param Sponsorship|null $sponsorship
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByIdOrAll(Sponsorship $sponsorship = null): \Illuminate\Http\JsonResponse
    {

        if (!$sponsorship) {
            $res = $this->model->all()->load(['dish', 'transaction']);
            $data = $res->toArray();
            return $this->returnService->successResponse($data);
        } else {
            return $this->returnService->successResponse($sponsorship->load(['dish', 'transaction'])->toArray());
        }
    }

    /**
     * @param string $date
     * @return bool
     */
    private function isPast(string $date): bool
    {
        return Carbon::createFromFormat('Y-m-d', $date)->isPast();
    }

    /**
     * Resetting model object
     */
    public function resetModel() {
        $this->model = new Sponsorship();
    }
}

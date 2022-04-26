<?php

namespace App\Http\Controllers;

use App\Http\Requests\SponsorshipCreateRequest;
use App\Http\Requests\SponsorshipUpdateRequest;
use App\Models\Sponsorship;
use App\Service\ReturnService;
use App\Service\SponsorshipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SponsorshipController extends Controller
{
    protected SponsorshipService $sponsorshipService;
    protected ReturnService $returnService;

    /**
     * SponsorshipController constructor.
     * @param SponsorshipService $sponsorshipService
     * @param ReturnService $returnService
     */
    public function __construct(SponsorshipService $sponsorshipService, ReturnService $returnService)
    {
        $this->sponsorshipService = $sponsorshipService;
        $this->returnService = $returnService;
    }

    /**
     * @param SponsorshipCreateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createSponsorship(SponsorshipCreateRequest $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validated();

        return $this->sponsorshipService->create($validated);
    }

    /**
     * @param Sponsorship $sponsorship
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelSponsorship(Sponsorship $sponsorship): \Illuminate\Http\JsonResponse
    {
        return $this->sponsorshipService->cancel($sponsorship);
    }

    /**
     * @param Sponsorship $sponsorship
     * @param SponsorshipUpdateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSponsorship(Sponsorship $sponsorship, SponsorshipUpdateRequest $request): \Illuminate\Http\JsonResponse
    {

        $validated = $request->validated();

        return $this->sponsorshipService->update($sponsorship, $validated);

    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUpcoming(): \Illuminate\Http\JsonResponse
    {
        $res = $this->sponsorshipService->getUpcoming();
        return $this->returnService->successResponse($res);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCompleted(): \Illuminate\Http\JsonResponse
    {
        $res = $this->sponsorshipService->getCompleted();
        return $this->returnService->successResponse($res);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAll(): \Illuminate\Http\JsonResponse
    {
        return $this->sponsorshipService->getByIdOrAll();
    }

    /**
     * @param Sponsorship $sponsorship
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSponsorshipById(Sponsorship $sponsorship): \Illuminate\Http\JsonResponse
    {
        return $this->sponsorshipService->getByIdOrAll($sponsorship);
    }
}

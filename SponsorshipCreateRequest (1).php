<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class SponsorshipCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'data' => 'required|array',
            'data.*.dish_id' => 'sometimes|required|integer',
            'data.*.title' => 'required',
            'data.*.currency' => 'in:USD,EUR',
            'data.*.date' => 'required|date_format:Y-m-d|unique:sponsorships,date',
            'data.*.attribution' => 'required',
            'data.*.relationship' => 'required',
            'data.*.note' => 'sometimes|required',
            'data.*.upgradable' => 'sometimes|required|boolean',
            'data.*.anonymous' => 'sometimes|required|boolean',
            'transaction' => 'required',
            'transaction.name' => 'required',
            'transaction.card_number' => 'required|integer',
            'transaction.card_exp' => 'required|integer',
            'transaction.card_cvv' => 'sometimes|required|integer',
            'transaction.card_bin' => 'sometimes|required|integer',
            'transaction.type' => 'sometimes|required'
        ];
    }
}

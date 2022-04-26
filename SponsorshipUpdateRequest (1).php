<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class SponsorshipUpdateRequest extends FormRequest
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
            'dish_id' => 'sometimes|required|integer',
            'title' => 'sometimes|required',
            'currency' => 'sometimes|in:USD,EUR',
            'date' => 'sometimes|required|date_format:Y-m-d',
            'attribution' => 'sometimes|required',
            'relationship' => 'sometimes|required',
            'note' => 'sometimes|required',
            'upgradable' => 'sometimes|required|boolean',
            'anonymous' => 'sometimes|required|boolean',
            'status' => 'sometimes|required|in:refunded',
        ];
    }
}

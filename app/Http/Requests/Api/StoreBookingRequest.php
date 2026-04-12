<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'court_id' => ['required', 'integer', 'exists:courts,id'],
            'slot_id' => ['required', 'integer', 'exists:slots,id'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'advance_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}

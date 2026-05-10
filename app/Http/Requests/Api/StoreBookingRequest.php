<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'slot_id' => ['nullable', 'required_without:slot_ids', 'integer', 'exists:slots,id'],
            'slot_ids' => ['nullable', 'required_without:slot_id', 'array', 'min:1', 'max:50'],
            'slot_ids.*' => ['integer', 'distinct', 'exists:slots,id'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'advance_amount' => ['nullable', 'numeric', 'min:0'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:64'],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->filled('slot_id') && $this->filled('slot_ids')) {
                $validator->errors()->add('slot_id', 'Send either slot_id or slot_ids, not both.');
            }
        });
    }
}

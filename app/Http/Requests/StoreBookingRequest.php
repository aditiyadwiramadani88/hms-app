<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'guest_id' => ['required', 'exists:guests,id'],
            'room_id' => ['required', 'exists:rooms,id'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_in_time' => ['nullable', 'string', 'max:5'],
            'check_out' => $this->check_in_time && $this->check_in_time >= '00:00' && $this->check_in_time < '06:00'
                ? ['required', 'date', 'after_or_equal:check_in']
                : ['required', 'date', 'after:check_in'],
            'adults' => ['required', 'integer', 'min:1'],
            'children' => ['nullable', 'integer', 'min:0'],
            'voucher_code' => ['nullable', 'exists:vouchers,code'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'guest_id.exists' => 'The selected guest does not exist.',
            'room_id.exists' => 'The selected room does not exist.',
            'check_in.after' => 'The check-in date must be a future date.',
            'check_out.after' => 'The check-out date must be after the check-in date.',
            'voucher_code.exists' => 'The voucher code does not exist.',
        ];
    }
}

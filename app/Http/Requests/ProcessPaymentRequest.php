<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessPaymentRequest extends FormRequest
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
            'payment_method' => ['required', 'in:cash,credit_card,bank_transfer,midtrans,xendit,charge_to_room,qris'],
            'amount' => ['required', 'numeric', 'min:0'],
            'reference_id' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'payment_method.required' => 'The payment method is required.',
            'payment_method.in' => 'The payment method must be one of: cash, credit_card, bank_transfer, midtrans, xendit, charge_to_room, qris.',
            'amount.required' => 'The payment amount is required.',
            'amount.numeric' => 'The amount must be a valid number.',
            'amount.min' => 'The amount must be greater than or equal to zero.',
        ];
    }
}

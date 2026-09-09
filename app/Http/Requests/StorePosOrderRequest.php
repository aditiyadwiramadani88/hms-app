<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePosOrderRequest extends FormRequest
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
            'booking_id' => ['nullable', 'exists:bookings,id'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'guest_id' => ['nullable', 'exists:guests,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_id' => ['required', 'exists:inventories,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'items.required' => 'At least one item is required.',
            'items.array' => 'The items must be an array.',
            'items.*.inventory_id.exists' => 'The selected inventory item does not exist.',
            'items.*.quantity.min' => 'Each item quantity must be at least 1.',
            'booking_id.exists' => 'The selected booking does not exist.',
            'room_id.exists' => 'The selected room does not exist.',
            'guest_id.exists' => 'The selected guest does not exist.',
        ];
    }
}

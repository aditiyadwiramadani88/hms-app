<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoomRequest extends FormRequest
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
            'room_number' => ['required', 'unique:rooms,room_number'],
            'room_type_id' => ['required', 'exists:room_types,id'],
            'floor' => ['required', 'integer', 'min:1'],
            'status' => ['sometimes', 'in:available,occupied,dirty,cleaning,out_of_order,maintenance'],
            'price_override' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'room_number.required' => 'The room number is required.',
            'room_number.unique' => 'This room number already exists.',
            'room_type_id.exists' => 'The selected room type does not exist.',
            'status.in' => 'The status must be one of: available, occupied, dirty, cleaning, out_of_order, maintenance.',
        ];
    }
}

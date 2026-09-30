<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignCoordinatorCareersRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'carrera_ids' => ['required', 'array', 'max:50'],
            'carrera_ids.*' => ['integer', 'min:1', 'distinct', Rule::exists('carreras', 'id')],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'carrera_ids.max' => 'No puede asignar más de 50 carreras a la vez.',
            'carrera_ids.*.min' => 'Cada carrera debe tener un identificador positivo.',
        ];
    }
}

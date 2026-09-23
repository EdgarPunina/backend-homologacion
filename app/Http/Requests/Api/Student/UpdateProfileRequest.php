<?php

namespace App\Http\Requests\Api\Student;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Estudiante') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombres_completos' => ['sometimes', 'required', 'string', 'max:255'],
            'cedula' => ['sometimes', 'required', 'string', 'between:10,20', Rule::unique('users', 'cedula')->ignore($this->user()?->getKey())],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user()?->getKey())],
            'numero_celular' => ['sometimes', 'required', 'string', 'between:7,20'],
        ];
    }
}

<?php

namespace App\Http\Requests\Api\Coordinator;

use App\CurriculumType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CurriculumIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('coordinador') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:150'],
            'tipo' => ['nullable', Rule::enum(CurriculumType::class)],
            'carrera' => ['nullable', 'integer', 'min:1', 'exists:carreras,id'],
            'estudiante' => ['nullable', 'integer', 'min:1', 'exists:users,id'],
            'activa' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'carrera.min' => 'La carrera seleccionada debe tener un identificador positivo.',
            'estudiante.min' => 'El estudiante seleccionado debe tener un identificador positivo.',
        ];
    }
}

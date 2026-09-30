<?php

namespace App\Http\Requests\Api\Coordinator;

use App\AcademicLevel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubjectRequest extends FormRequest
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
            'codigo_asignatura' => ['sometimes', 'required', 'string', 'max:50'],
            'nombre_asignatura' => ['sometimes', 'required', 'string', 'max:150'],
            'numero_creditos' => ['sometimes', 'required', 'integer:strict', 'min:0', 'max:2147483647'],
            'nivel_ciclo' => ['sometimes', 'required', Rule::enum(AcademicLevel::class)],
            'hr_carga_horaria' => ['sometimes', 'required', 'integer:strict', 'min:0', 'max:2147483647'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'numero_creditos.integer' => 'El número de créditos debe ser un entero.',
            'hr_carga_horaria.integer' => 'La carga horaria debe ser un entero.',
            'numero_creditos.max' => 'El número de créditos no puede superar 2147483647.',
            'hr_carga_horaria.max' => 'La carga horaria no puede superar 2147483647.',
        ];
    }
}

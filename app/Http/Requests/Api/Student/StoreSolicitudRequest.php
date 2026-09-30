<?php

namespace App\Http\Requests\Api\Student;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSolicitudRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('estudiante') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'coordinador_carrera_id' => ['required', 'integer', 'min:1'],
            'tramite_proceso_id' => ['required', 'integer', 'min:1', Rule::exists('tramite_proceso', 'id')],
            'procedencia_estudios' => ['required', 'string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'coordinador_carrera_id.min' => 'La asignación del coordinador debe tener un identificador positivo.',
            'tramite_proceso_id.min' => 'El trámite seleccionado debe tener un identificador positivo.',
        ];
    }
}

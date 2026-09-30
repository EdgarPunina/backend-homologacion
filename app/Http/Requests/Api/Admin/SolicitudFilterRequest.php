<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SolicitudFilterRequest extends FormRequest
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
            'estado' => ['bail', 'nullable', Rule::when(
                is_numeric($this->input('estado')),
                ['integer', 'min:1', Rule::exists('estados_solicitud', 'id')],
                ['string', 'max:100', Rule::exists('estados_solicitud', 'nombre')],
            )],
            'carrera' => ['nullable', 'integer', 'min:1', Rule::exists('carreras', 'id')],
            'tipo_tramite' => ['bail', 'nullable', Rule::when(
                is_numeric($this->input('tipo_tramite')),
                ['integer', 'min:1', Rule::exists('tipos_tramite', 'id')],
                ['string', 'max:100', Rule::exists('tipos_tramite', 'nombre')],
            )],
            'tipo_proceso' => ['bail', 'nullable', Rule::when(
                is_numeric($this->input('tipo_proceso')),
                ['integer', 'min:1', Rule::exists('tipos_proceso', 'id')],
                ['string', 'max:100', Rule::exists('tipos_proceso', 'nombre')],
            )],
            'estudiante' => ['nullable', 'string', 'max:255'],
            'coordinador' => ['nullable', 'string', 'max:255'],
            'fecha_desde' => ['nullable', 'date', 'date_format:Y-m-d'],
            'fecha_hasta' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:fecha_desde'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'estado.exists' => 'El estado seleccionado no existe.',
            'tipo_tramite.exists' => 'El tipo de trámite seleccionado no existe.',
            'tipo_proceso.exists' => 'El tipo de proceso seleccionado no existe.',
            'fecha_desde.date_format' => 'La fecha inicial debe tener el formato AAAA-MM-DD.',
            'fecha_hasta.date_format' => 'La fecha final debe tener el formato AAAA-MM-DD.',
            'carrera.min' => 'La carrera seleccionada debe tener un identificador positivo.',
        ];
    }
}

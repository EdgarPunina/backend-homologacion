<?php

namespace App\Http\Requests\Api\Coordinator;

use App\CurriculumType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCurriculumRequest extends FormRequest
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
            'nombre' => ['required', 'string', 'max:150'],
            'tipo' => ['required', Rule::enum(CurriculumType::class)],
            'carrera_id' => ['nullable', 'integer', 'min:1', 'exists:carreras,id', 'required_if:tipo,institucional', 'prohibited_if:tipo,origen'],
            'estudiante_id' => ['nullable', 'integer', 'min:1', 'exists:users,id', 'required_if:tipo,origen', 'prohibited_if:tipo,institucional'],
            'activa' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'carrera_id.prohibited_if' => 'Una malla de origen no puede incluir una carrera de destino.',
            'estudiante_id.prohibited_if' => 'Una malla institucional no puede incluir un estudiante.',
            'carrera_id.min' => 'La carrera seleccionada debe tener un identificador positivo.',
            'estudiante_id.min' => 'El estudiante seleccionado debe tener un identificador positivo.',
        ];
    }
}

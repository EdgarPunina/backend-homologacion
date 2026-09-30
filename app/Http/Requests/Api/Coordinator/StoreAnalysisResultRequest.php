<?php

namespace App\Http\Requests\Api\Coordinator;

use App\AnalysisConclusion;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnalysisResultRequest extends FormRequest
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
            'conclusion_general' => ['required', Rule::enum(AnalysisConclusion::class)],
            'total_creditos_reconocidos' => ['required', 'integer:strict', 'min:0', 'max:2147483647'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'total_creditos_reconocidos.integer' => 'El total de créditos reconocidos debe ser un número entero.',
            'total_creditos_reconocidos.min' => 'El total de créditos reconocidos no puede ser negativo.',
            'total_creditos_reconocidos.max' => 'El total de créditos reconocidos no puede superar 2147483647.',
        ];
    }
}

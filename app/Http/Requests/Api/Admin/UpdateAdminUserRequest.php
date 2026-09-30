<?php

namespace App\Http\Requests\Api\Admin;

use App\Rules\SoloLetras;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateAdminUserRequest extends FormRequest
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
        $userId = $this->route('user')?->getKey();

        return [
            'nombres_completos' => ['sometimes', 'required', 'string', 'min:3', 'max:255', new SoloLetras],
            'cedula' => ['sometimes', 'required', 'string', 'regex:/\A[0-9]{10}\z/', Rule::unique('users', 'cedula')->ignore($userId)],
            'email' => ['sometimes', 'required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'numero_celular' => ['sometimes', 'required', 'string', 'regex:/\A[0-9]{10}\z/'],
            'password' => ['sometimes', 'required', 'string', Password::defaults()],
            'rol_id' => ['sometimes', 'required', 'integer', 'min:1', Rule::exists('roles', 'id')],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nombres_completos.min' => 'Los nombres completos deben tener al menos 3 caracteres.',
            'cedula.regex' => 'La cédula debe contener exactamente 10 dígitos numéricos.',
            'numero_celular.regex' => 'El número celular debe contener exactamente 10 dígitos numéricos.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.letters' => 'La contraseña debe contener al menos una letra.',
            'password.numbers' => 'La contraseña debe contener al menos un número.',
            'rol_id.min' => 'El rol seleccionado debe tener un identificador positivo.',
        ];
    }
}

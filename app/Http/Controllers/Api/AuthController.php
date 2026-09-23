<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombres_completos' => ['required', 'string', 'max:255'],
            'cedula' => ['required', 'string', 'max:20', 'unique:users,cedula'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'numero_celular' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'password_confirmation' => ['required', 'string'],
        ]);

        $user = User::create($data);
        $user->assignRole('Estudiante');
        $token = (string) $user->createToken('frontend')->plainTextToken;

        return response()->json([
            'success' => true,
            'user' => $this->userData($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['success' => false, 'message' => 'Credenciales incorrectas.'], 401);
        }

        if (! $user->cuenta_activa) {
            return response()->json(['success' => false, 'message' => 'La cuenta se encuentra inactiva.'], 403);
        }

        $token = (string) $user->createToken('frontend')->plainTextToken;

        return response()->json([
            'success' => true,
            'user' => $this->userData($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['success' => true, 'user' => $this->userData($request->user())]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['success' => true, 'message' => 'Sesión cerrada.']);
    }

    public function roles(): JsonResponse
    {
        return response()->json(['success' => true, 'roles' => Role::query()->pluck('name')]);
    }

    /** @return array{id: int, nombres_completos: string, cedula: ?string, email: string, numero_celular: ?string, cuenta_activa: bool, roles: list<string>} */
    private function userData(User $user): array
    {
        return [
            'id' => $user->id,
            'nombres_completos' => $user->nombres_completos,
            'cedula' => $user->cedula,
            'email' => $user->email,
            'numero_celular' => $user->numero_celular,
            'cuenta_activa' => $user->cuenta_activa,
            'roles' => $user->getRoleNames()->values()->all(),
        ];
    }
}

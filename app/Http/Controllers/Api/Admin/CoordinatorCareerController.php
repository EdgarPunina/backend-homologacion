<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\AssignCoordinatorCareersRequest;
use App\Http\Resources\Api\CareerResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CoordinatorCareerController extends Controller
{
    public function index(User $coordinator): JsonResponse
    {
        if (! $coordinator->hasRole('Coordinador')) {
            return response()->json(['success' => false, 'message' => 'El usuario no es Coordinador.'], 422);
        }

        return response()->json([
            'success' => true,
            'data' => CareerResource::collection($coordinator->carrerasCoordinadas()->orderBy('nombre')->get()),
        ]);
    }

    public function update(AssignCoordinatorCareersRequest $request, User $coordinator): JsonResponse
    {
        if (! $coordinator->hasRole('Coordinador')) {
            return response()->json(['success' => false, 'message' => 'El usuario no es Coordinador.'], 422);
        }

        DB::transaction(fn () => $coordinator->carrerasCoordinadas()->sync($request->validated('carrera_ids')));

        return response()->json([
            'success' => true,
            'message' => 'Carreras asignadas correctamente.',
            'data' => CareerResource::collection($coordinator->carrerasCoordinadas()->orderBy('nombre')->get()),
        ]);
    }
}

<?php

namespace App\Services;

use App\Models\ResolucionSolicitud;
use App\Models\Solicitud;
use App\Models\User;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ResolutionService
{
    /** @param array{numero_resolucion: string, fecha_aprobacion: string, archivo: UploadedFile} $data */
    public function create(Solicitud $solicitud, User $actor, array $data, ?Closure $afterCreate = null): ResolucionSolicitud
    {
        $path = $data['archivo']->store('resoluciones', 'local');

        if (! is_string($path)) {
            throw new RuntimeException('No fue posible almacenar el archivo de resolución.');
        }

        try {
            return DB::transaction(function () use ($solicitud, $actor, $data, $path, $afterCreate): ResolucionSolicitud {
                $resolution = ResolucionSolicitud::query()->create([
                    'solicitud_id' => $solicitud->getKey(),
                    'coordinador_id' => $actor->getKey(),
                    'numero_resolucion' => $data['numero_resolucion'],
                    'fecha_aprobacion' => $data['fecha_aprobacion'],
                    'ruta_archivo' => $path,
                ]);
                $afterCreate?->__invoke($resolution);

                return $resolution;
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);

            throw $exception;
        }
    }
}

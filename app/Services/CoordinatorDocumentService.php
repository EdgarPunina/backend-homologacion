<?php

namespace App\Services;

use App\Models\EstadoDocumento;
use App\Models\SolicitudDocumento;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CoordinatorDocumentService
{
    public function __construct(
        private CoordinatorAccessService $access,
        private SolicitudWorkflowService $workflow,
    ) {}

    public function review(User $coordinator, int $id, string $state, ?string $observation): SolicitudDocumento
    {
        $document = DB::transaction(function () use ($coordinator, $id, $state, $observation): SolicitudDocumento {
            $document = $this->access->document($coordinator, $id, true);
            abort_unless($this->workflow->currentState($document->solicitud) === 'en_revision', 409, 'La solicitud no está en revisión.');
            abort_unless($document->ruta_documento_oficio !== null && Storage::disk('local')->exists($document->ruta_documento_oficio), 409, 'El documento aún no ha sido presentado o su archivo no está disponible.');

            $document->update([
                'estado_documento_id' => EstadoDocumento::query()->where('nombre', $state)->firstOrFail()->id,
                'validez' => $state === 'aprobado',
            ]);

            if ($state === 'observado') {
                $document->observaciones()->create(['observacion' => $observation]);
                $this->workflow->transition($document->solicitud, 'observado', $coordinator, 'Se observaron documentos de la solicitud.');
            }

            return $document;
        });

        if ($state === 'aprobado') {
            $this->workflow->advanceToProcessingWhenReady($document->solicitud, $coordinator);
        }

        return $document->load(['documentoRequerido', 'estadoDocumento', 'observaciones', 'verificaciones.coordinador']);
    }

    public function verify(User $coordinator, int $id, bool $approved): SolicitudDocumento
    {
        $document = DB::transaction(function () use ($coordinator, $id, $approved): SolicitudDocumento {
            $document = $this->access->document($coordinator, $id, true);
            abort_unless($this->workflow->currentState($document->solicitud) === 'en_revision', 409, 'La solicitud no está en revisión.');
            $document->verificaciones()->updateOrCreate(
                ['coordinador_id' => $coordinator->id],
                ['estado' => $approved],
            );

            return $document;
        });

        if ($approved) {
            $this->workflow->advanceToProcessingWhenReady($document->solicitud, $coordinator);
        }

        return $document->load(['documentoRequerido', 'estadoDocumento', 'observaciones', 'verificaciones.coordinador']);
    }
}

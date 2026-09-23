<?php

namespace App\Services;

use App\Models\ComparacionAsignatura;
use App\Models\Solicitud;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class TechnicalReportService
{
    public function __construct(private CoordinatorAccessService $access) {}

    public function generate(User $coordinator, Solicitud $solicitud): string
    {
        return DB::transaction(function () use ($coordinator, $solicitud): string {
            $locked = $this->access->solicitud($coordinator, $solicitud->id, true);
            $locked->load([
                'estudiante', 'carrera', 'tramiteProceso.tipoTramite', 'tramiteProceso.tipoProceso',
                'comparacionesAsignaturas.asignaturaOrigen', 'comparacionesAsignaturas.asignaturaDestino', 'resultado',
            ]);
            abort_unless($locked->resultado !== null, 409, 'La solicitud todavía no tiene resultado académico.');
            abort_unless(in_array($locked->resultado->conclusion_general, ['total', 'parcial'], true), 409, 'El resultado no permite generar un informe técnico.');

            $path = 'informes-tecnicos/solicitud-'.$locked->id.'.pdf';
            $stored = Storage::disk('local')->put($path, $this->pdf($this->lines($locked, $coordinator)));
            if (! $stored) {
                throw new RuntimeException('No fue posible almacenar el informe técnico.');
            }
            $locked->resultado->update([
                'ruta_informe_tecnico' => $path,
                'informe_generado_at' => now(),
            ]);

            return $path;
        });
    }

    /** @return list<string> */
    private function lines(Solicitud $solicitud, User $coordinator): array
    {
        $lines = [
            'INFORME TECNICO DE RECONOCIMIENTO Y HOMOLOGACION',
            'Solicitud: '.$solicitud->id,
            'Estudiante: '.$solicitud->estudiante->nombres_completos,
            'Cedula: '.$solicitud->estudiante->cedula,
            'Carrera: '.$solicitud->carrera->nombre,
            'Tramite: '.$solicitud->tramiteProceso->tipoTramite->nombre,
            'Proceso: '.$solicitud->tramiteProceso->tipoProceso->nombre,
            'Procedencia: '.$solicitud->procedencia_estudios,
            '',
            'COMPARACIONES ACADEMICAS',
        ];

        foreach ($solicitud->comparacionesAsignaturas as $comparison) {
            /** @var ComparacionAsignatura $comparison */
            $lines[] = sprintf(
                '%s - %s => %s - %s: %s%%',
                $comparison->asignaturaOrigen->codigo_asignatura,
                $comparison->asignaturaOrigen->nombre_asignatura,
                $comparison->asignaturaDestino->codigo_asignatura,
                $comparison->asignaturaDestino->nombre_asignatura,
                $comparison->porcentaje_coincidencia,
            );
            if ($comparison->observacion) {
                $lines[] = 'Observacion: '.$comparison->observacion;
            }
        }

        $lines[] = '';
        $lines[] = 'Resultado: '.$solicitud->resultado->conclusion_general;
        $lines[] = 'Creditos reconocidos: '.$solicitud->resultado->total_creditos_reconocidos;
        $lines[] = 'Coordinador responsable: '.$coordinator->nombres_completos;
        $lines[] = 'Fecha de generacion: '.now()->toDateTimeString();

        return $lines;
    }

    /** @param list<string> $lines */
    private function pdf(array $lines): string
    {
        $content = "BT\n/F1 10 Tf\n50 790 Td\n";
        foreach (array_slice($lines, 0, 46) as $index => $line) {
            $ascii = Str::ascii(Str::limit($line, 105, '...'));
            $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $ascii);
            $content .= ($index === 0 ? '' : "0 -16 Td\n").'('.$escaped.") Tj\n";
        }
        $content .= "ET\n";

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            '<< /Length '.strlen($content)." >>\nstream\n".$content.'endstream',
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $number => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($number + 1)." 0 obj\n".$object."\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf."trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
    }
}

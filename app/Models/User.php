<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['nombres_completos', 'cedula', 'email', 'password', 'numero_celular', 'cuenta_activa', 'creador_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cuenta_activa' => 'boolean',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function creador(): BelongsTo
    {
        return $this->belongsTo(self::class, 'creador_id');
    }

    /** @return HasMany<User, $this> */
    public function usuariosCreados(): HasMany
    {
        return $this->hasMany(self::class, 'creador_id');
    }

    /** @return HasMany<AntecedenteAcademico, $this> */
    public function antecedentesAcademicos(): HasMany
    {
        return $this->hasMany(AntecedenteAcademico::class, 'estudiante_id');
    }

    /** @return HasMany<CoordinadorCarrera, $this> */
    public function coordinaciones(): HasMany
    {
        return $this->hasMany(CoordinadorCarrera::class, 'coordinador_id');
    }

    /** @return BelongsToMany<Carrera, $this> */
    public function carrerasCoordinadas(): BelongsToMany
    {
        return $this->belongsToMany(Carrera::class, 'coordinador_carreras', 'coordinador_id', 'carrera_id')
            ->withTimestamps();
    }

    /** @return HasMany<EstudianteCarrera, $this> */
    public function carrerasComoEstudiante(): HasMany
    {
        return $this->hasMany(EstudianteCarrera::class, 'estudiante_id');
    }

    /** @return HasMany<Solicitud, $this> */
    public function solicitudesComoEstudiante(): HasMany
    {
        return $this->hasMany(Solicitud::class, 'estudiante_id');
    }

    /** @return HasMany<Solicitud, $this> */
    public function solicitudesComoCoordinador(): HasMany
    {
        return $this->hasMany(Solicitud::class, 'coordinador_id');
    }

    /** @return HasMany<MallaCurricular, $this> */
    public function mallasCreadas(): HasMany
    {
        return $this->hasMany(MallaCurricular::class, 'creador_id');
    }

    /** @return HasMany<MallaCurricular, $this> */
    public function mallasDeOrigen(): HasMany
    {
        return $this->hasMany(MallaCurricular::class, 'estudiante_id');
    }

    /** @return HasMany<VerificacionDocumento, $this> */
    public function verificacionesRealizadas(): HasMany
    {
        return $this->hasMany(VerificacionDocumento::class, 'coordinador_id');
    }

    /** @return HasMany<ResultadoSolicitud, $this> */
    public function resultadosEmitidos(): HasMany
    {
        return $this->hasMany(ResultadoSolicitud::class, 'coordinador_id');
    }

    /** @return HasMany<ResolucionSolicitud, $this> */
    public function resolucionesEmitidas(): HasMany
    {
        return $this->hasMany(ResolucionSolicitud::class, 'coordinador_id');
    }
}

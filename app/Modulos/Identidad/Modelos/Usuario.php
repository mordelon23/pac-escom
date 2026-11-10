<?php

namespace App\Modulos\Identidad\Modelos;

use App\Modulos\Actividades\Modelos\Actividad;
use App\Modulos\Identidad\Enums\EstadoCuenta;
use App\Modulos\Identidad\Enums\RolSistema;
use App\Modulos\Inscripciones\Enums\EstadoInscripcion;
use App\Modulos\Inscripciones\Modelos\Inscripcion;
use Illuminate\Auth\MustVerifyEmail as VerificaCorreo;
use Illuminate\Auth\Passwords\CanResetPassword as RestableceContrasena;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Usuario del sistema. Las especializaciones del diagrama de clases (Alumno, ResponsableGrupo,
 * Coordinador y Superadministrador) se implementan como roles sobre una sola tabla (RBAC).
 *
 * @property int $id
 * @property string $nombre
 * @property string $apellidos
 * @property string $correo
 * @property ?string $matricula
 * @property EstadoCuenta $estado
 */
class Usuario extends Authenticatable implements MustVerifyEmail, CanResetPassword
{
    use Notifiable, RestableceContrasena, VerificaCorreo;

    protected $table = 'usuarios';

    protected $fillable = ['rol_id', 'matricula', 'nombre', 'apellidos', 'correo', 'password', 'estado'];

    protected $hidden = ['password', 'remember_token'];

    /** Valores por defecto en memoria, iguales a los DEFAULT de la tabla. */
    protected $attributes = ['estado' => 'ACTIVA', 'intentos_fallidos' => 0];

    protected function casts(): array
    {
        return [
            'correo_verificado_en' => 'datetime',
            'password' => 'hashed',
            'estado' => EstadoCuenta::class,
            'intentos_fallidos' => 'integer',
        ];
    }

    /* ---------------- Relaciones ---------------- */

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class);
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class);
    }

    public function agenda(): BelongsToMany
    {
        return $this->belongsToMany(Actividad::class, 'agenda')->withPivot('created_at');
    }

    public function actividadesResponsable(): HasMany
    {
        return $this->hasMany(Actividad::class, 'responsable_id');
    }

    /* ---------------- Roles y permisos (RBAC) ---------------- */

    public function rolSistema(): RolSistema
    {
        return RolSistema::from($this->rol->clave);
    }

    public function tieneRol(RolSistema|string ...$roles): bool
    {
        $actual = $this->rol->clave;
        foreach ($roles as $rol) {
            if ($actual === ($rol instanceof RolSistema ? $rol->value : $rol)) {
                return true;
            }
        }

        return false;
    }

    public function esAlumno(): bool
    {
        return $this->tieneRol(RolSistema::ALUMNO);
    }

    /** El superadministrador especializa al coordinador: tiene todos sus permisos. */
    public function esCoordinador(): bool
    {
        return $this->tieneRol(RolSistema::COORDINADOR, RolSistema::SUPERADMINISTRADOR);
    }

    public function esSuperadministrador(): bool
    {
        return $this->tieneRol(RolSistema::SUPERADMINISTRADOR);
    }

    public function tienePermiso(string $clave): bool
    {
        return $this->rol->permisos->contains('clave', $clave);
    }

    /* ---------------- Reglas del dominio ---------------- */

    public function estaActiva(): bool
    {
        return $this->estado === EstadoCuenta::ACTIVA;
    }

    public function inscripcionesActivas(): HasMany
    {
        return $this->inscripciones()->where('estado', EstadoInscripcion::ACTIVA);
    }

    public function nombreCompleto(): string
    {
        return "{$this->nombre} {$this->apellidos}";
    }

    public function iniciales(): string
    {
        return mb_strtoupper(mb_substr($this->nombre, 0, 1) . mb_substr($this->apellidos, 0, 1));
    }

    /* ---------------- Adaptación al esquema en español ---------------- */

    public function hasVerifiedEmail(): bool
    {
        return ! is_null($this->correo_verificado_en);
    }

    public function markEmailAsVerified(): bool
    {
        return $this->forceFill(['correo_verificado_en' => $this->freshTimestamp()])->save();
    }

    public function getEmailForVerification(): string
    {
        return $this->correo;
    }

    public function getEmailForPasswordReset(): string
    {
        return $this->correo;
    }

    public function routeNotificationForMail(): string
    {
        return $this->correo;
    }
}

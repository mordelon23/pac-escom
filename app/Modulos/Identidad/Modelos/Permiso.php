<?php

namespace App\Modulos\Identidad\Modelos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permiso extends Model
{
    protected $table = 'permisos';

    protected $fillable = ['clave', 'descripcion'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'rol_permiso');
    }
}

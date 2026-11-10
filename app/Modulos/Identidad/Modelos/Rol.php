<?php

namespace App\Modulos\Identidad\Modelos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rol extends Model
{
    protected $table = 'roles';

    protected $fillable = ['clave', 'nombre'];

    public function permisos(): BelongsToMany
    {
        return $this->belongsToMany(Permiso::class, 'rol_permiso');
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class);
    }
}

<?php

namespace App\Modulos\Actividades\Modelos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    protected $table = 'categorias';

    protected $fillable = ['nombre', 'descripcion'];

    public function actividades(): HasMany
    {
        return $this->hasMany(Actividad::class);
    }
}

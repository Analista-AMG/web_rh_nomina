<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrupoGerencia extends Model
{
    protected $table = 'dbo.dim_grupo_gerencia';
    public $timestamps = false;
    protected $fillable = ['nombre_grupo_gerencia'];
}

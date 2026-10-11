<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Una búsqueda del catálogo (texto normalizado y cuántos cursos encontró); sin IP ni datos del navegador. */
class SearchLog extends Model
{
    protected $guarded = [];
}

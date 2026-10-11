<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Nota o marcador personal de un alumno dentro de una lección (opcionalmente ligado a un minuto del video). */
class LessonNote extends Model
{
    public const NOTE = 'note';
    public const BOOKMARK = 'bookmark';

    protected $guarded = [];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Cuántos avisos de «retoma tu curso» se han enviado a una persona para un curso. */
class ProgressReminder extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['last_sent_at' => 'datetime'];
    }
}

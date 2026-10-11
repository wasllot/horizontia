<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Solicitud de acceso a la academia (como estudiante o como instructor). Nadie se registra solo: el equipo la aprueba. */
class AccessRequest extends Model
{
    public const PENDING = 'pending';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime', 'activated_at' => 'datetime'];
    }

    public function typeLabel(): string
    {
        return $this->type === 'tutor' ? 'Instructor' : 'Estudiante';
    }
}

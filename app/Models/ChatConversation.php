<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Conversación uno a uno entre dos personas. */
class ChatConversation extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['last_message_at' => 'datetime'];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id');
    }

    public function includes(User|int $user): bool
    {
        $id = $user instanceof User ? $user->id : $user;

        return (int) $this->user_low === (int) $id || (int) $this->user_high === (int) $id;
    }

    public function otherId(User|int $me): int
    {
        $id = $me instanceof User ? $me->id : $me;

        return (int) ((int) $this->user_low === (int) $id ? $this->user_high : $this->user_low);
    }
}

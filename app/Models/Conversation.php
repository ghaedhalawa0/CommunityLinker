<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

#[Fillable(['user_one_id', 'user_two_id'])]
class Conversation extends Model
{
    public function userOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function otherParticipant(User $participant): User
    {
        return $this->userOne->is($participant) ? $this->userTwo : $this->userOne;
    }

    public function markAsReadBy(User $participant): void
    {
        $readColumn = match ($participant->id) {
            $this->user_one_id => 'user_one_last_read_message_id',
            $this->user_two_id => 'user_two_last_read_message_id',
            default => null,
        };

        if ($readColumn === null) {
            return;
        }

        DB::table($this->getTable())
            ->where($this->getKeyName(), $this->getKey())
            ->update([$readColumn => $this->messages()->max('id') ?? 0]);
    }
}

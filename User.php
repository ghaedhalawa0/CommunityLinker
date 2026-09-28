<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'bio', 'avatar_path', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the posts created by the user.
     *
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Get the published posts created by the user.
     *
     * @return HasMany<Post, $this>
     */
    public function publishedPosts(): HasMany
    {
        return $this->posts()->where('status', Post::STATUS_PUBLISHED);
    }

    public function hasCompletedProfile(): bool
    {
        return filled($this->bio) && filled($this->avatar_path);
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function hasUnreadMessages(): bool
    {
        return Conversation::query()
            ->where(function (Builder $conversations): void {
                $conversations
                    ->where(function (Builder $conversation): void {
                        $conversation
                            ->where('user_one_id', $this->id)
                            ->whereHas('messages', function (Builder $messages): void {
                                $messages
                                    ->where('sender_id', '!=', $this->id)
                                    ->whereColumn('messages.id', '>', 'conversations.user_one_last_read_message_id');
                            });
                    })
                    ->orWhere(function (Builder $conversation): void {
                        $conversation
                            ->where('user_two_id', $this->id)
                            ->whereHas('messages', function (Builder $messages): void {
                                $messages
                                    ->where('sender_id', '!=', $this->id)
                                    ->whereColumn('messages.id', '>', 'conversations.user_two_last_read_message_id');
                            });
                    });
            })
            ->exists();
    }

    /**
     * @return HasMany<PostReaction, $this>
     */
    public function postReactions(): HasMany
    {
        return $this->hasMany(PostReaction::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}

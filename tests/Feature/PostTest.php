<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_create_posts_through_their_relationship(): void
    {
        $user = User::factory()->create();

        $post = $user->posts()->create([
            'body' => 'A community update',
        ]);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'user_id' => $user->id,
            'status' => Post::STATUS_DRAFT,
        ]);
        $this->assertSame($user->id, $post->user->id);
    }

    public function test_published_scope_excludes_draft_posts(): void
    {
        $user = User::factory()->create();
        $publishedPost = $user->posts()->create([
            'body' => 'A public update',
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
        $user->posts()->create([
            'body' => 'A private draft',
            'status' => Post::STATUS_DRAFT,
        ]);

        $this->assertSame([$publishedPost->id], Post::published()->pluck('id')->all());
    }

    public function test_deleting_a_user_removes_their_posts(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->create();

        $user->delete();

        $this->assertModelMissing($post);
    }
}

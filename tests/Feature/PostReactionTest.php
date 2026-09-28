<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostReactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_like_a_published_post(): void
    {
        $author = User::factory()->create();
        $user = User::factory()->create();
        $post = Post::factory()->for($author)->create();
        $profileUrl = route('profiles.show', ['user' => $author->username]);

        $response = $this->from($profileUrl)
            ->actingAs($user)
            ->post(route('posts.react', $post), [
                'reaction' => 'like',
            ]);

        $response->assertRedirect($profileUrl);
        $this->assertDatabaseHas('post_reactions', [
            'post_id' => $post->id,
            'user_id' => $user->id,
            'reaction' => 'like',
        ]);
    }

    public function test_user_can_switch_a_like_to_a_dislike_on_the_same_post(): void
    {
        $author = User::factory()->create();
        $user = User::factory()->create();
        $post = Post::factory()->for($author)->create();

        $this->actingAs($user)
            ->post(route('posts.react', $post), ['reaction' => 'like']);

        $this->actingAs($user)
            ->from(route('profiles.show', ['user' => $author->username]))
            ->post(route('posts.react', $post), ['reaction' => 'dislike'])
            ->assertRedirect(route('profiles.show', ['user' => $author->username]));

        $this->assertDatabaseHas('post_reactions', [
            'post_id' => $post->id,
            'user_id' => $user->id,
            'reaction' => 'dislike',
        ]);
        $this->assertDatabaseMissing('post_reactions', [
            'post_id' => $post->id,
            'user_id' => $user->id,
            'reaction' => 'like',
        ]);
    }

    public function test_reaction_returns_updated_counts_for_ajax_requests(): void
    {
        $author = User::factory()->create();
        $user = User::factory()->create();
        $post = Post::factory()->for($author)->create();

        $response = $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->postJson(route('posts.react', $post), ['reaction' => 'like']);

        $response->assertOk()->assertJson([
            'reaction' => 'like',
            'likes_count' => 1,
            'dislikes_count' => 0,
        ]);
    }

    public function test_unverified_user_can_react_to_a_post(): void
    {
        $author = User::factory()->create();
        $user = User::factory()->unverified()->create();
        $post = Post::factory()->for($author)->create();

        $this->actingAs($user)
            ->post(route('posts.react', $post), ['reaction' => 'like'])
            ->assertRedirect(route('home'));

        $this->assertDatabaseHas('post_reactions', [
            'post_id' => $post->id,
            'user_id' => $user->id,
            'reaction' => 'like',
        ]);
    }
}

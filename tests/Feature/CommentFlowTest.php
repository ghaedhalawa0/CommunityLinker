<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_in_user_can_comment_on_a_published_post(): void
    {
        $author = User::factory()->create();
        $commenter = User::factory()->create();
        $post = Post::factory()->for($author)->create();
        $body = 'The weekend market is worth a visit.';

        $profileUrl = route('profiles.show', ['user' => $author->username]);
        $response = $this->from($profileUrl)
            ->actingAs($commenter)
            ->post(route('comments.store', $post), [
                'body' => $body,
                'comment_post_id' => $post->id,
            ]);

        $response->assertRedirect($profileUrl);
        $this->assertDatabaseHas('comments', [
            'post_id' => $post->id,
            'user_id' => $commenter->id,
            'body' => $body,
        ]);
        $this->get($profileUrl)
            ->assertSee($body)
            ->assertSee($commenter->name);
    }

    public function test_comment_form_starts_collapsed_for_signed_in_users(): void
    {
        $post = Post::factory()->create();
        $user = $post->user;
        $profileUrl = route('profiles.show', ['user' => $post->user->username]);

        $this->actingAs($user)->get($profileUrl)
            ->assertSee('aria-expanded="false"', false)
            ->assertSee('aria-controls="comment-form-'.$post->id.'"', false)
            ->assertSee('id="comment-form-'.$post->id.'" class="comment-form"', false)
            ->assertSee('aria-label="Write a comment"', false)
            ->assertSee('hidden', false);
    }

    public function test_guest_is_redirected_to_sign_in_before_commenting(): void
    {
        $post = Post::factory()->create();

        $this->post(route('comments.store', $post), [
            'body' => 'A guest comment',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_unverified_user_can_comment(): void
    {
        $post = Post::factory()->create();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->post(route('comments.store', $post), [
                'body' => 'An unverified comment',
            ])
            ->assertRedirect(route('home'));

        $this->assertDatabaseHas('comments', [
            'post_id' => $post->id,
            'user_id' => $user->id,
            'body' => 'An unverified comment',
        ]);
    }

    public function test_draft_posts_cannot_receive_comments(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->draft()->create();
        $commenter = User::factory()->create();

        $this->actingAs($commenter)
            ->post(route('comments.store', $post), [
                'body' => 'A comment on a draft',
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_empty_comment_is_rejected(): void
    {
        $post = Post::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('comments.store', $post), [
                'body' => '',
            ])
            ->assertSessionHasErrors('body');

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_comment_text_is_escaped_in_the_feed(): void
    {
        $post = Post::factory()->create();
        $commenter = User::factory()->create();
        $body = '<script>alert(1)</script>';
        Comment::factory()->for($post)->for($commenter)->create(['body' => $body]);

        $this->get(route('profiles.show', ['user' => $post->user->username]))
            ->assertSee($body)
            ->assertDontSee('<script>', false);
    }

    public function test_feed_shows_only_the_latest_three_comments(): void
    {
        $post = Post::factory()->create();
        $commenter = User::factory()->create();

        Comment::factory()->for($post)->for($commenter)->create([
            'body' => 'The oldest comment',
            'created_at' => now()->subMinutes(4),
        ]);
        Comment::factory()->for($post)->for($commenter)->create([
            'body' => 'Comment two',
            'created_at' => now()->subMinutes(3),
        ]);
        Comment::factory()->for($post)->for($commenter)->create([
            'body' => 'Comment three',
            'created_at' => now()->subMinutes(2),
        ]);
        Comment::factory()->for($post)->for($commenter)->create([
            'body' => 'The newest comment',
            'created_at' => now()->subMinute(),
        ]);

        $this->get(route('profiles.show', ['user' => $post->user->username]))
            ->assertSee('4 comments')
            ->assertDontSee('The oldest comment')
            ->assertSee('The newest comment');
    }
}

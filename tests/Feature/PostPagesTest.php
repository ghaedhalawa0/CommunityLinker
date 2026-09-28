<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_feed_shows_published_posts_but_not_drafts(): void
    {
        $user = User::factory()->create();
        Post::factory()->for($user)->create(['body' => 'A public neighborhood note']);
        Post::factory()->for($user)->draft()->create(['body' => 'A note that is still a draft']);

        $this->actingAs($user)->get(route('home'))
            ->assertOk()
            ->assertSee('A public neighborhood note')
            ->assertDontSee('A note that is still a draft');
    }

    public function test_edited_post_displays_edited_label_and_updated_timestamp(): void
    {
        $user = User::factory()->create();
        $createdAt = now()->subHours(2);
        $updatedAt = now()->subHour();
        Post::factory()->for($user)->create([
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
            'published_at' => $createdAt,
        ]);

        $this->actingAs($user)
            ->get(route('profiles.show', ['user' => $user->username]))
            ->assertSee('class="post-edited">edited</span>', false)
            ->assertSee('datetime="'.$updatedAt->toIso8601String().'"', false)
            ->assertSee($updatedAt->diffForHumans());
    }

    public function test_guest_home_shows_the_landing_page(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Welcome to')
            ->assertSee('CommunityLinker.')
            ->assertSee(route('register'));
    }

    public function test_guest_is_redirected_before_composing_a_post(): void
    {
        $this->get(route('posts.create'))->assertRedirect(route('login'));
    }

    public function test_unverified_user_can_compose_a_post(): void
    {
        $this->actingAs(User::factory()->unverified()->create())
            ->get(route('posts.create'))
            ->assertOk()
            ->assertSee('What’s on your mind?');
    }

    public function test_unverified_user_sees_the_post_composer(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get(route('home'))
            ->assertSee('What’s happening around you?')
            ->assertSee('Write a post');
    }

    public function test_authenticated_user_can_publish_a_post(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('posts.store'), [
            'body' => 'A useful local recommendation',
            'status' => Post::STATUS_PUBLISHED,
        ]);

        $response->assertRedirect(route('profiles.show', ['user' => $user->username]));
        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'body' => 'A useful local recommendation',
            'status' => Post::STATUS_PUBLISHED,
        ]);
    }

    public function test_owner_can_edit_and_delete_a_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->draft()->create(['body' => 'An earlier draft']);

        $updateResponse = $this->actingAs($user)->put(route('posts.update', $post), [
            'body' => 'A finished community note',
            'status' => Post::STATUS_PUBLISHED,
        ]);

        $updateResponse->assertRedirect(route('profiles.show', ['user' => $user->username]));
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'body' => 'A finished community note',
            'status' => Post::STATUS_PUBLISHED,
        ]);

        $this->delete(route('posts.destroy', $post))->assertRedirect(route('profiles.show', ['user' => $user->username]));
        $this->assertModelMissing($post);
    }

    public function test_owner_post_actions_are_available_from_the_overflow_menu(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->create();

        $this->actingAs($user)
            ->get(route('profiles.show', ['user' => $user->username]))
            ->assertSee('<details class="post-menu">', false)
            ->assertSee('aria-label="More post actions"', false)
            ->assertSee('Edit post')
            ->assertSee('Delete post');
    }

    public function test_another_user_cannot_edit_a_post(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $post = Post::factory()->for($owner)->create(['body' => 'Owner’s post']);

        $this->actingAs($otherUser)
            ->put(route('posts.update', $post), [
                'body' => 'Changed by someone else',
                'status' => Post::STATUS_PUBLISHED,
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'body' => 'Owner’s post',
        ]);
    }
}

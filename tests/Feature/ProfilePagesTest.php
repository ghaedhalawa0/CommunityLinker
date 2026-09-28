<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_profile_shows_only_published_posts(): void
    {
        $user = User::factory()->create(['bio' => 'I love local gardens.']);
        Post::factory()->for($user)->create(['body' => 'A public profile post']);
        Post::factory()->for($user)->draft()->create(['body' => 'An unpublished profile draft']);

        $this->get(route('profiles.show', ['user' => $user->username]))
            ->assertOk()
            ->assertSee('I love local gardens.')
            ->assertSee('A public profile post')
            ->assertDontSee('An unpublished profile draft');
    }

    public function test_complete_profile_prompt_disappears_after_bio_and_avatar_are_added(): void
    {
        Storage::fake('public');
        $user = User::factory()->create([
            'bio' => 'Sharing neighborhood notes.',
            'avatar_path' => 'avatars/profile.png',
        ]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee('Complete your profile');

        $this->actingAs($user)
            ->get(route('profiles.show', ['user' => $user->username]))
            ->assertOk()
            ->assertDontSee('Add a little about yourself');
    }

    public function test_user_can_update_their_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Alex Morgan',
            'username' => 'alexmorgan',
            'bio' => 'Sharing little things from the neighborhood.',
        ]);

        $response->assertRedirect(route('profiles.show', ['user' => 'alexmorgan']));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'username' => 'alexmorgan',
            'bio' => 'Sharing little things from the neighborhood.',
        ]);
    }

    public function test_user_can_update_their_profile_picture_from_their_profile(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $avatar = UploadedFile::fake()->createWithContent(
            'avatar.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='),
        );

        $response = $this->actingAs($user)->patch(route('profile.avatar.update'), [
            'avatar' => $avatar,
        ]);

        $response->assertRedirect(route('profiles.show', ['user' => $user->username]));
        $avatarPath = $user->fresh()->avatar_path;

        $this->assertNotNull($avatarPath);
        $this->assertTrue(Storage::disk('public')->exists($avatarPath));
    }

    public function test_profile_picture_menu_shows_add_image_and_conditional_delete_action(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profiles.show', ['user' => $user->username]))
            ->assertSee('Add Image')
            ->assertDontSee('Delete image');

        $user->update(['avatar_path' => 'avatars/profile.png']);

        $this->actingAs($user)
            ->get(route('profiles.show', ['user' => $user->username]))
            ->assertSee('Add Image')
            ->assertSee('Delete image');
    }

    public function test_user_can_delete_their_profile_picture(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['avatar_path' => 'avatars/profile.png']);
        Storage::disk('public')->put($user->avatar_path, 'avatar');

        $response = $this->actingAs($user)->delete(route('profile.avatar.delete'));

        $response->assertRedirect(route('profiles.show', ['user' => $user->username]));
        $this->assertNull($user->fresh()->avatar_path);
        $this->assertFalse(Storage::disk('public')->exists('avatars/profile.png'));
    }

    public function test_owner_profile_displays_account_delete_confirmation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profiles.show', ['user' => $user->username]))
            ->assertSee('Delete Account')
            ->assertSee('Are you sure?')
            ->assertSee('action="'.route('account.destroy').'"', false);
    }

    public function test_user_can_delete_their_account_and_dependent_content(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $userPost = Post::factory()->for($user)->create();
        $otherPost = Post::factory()->for($otherUser)->create();
        $comment = Comment::factory()->for($otherPost)->for($user)->create();
        $reaction = $user->postReactions()->create([
            'post_id' => $otherPost->id,
            'reaction' => 'like',
        ]);

        $this->actingAs($user)
            ->delete(route('account.destroy'))
            ->assertRedirect(route('home'))
            ->assertSessionHas('status', 'Your account has been deleted.');

        $this->assertGuest();
        $this->assertModelMissing($user);
        $this->assertModelMissing($userPost);
        $this->assertModelMissing($comment);
        $this->assertModelMissing($reaction);
        $this->assertModelExists($otherUser);
        $this->assertModelExists($otherPost);
    }

    public function test_guest_cannot_delete_an_account(): void
    {
        $this->delete(route('account.destroy'))
            ->assertRedirect(route('login'));
    }
}

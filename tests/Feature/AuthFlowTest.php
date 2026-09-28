<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_register_without_email_verification(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Alex Morgan',
            'username' => 'alexmorgan',
            'email' => 'alex@example.com',
            'password' => 'community-password',
            'password_confirmation' => 'community-password',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'username' => 'alexmorgan',
            'email' => 'alex@example.com',
            'email_verified_at' => null,
        ]);
    }

    public function test_guest_can_sign_in_and_sign_out(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_invalid_credentials_return_a_sign_in_error(): void
    {
        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => 'missing@example.com',
            'password' => 'not-the-password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_user_can_sign_in_without_email_verification(): void
    {
        $user = User::factory()->unverified()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }
}

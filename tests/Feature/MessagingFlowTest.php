<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessagingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_exchange_persisted_private_messages(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $body = 'The community garden looks lovely.';

        $response = $this->actingAs($sender)->postJson(
            route('messages.store', ['user' => $recipient->username]),
            ['body' => $body],
        );

        $response->assertCreated()
            ->assertJsonPath('body', $body)
            ->assertJsonPath('sender_id', $sender->id);
        $this->assertDatabaseHas('messages', [
            'sender_id' => $sender->id,
            'body' => $body,
        ]);
        $this->assertDatabaseCount('conversations', 1);

        $this->actingAs($sender)
            ->get(route('messages.index'))
            ->assertSee($recipient->name)
            ->assertSee($body);
        $this->get(route('messages.show', ['user' => $recipient->username]))
            ->assertSee($body);

        $messageId = $response->json('id');
        $this->actingAs($recipient)
            ->getJson(route('messages.updates', [
                'user' => $sender->username,
                'after' => 0,
            ]))
            ->assertOk()
            ->assertJsonPath('messages.0.id', $messageId)
            ->assertJsonPath('messages.0.body', $body);
    }

    public function test_users_cannot_read_messages_from_an_unrelated_conversation(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $unrelatedUser = User::factory()->create();

        $this->actingAs($sender)->postJson(
            route('messages.store', ['user' => $recipient->username]),
            ['body' => 'A private note.'],
        );

        $this->actingAs($unrelatedUser)
            ->get(route('messages.show', ['user' => $recipient->username]))
            ->assertOk()
            ->assertDontSee('A private note.');
    }

    public function test_users_cannot_message_themselves(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('messages.store', ['user' => $user->username]), [
                'body' => 'Talking to myself.',
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('conversations', 0);
        $this->assertDatabaseCount('messages', 0);
    }

    public function test_empty_message_is_rejected(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $this->actingAs($sender)
            ->postJson(route('messages.store', ['user' => $recipient->username]), [
                'body' => '',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('body');

        $this->assertDatabaseCount('messages', 0);
    }

    public function test_message_over_two_thousand_characters_is_rejected(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $this->actingAs($sender)
            ->postJson(route('messages.store', ['user' => $recipient->username]), [
                'body' => str_repeat('a', 2001),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('body');

        $this->assertDatabaseCount('messages', 0);
    }

    public function test_message_body_is_escaped_in_the_conversation(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $body = '<script>alert(1)</script>';

        $this->actingAs($sender)->postJson(
            route('messages.store', ['user' => $recipient->username]),
            ['body' => $body],
        );

        $this->actingAs($recipient)
            ->get(route('messages.show', ['user' => $sender->username]))
            ->assertSee($body)
            ->assertDontSee('<script>', false);
    }

    public function test_guest_is_redirected_to_login_before_accessing_messages(): void
    {
        $recipient = User::factory()->create();

        $this->get(route('messages.index'))
            ->assertRedirect(route('login'));

        $this->post(route('messages.store', ['user' => $recipient->username]), [
            'body' => 'A guest message.',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('messages', 0);
    }

    public function test_member_profile_links_to_private_messages(): void
    {
        $viewer = User::factory()->create();
        $profile = User::factory()->create();

        $this->actingAs($viewer)
            ->get(route('profiles.show', ['user' => $profile->username]))
            ->assertSee(route('messages.show', ['user' => $profile->username]), false)
            ->assertSee('Message');
    }

    public function test_published_post_has_a_message_action_for_other_members_only(): void
    {
        $author = User::factory()->create();
        $viewer = User::factory()->create();
        Post::factory()->for($author)->create();
        $messageUrl = route('messages.show', ['user' => $author->username]);

        $this->actingAs($viewer)
            ->get(route('home'))
            ->assertSee($messageUrl, false)
            ->assertSee('aria-label="Message '.e($author->name).'"', false);

        $this->actingAs($author)
            ->get(route('home'))
            ->assertDontSee('aria-label="Message '.e($author->name).'"', false);
    }

    public function test_conversation_shows_accessible_smiley_picker(): void
    {
        $viewer = User::factory()->create();
        $recipient = User::factory()->create();

        $this->actingAs($viewer)
            ->get(route('messages.show', ['user' => $recipient->username]))
            ->assertSee('aria-label="Add a smiley"', false)
            ->assertSee('aria-label="Choose a smiley"', false)
            ->assertSee('data-emoji-value="😊"', false);
    }

    public function test_incoming_message_shows_navigation_dot_until_conversation_is_opened(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $this->actingAs($sender)->postJson(
            route('messages.store', ['user' => $recipient->username]),
            ['body' => 'A new message for you.'],
        );

        $this->actingAs($recipient)
            ->get(route('home'))
            ->assertSee('data-unread-indicator', false);

        $this->get(route('messages.show', ['user' => $sender->username]))
            ->assertSee('A new message for you.');

        $this->get(route('home'))
            ->assertDontSee('data-unread-indicator', false);
    }

    public function test_incoming_message_is_unread_for_the_first_conversation_participant(): void
    {
        $recipient = User::factory()->create();
        $sender = User::factory()->create();

        $this->actingAs($sender)->postJson(
            route('messages.store', ['user' => $recipient->username]),
            ['body' => 'A message for the first participant.'],
        );

        $this->actingAs($recipient)
            ->get(route('home'))
            ->assertSee('data-unread-indicator', false);
    }

    public function test_sender_can_delete_their_message_from_the_conversation(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $response = $this->actingAs($sender)->postJson(
            route('messages.store', ['user' => $recipient->username]),
            ['body' => 'Please remove this message.'],
        );
        $messageId = $response->json('id');

        $this->get(route('messages.show', ['user' => $recipient->username]))
            ->assertSee('Delete message')
            ->assertSee('data-delete-url-template', false);
        $this->actingAs($recipient)
            ->get(route('messages.show', ['user' => $sender->username]))
            ->assertDontSee('Delete message');

        $this->actingAs($sender)->deleteJson(route('messages.destroy', [
            'user' => $recipient->username,
            'message' => $messageId,
        ]))->assertNoContent();

        $this->assertDatabaseMissing('messages', ['id' => $messageId]);
        $this->get(route('messages.show', ['user' => $recipient->username]))
            ->assertDontSee('Please remove this message.');
    }

    public function test_recipient_cannot_delete_another_users_message(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $response = $this->actingAs($sender)->postJson(
            route('messages.store', ['user' => $recipient->username]),
            ['body' => 'This message belongs to the sender.'],
        );
        $messageId = $response->json('id');

        $this->actingAs($recipient)
            ->deleteJson(route('messages.destroy', [
                'user' => $sender->username,
                'message' => $messageId,
            ]))
            ->assertForbidden();

        $this->assertDatabaseHas('messages', ['id' => $messageId]);
    }

    public function test_user_cannot_delete_a_message_from_another_conversation(): void
    {
        $user = User::factory()->create();
        $otherParticipant = User::factory()->create();
        $unrelatedParticipant = User::factory()->create();
        $messageResponse = $this->actingAs($user)->postJson(
            route('messages.store', ['user' => $otherParticipant->username]),
            ['body' => 'A separate conversation message.'],
        );
        $messageId = $messageResponse->json('id');

        $this->deleteJson(route('messages.destroy', [
            'user' => $unrelatedParticipant->username,
            'message' => $messageId,
        ]))->assertNotFound();

        $this->assertDatabaseHas('messages', ['id' => $messageId]);
    }

    public function test_sender_can_edit_their_message_but_recipient_cannot(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $messageId = $this->actingAs($sender)->postJson(
            route('messages.store', ['user' => $recipient->username]),
            ['body' => 'Original text.'],
        )->json('id');

        $this->get(route('messages.show', ['user' => $recipient->username]))
            ->assertSee('aria-label="Message options"', false)
            ->assertSee('data-message-menu-panel hidden', false)
            ->assertSee('aria-label="Edit message"', false)
            ->assertSee('aria-label="Reply to message"', false)
            ->assertSee('aria-label="Forward message"', false)
            ->assertSee('aria-label="Delete message"', false);

        $this->patchJson(route('messages.update', [
            'user' => $recipient->username,
            'message' => $messageId,
        ]), ['body' => 'Edited text.'])
            ->assertOk()
            ->assertJsonPath('body', 'Edited text.');

        $this->actingAs($recipient)
            ->patchJson(route('messages.update', [
                'user' => $sender->username,
                'message' => $messageId,
            ]), ['body' => 'Unauthorized edit.'])
            ->assertForbidden();

        $this->get(route('messages.show', ['user' => $sender->username]))
            ->assertDontSee('aria-label="Edit message"', false)
            ->assertDontSee('aria-label="Delete message"', false)
            ->assertSee('aria-label="Reply to message"', false)
            ->assertSee('aria-label="Forward message"', false);

        $this->assertDatabaseHas('messages', [
            'id' => $messageId,
            'body' => 'Edited text.',
        ]);
    }

    public function test_message_edits_are_included_in_conversation_updates(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $messageId = $this->actingAs($sender)->postJson(
            route('messages.store', ['user' => $recipient->username]),
            ['body' => 'Before edit.'],
        )->json('id');

        $this->patchJson(route('messages.update', [
            'user' => $recipient->username,
            'message' => $messageId,
        ]), ['body' => 'After edit.'])->assertOk();

        $this->actingAs($recipient)
            ->getJson(route('messages.updates', [
                'user' => $sender->username,
                'after' => $messageId,
                'updated_after' => now()->subMinute()->toIso8601String(),
            ]))
            ->assertOk()
            ->assertJsonPath('messages.0.id', $messageId)
            ->assertJsonPath('messages.0.body', 'After edit.');
    }

    public function test_user_can_reply_to_a_message_in_the_same_conversation(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $originalMessageId = $this->actingAs($sender)->postJson(
            route('messages.store', ['user' => $recipient->username]),
            ['body' => 'Meet by the library?'],
        )->json('id');

        $reply = $this->actingAs($recipient)->postJson(
            route('messages.store', ['user' => $sender->username]),
            [
                'body' => 'Yes, see you there.',
                'reply_to_message_id' => $originalMessageId,
            ],
        );

        $reply->assertCreated()
            ->assertJsonPath('reply_to.id', $originalMessageId)
            ->assertJsonPath('reply_to.body', 'Meet by the library?');
        $this->assertDatabaseHas('messages', [
            'id' => $reply->json('id'),
            'reply_to_message_id' => $originalMessageId,
        ]);
        $this->get(route('messages.show', ['user' => $sender->username]))
            ->assertSee('Meet by the library?')
            ->assertSee('Yes, see you there.');
    }

    public function test_user_cannot_reply_to_a_message_from_another_conversation(): void
    {
        $sender = User::factory()->create();
        $firstRecipient = User::factory()->create();
        $otherRecipient = User::factory()->create();
        $messageId = $this->actingAs($sender)->postJson(
            route('messages.store', ['user' => $firstRecipient->username]),
            ['body' => 'Private to the first recipient.'],
        )->json('id');

        $this->postJson(route('messages.store', ['user' => $otherRecipient->username]), [
            'body' => 'Trying to reply across conversations.',
            'reply_to_message_id' => $messageId,
        ])->assertNotFound();

        $this->assertDatabaseCount('conversations', 1);
        $this->assertDatabaseCount('messages', 1);
    }

    public function test_user_can_forward_a_message_to_another_member(): void
    {
        $sender = User::factory()->create();
        $conversationPartner = User::factory()->create();
        $forwardRecipient = User::factory()->create();
        $messageId = $this->actingAs($sender)->postJson(
            route('messages.store', ['user' => $conversationPartner->username]),
            ['body' => 'The meeting starts at six.'],
        )->json('id');

        $forward = $this->postJson(route('messages.forward', ['message' => $messageId]), [
            'recipient_id' => $forwardRecipient->id,
        ]);

        $forward->assertCreated()
            ->assertJsonPath('body', 'The meeting starts at six.')
            ->assertJsonPath('forwarded_from.id', $messageId);
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $forward->json('conversation_id'),
            'sender_id' => $sender->id,
            'body' => 'The meeting starts at six.',
            'forwarded_from_message_id' => $messageId,
        ]);
        $this->get(route('messages.show', ['user' => $forwardRecipient->username]))
            ->assertSee('Forwarded from '.$sender->name)
            ->assertSee('The meeting starts at six.');
    }

    public function test_forward_dialog_displays_a_searchable_recipient_list(): void
    {
        $viewer = User::factory()->create();
        $conversationPartner = User::factory()->create();
        $anotherMember = User::factory()->create();

        $this->actingAs($viewer)
            ->get(route('messages.show', ['user' => $conversationPartner->username]))
            ->assertSee('Forward message')
            ->assertSee('Choose a recipient')
            ->assertSee('data-forward-search', false)
            ->assertSee('data-forward-recipient-list', false)
            ->assertSee('Search by name or username')
            ->assertDontSee($anotherMember->name);
    }

    public function test_recipient_search_matches_names_and_usernames_without_returning_the_current_user(): void
    {
        $viewer = User::factory()->create([
            'name' => 'Garden Account',
            'username' => 'garden_account',
        ]);
        $matchingName = User::factory()->create([
            'name' => 'Garden Neighbor',
            'username' => 'neighbor_one',
        ]);
        $matchingUsername = User::factory()->create([
            'name' => 'Riley Rivers',
            'username' => 'garden_riley',
        ]);
        User::factory()->create([
            'name' => 'Other Member',
            'username' => 'other_member',
        ]);

        $this->actingAs($viewer)
            ->getJson(route('messages.recipients', ['query' => 'garden']))
            ->assertOk()
            ->assertJsonCount(2, 'recipients')
            ->assertJsonFragment(['id' => $matchingName->id, 'name' => 'Garden Neighbor'])
            ->assertJsonFragment(['id' => $matchingUsername->id, 'name' => 'Riley Rivers'])
            ->assertJsonMissing(['id' => $viewer->id]);
    }

    public function test_guest_cannot_search_forward_recipients(): void
    {
        $this->getJson(route('messages.recipients', ['query' => 'garden']))
            ->assertUnauthorized();
    }

    public function test_user_cannot_forward_a_message_from_another_conversation(): void
    {
        $sender = User::factory()->create();
        $conversationPartner = User::factory()->create();
        $outsider = User::factory()->create();
        $forwardRecipient = User::factory()->create();
        $messageId = $this->actingAs($sender)->postJson(
            route('messages.store', ['user' => $conversationPartner->username]),
            ['body' => 'This message is private.'],
        )->json('id');

        $this->actingAs($outsider)
            ->postJson(route('messages.forward', ['message' => $messageId]), [
                'recipient_id' => $forwardRecipient->id,
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('messages', 1);
    }
}

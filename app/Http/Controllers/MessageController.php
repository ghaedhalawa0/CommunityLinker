<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $conversations = Conversation::query()
            ->where(fn (Builder $query) => $query
                ->where('user_one_id', $user->id)
                ->orWhere('user_two_id', $user->id))
            ->with(['userOne', 'userTwo', 'latestMessage.sender'])
            ->orderByDesc('updated_at')
            ->get();

        return view('messages.index', compact('conversations', 'user'));
    }

    public function unread(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return response()->json(['has_unread_messages' => $user->hasUnreadMessages()]);
    }

    public function recipients(Request $request): JsonResponse
    {
        $viewer = $request->user();
        abort_unless($viewer instanceof User, 401);

        $validated = $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:50'],
        ]);
        $searchTerm = '%'.$validated['query'].'%';
        $recipients = User::query()
            ->whereKeyNot($viewer->id)
            ->where(fn (Builder $users) => $users
                ->where('name', 'like', $searchTerm)
                ->orWhere('username', 'like', $searchTerm))
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'username', 'avatar_path']);

        return response()->json([
            'recipients' => $recipients->map(fn (User $recipient): array => [
                'id' => $recipient->id,
                'name' => $recipient->name,
                'username' => $recipient->username,
                'avatar_url' => $recipient->avatar_path
                    ? asset('storage/'.$recipient->avatar_path)
                    : null,
            ]),
        ]);
    }

    public function show(Request $request, User $user): View
    {
        $viewer = $request->user();
        abort_unless($viewer instanceof User, 401);
        abort_unless(! $viewer->is($user), 404);

        $conversation = $this->findConversation($viewer, $user);
        $messages = $conversation
            ? $conversation->messages()
                ->with(['sender', 'replyTo.sender', 'forwardedFrom.sender'])
                ->latest('id')
                ->limit(100)
                ->get()
                ->reverse()
                ->values()
            : collect();

        $conversation?->markAsReadBy($viewer);

        return view('messages.show', compact('conversation', 'messages', 'user'));
    }

    public function store(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $sender = $request->user();
        abort_unless($sender instanceof User, 401);
        abort_unless(! $sender->is($user), 404);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'reply_to_message_id' => ['nullable', 'integer'],
        ]);

        $conversation = $this->findConversation($sender, $user);
        $replyTo = isset($validated['reply_to_message_id'])
            ? $conversation?->messages()->find($validated['reply_to_message_id'])
            : null;
        abort_unless(! isset($validated['reply_to_message_id']) || $replyTo instanceof Message, 404);

        $conversation ??= Conversation::firstOrCreate([
            'user_one_id' => min($sender->id, $user->id),
            'user_two_id' => max($sender->id, $user->id),
        ]);
        $message = $conversation->messages()->make([
            'body' => $validated['body'],
            'reply_to_message_id' => $replyTo?->id,
        ]);
        $message->sender()->associate($sender);
        $message->save();
        $conversation->touch();
        $message->load(['sender', 'replyTo.sender', 'forwardedFrom.sender']);

        if ($request->expectsJson()) {
            return response()->json($this->messagePayload($message), 201);
        }

        return redirect()
            ->route('messages.show', ['user' => $user->username])
            ->with('status', 'Your message was sent.');
    }

    public function update(Request $request, User $user, Message $message): JsonResponse|RedirectResponse
    {
        $sender = $request->user();
        abort_unless($sender instanceof User, 401);
        abort_unless(! $sender->is($user), 404);

        $conversation = $this->findConversation($sender, $user);
        abort_unless($conversation && $message->conversation_id === $conversation->id, 404);
        abort_unless($message->sender_id === $sender->id, 403);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);
        $message->update($validated);
        $conversation->touch();
        $message->load(['sender', 'replyTo.sender', 'forwardedFrom.sender']);

        if ($request->expectsJson()) {
            return response()->json($this->messagePayload($message));
        }

        return redirect()
            ->route('messages.show', ['user' => $user->username])
            ->with('status', 'Your message was updated.');
    }

    public function forward(Request $request, Message $message): JsonResponse|RedirectResponse
    {
        $sender = $request->user();
        abort_unless($sender instanceof User, 401);

        $validated = $request->validate([
            'recipient_id' => ['required', 'integer', 'exists:users,id', Rule::notIn([$sender->id])],
        ]);
        $sourceConversation = $message->conversation;
        abort_unless(
            $sourceConversation && in_array($sender->id, [$sourceConversation->user_one_id, $sourceConversation->user_two_id], true),
            404,
        );

        $recipient = User::findOrFail($validated['recipient_id']);
        $targetConversation = Conversation::firstOrCreate([
            'user_one_id' => min($sender->id, $recipient->id),
            'user_two_id' => max($sender->id, $recipient->id),
        ]);
        $forwardedMessage = $targetConversation->messages()->make([
            'body' => $message->body,
            'forwarded_from_message_id' => $message->id,
        ]);
        $forwardedMessage->sender()->associate($sender);
        $forwardedMessage->save();
        $targetConversation->touch();
        $forwardedMessage->load(['sender', 'replyTo.sender', 'forwardedFrom.sender']);

        if ($request->expectsJson()) {
            return response()->json($this->messagePayload($forwardedMessage), 201);
        }

        return redirect()
            ->route('messages.show', ['user' => $recipient->username])
            ->with('status', 'Message forwarded.');
    }

    public function updates(Request $request, User $user): JsonResponse
    {
        $viewer = $request->user();
        abort_unless($viewer instanceof User, 401);
        abort_unless(! $viewer->is($user), 404);

        $validated = $request->validate([
            'after' => ['nullable', 'integer', 'min:0'],
            'updated_after' => ['nullable', 'date'],
        ]);
        $updatedAfter = isset($validated['updated_after'])
            ? Carbon::parse($validated['updated_after'])->toDateTimeString()
            : null;
        $conversation = $this->findConversation($viewer, $user);
        $messages = $conversation
            ? $conversation->messages()
                ->with(['sender', 'replyTo.sender', 'forwardedFrom.sender'])
                ->where(fn (Builder $query) => $query
                    ->where('id', '>', $validated['after'] ?? 0)
                    ->when($updatedAfter !== null, fn (Builder $query) => $query
                        ->orWhere('updated_at', '>=', $updatedAfter)))
                ->oldest('id')
                ->limit(50)
                ->get()
            : collect();

        if ($conversation && $messages->contains(fn (Message $message): bool => $message->sender_id !== $viewer->id)) {
            $conversation->markAsReadBy($viewer);
        }

        return response()->json([
            'messages' => $messages->map(fn (Message $message): array => $this->messagePayload($message)),
        ]);
    }

    public function destroy(Request $request, User $user, Message $message): JsonResponse|RedirectResponse
    {
        $sender = $request->user();
        abort_unless($sender instanceof User, 401);
        abort_unless(! $sender->is($user), 404);

        $conversation = $this->findConversation($sender, $user);
        abort_unless($conversation && $message->conversation_id === $conversation->id, 404);
        abort_unless($message->sender_id === $sender->id, 403);

        $message->delete();

        if ($request->expectsJson()) {
            return response()->json(status: 204);
        }

        return redirect()
            ->route('messages.show', ['user' => $user->username])
            ->with('status', 'Your message was deleted.');
    }

    private function findConversation(User $firstUser, User $secondUser): ?Conversation
    {
        return Conversation::query()
            ->where('user_one_id', min($firstUser->id, $secondUser->id))
            ->where('user_two_id', max($firstUser->id, $secondUser->id))
            ->first();
    }

    /**
     * @return array{
     *     id: int,
     *     conversation_id: int,
     *     body: string,
     *     sender_id: int,
     *     sender_name: string,
     *     created_at: string,
     *     updated_at: string,
     *     reply_to: array{id: int, body: string, sender_name: string}|null,
     *     forwarded_from: array{id: int, sender_name: string}|null
     * }
     */
    private function messagePayload(Message $message): array
    {
        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'body' => $message->body,
            'sender_id' => $message->sender_id,
            'sender_name' => $message->sender->name,
            'created_at' => $message->created_at->toIso8601String(),
            'updated_at' => $message->updated_at->toIso8601String(),
            'reply_to' => $message->replyTo ? [
                'id' => $message->replyTo->id,
                'body' => $message->replyTo->body,
                'sender_name' => $message->replyTo->sender->name,
            ] : null,
            'forwarded_from' => $message->forwardedFrom ? [
                'id' => $message->forwardedFrom->id,
                'sender_name' => $message->forwardedFrom->sender->name,
            ] : null,
        ];
    }
}

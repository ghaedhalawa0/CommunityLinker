@extends('layouts.app')

@section('title', 'Chat with '.$user->name.' · CommunityLinker')

@section('content')
    <section
        class="chat-page"
        data-chat
        data-current-user-id="{{ auth()->id() }}"
        data-delete-url-template="{{ route('messages.destroy', ['user' => $user->username, 'message' => 'MESSAGE_ID']) }}"
        data-update-url-template="{{ route('messages.update', ['user' => $user->username, 'message' => 'MESSAGE_ID']) }}"
        data-forward-url-template="{{ route('messages.forward', ['message' => 'MESSAGE_ID']) }}"
    >
        <a class="chat-back-link" href="{{ route('messages.index') }}"><span aria-hidden="true">←</span> All messages</a>

        <header class="chat-heading">
            @include('partials.avatar', ['user' => $user, 'size' => 'md'])
            <div>
                <h1>{{ $user->name }}</h1>
                <a href="{{ route('profiles.show', ['user' => $user->username]) }}">{{ '@'.$user->username }}</a>
            </div>
        </header>

        <div class="chat-history">
            @if ($messages->isEmpty())
                <p class="chat-empty" data-chat-empty>Say hello to start the conversation.</p>
            @endif
            <ol
                class="chat-message-list"
                data-message-list
                data-after-id="{{ $messages->last()?->id ?? 0 }}"
                data-updated-after="{{ $messages->max('updated_at')?->toIso8601String() }}"
                data-updates-url="{{ route('messages.updates', ['user' => $user->username]) }}"
                aria-live="polite"
                aria-relevant="additions"
            >
                @foreach ($messages as $message)
                    @php($isOwnMessage = $message->sender_id === auth()->id())
                    <li
                        class="chat-message-row {{ $isOwnMessage ? 'is-own-message' : '' }}"
                        data-message-id="{{ $message->id }}"
                        data-message-body="{{ $message->body }}"
                        data-message-sender="{{ $message->sender->name }}"
                    >
                        <div class="chat-message-stack">
                            <article class="chat-message-bubble">
                            @unless ($isOwnMessage)
                                <strong>{{ $message->sender->name }}</strong>
                            @endunless
                            @if ($message->forwardedFrom)
                                <small class="chat-forwarded-label">Forwarded from {{ $message->forwardedFrom->sender->name }}</small>
                            @endif
                            @if ($message->replyTo)
                                <blockquote class="chat-reply-quote">
                                    <strong>{{ $message->replyTo->sender->name }}</strong>
                                    <span>{{ $message->replyTo->body }}</span>
                                </blockquote>
                            @endif
                            <p data-message-body-text>{{ $message->body }}</p>
                            @if ($isOwnMessage)
                                <form
                                    class="chat-message-edit-form"
                                    method="POST"
                                    action="{{ route('messages.update', ['user' => $user->username, 'message' => $message->id]) }}"
                                    data-message-edit-form
                                    hidden
                                >
                                    @csrf
                                    @method('PATCH')
                                    <label class="visually-hidden" for="edit-message-{{ $message->id }}">Edit message</label>
                                    <textarea id="edit-message-{{ $message->id }}" name="body" rows="2" maxlength="2000" required>{{ $message->body }}</textarea>
                                    <span class="chat-edit-actions">
                                        <button class="chat-action-button" type="submit" aria-label="Save edit" title="Save edit">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6" /></svg>
                                        </button>
                                        <button class="chat-action-button" type="button" data-message-edit-cancel aria-label="Cancel edit" title="Cancel edit">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m18 6-12 12M6 6l12 12" /></svg>
                                        </button>
                                    </span>
                                </form>
                            @endif
                                <div class="chat-message-meta">
                                    <time datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->format('g:i A') }}</time>
                                </div>
                            </article>
                            <div class="chat-message-menu" data-message-menu>
                                <button
                                    class="chat-more-trigger"
                                    type="button"
                                    aria-label="Message options"
                                    aria-controls="message-actions-{{ $message->id }}"
                                    aria-expanded="false"
                                    title="Message options"
                                    data-message-menu-trigger
                                >•••</button>
                                <div class="chat-message-menu-panel" id="message-actions-{{ $message->id }}" data-message-menu-panel hidden>
                                    @if ($isOwnMessage)
                                        <button class="chat-message-menu-item" type="button" data-message-action="edit" aria-label="Edit message">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9" /><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z" /></svg>
                                            <span>Edit message</span>
                                        </button>
                                    @endif
                                    <button class="chat-message-menu-item" type="button" data-message-action="reply" aria-label="Reply to message">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 14-5-5 5-5" /><path d="M4 9h10a5 5 0 0 1 0 10h-1" /></svg>
                                        <span>Reply</span>
                                    </button>
                                    <button class="chat-message-menu-item" type="button" data-message-action="forward" aria-label="Forward message">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 14 5-5-5-5" /><path d="M20 9H10a5 5 0 0 0 0 10h1" /></svg>
                                        <span>Forward</span>
                                    </button>
                                    @if ($isOwnMessage)
                                        <form class="chat-message-delete-form" method="POST" action="{{ route('messages.destroy', ['user' => $user->username, 'message' => $message->id]) }}" data-message-delete-form>
                                            @csrf
                                            @method('DELETE')
                                            <button class="chat-message-menu-item chat-message-menu-item-danger" type="submit" aria-label="Delete message">
                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18" /><path d="M8 6V4h8v2m-9 0 1 14h8l1-14M10 10v6m4-6v6" /></svg>
                                                <span>Delete message</span>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>

        <form class="chat-composer" method="POST" action="{{ route('messages.store', ['user' => $user->username]) }}" data-message-form>
            @csrf
            <input type="hidden" name="reply_to_message_id" data-reply-to-message-id>
            <div class="chat-reply-preview" data-reply-preview hidden>
                <span><strong>Replying to <span data-reply-sender></span></strong><span data-reply-body></span></span>
                <button class="chat-action-button" type="button" data-reply-cancel aria-label="Cancel reply" title="Cancel reply">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m18 6-12 12M6 6l12 12" /></svg>
                </button>
            </div>
            <label class="visually-hidden" for="message-body">Write a message</label>
            <textarea id="message-body" name="body" rows="2" maxlength="2000" placeholder="Write a message…" required>{{ old('body') }}</textarea>
            <div class="chat-emoji-tools">
                <button
                    class="icon-button chat-emoji-trigger"
                    type="button"
                    aria-label="Add a smiley"
                    aria-controls="chat-emoji-picker"
                    aria-expanded="false"
                    title="Add a smiley"
                    data-emoji-trigger
                >☺</button>
                <div class="chat-emoji-picker" id="chat-emoji-picker" role="group" aria-label="Choose a smiley" data-emoji-picker hidden>
                    <button class="chat-emoji-option" type="button" data-emoji-value="🙂" aria-label="Slightly smiling face">🙂</button>
                    <button class="chat-emoji-option" type="button" data-emoji-value="😀" aria-label="Grinning face">😀</button>
                    <button class="chat-emoji-option" type="button" data-emoji-value="😄" aria-label="Smiling face with open mouth">😄</button>
                    <button class="chat-emoji-option" type="button" data-emoji-value="😅" aria-label="Smiling face with sweat">😅</button>
                    <button class="chat-emoji-option" type="button" data-emoji-value="😂" aria-label="Face with tears of joy">😂</button>
                    <button class="chat-emoji-option" type="button" data-emoji-value="😊" aria-label="Smiling face with smiling eyes">😊</button>
                    <button class="chat-emoji-option" type="button" data-emoji-value="😍" aria-label="Heart eyes">😍</button>
                    <button class="chat-emoji-option" type="button" data-emoji-value="😎" aria-label="Smiling face with sunglasses">😎</button>
                    <button class="chat-emoji-option" type="button" data-emoji-value="🤗" aria-label="Hugging face">🤗</button>
                    <button class="chat-emoji-option" type="button" data-emoji-value="😉" aria-label="Winking face">😉</button>
                    <button class="chat-emoji-option" type="button" data-emoji-value="🙌" aria-label="Raising hands">🙌</button>
                    <button class="chat-emoji-option" type="button" data-emoji-value="❤️" aria-label="Red heart">❤️</button>
                    <button class="chat-emoji-option" type="button" data-emoji-value="👍" aria-label="Thumbs up">👍</button>
                    <button class="chat-emoji-option" type="button" data-emoji-value="😢" aria-label="Crying face">😢</button>
                    <button class="chat-emoji-option" type="button" data-emoji-value="😮" aria-label="Face with open mouth">😮</button>
                </div>
            </div>
            <button class="button button-primary" type="submit">Send <span aria-hidden="true">↗</span></button>
        </form>
        <dialog class="chat-forward-dialog" data-forward-dialog>
            <form method="POST" action="{{ route('messages.forward', ['message' => 'MESSAGE_ID']) }}" data-forward-form>
                @csrf
                <div class="chat-forward-heading">
                    <div>
                        <p class="eyebrow">SHARE A MESSAGE</p>
                        <h2>Forward message</h2>
                    </div>
                    <button class="chat-action-button" type="button" data-forward-cancel aria-label="Close forward dialog" title="Close">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m18 6-12 12M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="chat-forward-preview" data-forward-preview hidden>
                    <span class="eyebrow">MESSAGE</span>
                    <p data-forward-message-preview></p>
                </div>
                <div class="chat-forward-recipient-heading">
                    <label for="forward-search">Choose a recipient</label>
                    <span data-forward-recipient-count>0</span>
                </div>
                <label class="visually-hidden" for="forward-search">Find a community member</label>
                <input
                    class="chat-forward-search"
                    id="forward-search"
                    type="search"
                    placeholder="Search by name or username"
                    autocomplete="off"
                    data-forward-search
                    data-search-url="{{ route('messages.recipients') }}"
                >
                <fieldset class="chat-forward-recipient-list" aria-label="Recipients" data-forward-recipient-list>
                    <p class="chat-forward-empty" data-forward-empty>Search by name or username to find recipients.</p>
                </fieldset>
                <p class="chat-forward-no-results" data-forward-no-results hidden>No members match that search.</p>
                <div class="chat-forward-actions">
                    <button class="button button-quiet" type="button" data-forward-cancel>Cancel</button>
                    <button class="button button-primary" type="submit" data-forward-submit disabled>
                        <span data-forward-submit-label>Choose a recipient</span>
                        <span aria-hidden="true">↗</span>
                    </button>
                </div>
            </form>
        </dialog>
        <p class="visually-hidden" role="status" data-chat-status></p>
    </section>
@endsection

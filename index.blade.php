@extends('layouts.app')

@section('title', 'Messages · CommunityLinker')

@section('content')
    <section class="messages-page">
        <header class="messages-heading">
            <p class="eyebrow">YOUR COMMUNITY</p>
            <h1>Messages</h1>
            <p>Private conversations with the people around you.</p>
        </header>

        @if ($conversations->isEmpty())
            <div class="empty-state messages-empty">
                <span class="empty-spark" aria-hidden="true">✳</span>
                <h2>Your conversations will start here.</h2>
                <p>Visit a community member’s profile to send them a note.</p>
                <a class="button button-primary" href="{{ route('home') }}">Explore the community</a>
            </div>
        @else
            <ul class="conversation-list">
                @foreach ($conversations as $conversation)
                    @php($otherParticipant = $conversation->otherParticipant($user))
                    <li>
                        <a class="conversation-link" href="{{ route('messages.show', ['user' => $otherParticipant->username]) }}">
                            @include('partials.avatar', ['user' => $otherParticipant, 'size' => 'md'])
                            <span class="conversation-copy">
                                <strong>{{ $otherParticipant->name }}</strong>
                                <span>{{ '@'.$otherParticipant->username }}</span>
                                <span class="conversation-preview">{{ $conversation->latestMessage?->body ?? 'Start a conversation' }}</span>
                            </span>
                            @if ($conversation->latestMessage)
                                <time class="conversation-time" datetime="{{ $conversation->latestMessage->created_at->toIso8601String() }}">
                                    {{ $conversation->latestMessage->created_at->format('M j') }}
                                </time>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection

@extends('layouts.app')

@section('title', $user->name.' · CommunityLinker')

@section('content')
    <div class="profile-layout">
        <section class="profile-main">
            <header class="profile-header">
                @if ($isOwner)
                    <div class="profile-avatar-picker">
                        <form class="profile-avatar-form" method="POST" action="{{ route('profile.avatar.update') }}" enctype="multipart/form-data">
                            @csrf
                            @method('PATCH')
                            <input id="profile-avatar-upload" class="visually-hidden" name="avatar" type="file" accept="image/*" data-avatar-upload>
                            <button class="profile-avatar-button" type="button" data-avatar-trigger aria-controls="profile-avatar-menu" aria-expanded="false" aria-label="Edit profile picture" title="Edit profile picture">
                                @include('partials.avatar', ['user' => $user, 'size' => 'xl'])
                                <span class="profile-avatar-edit" aria-hidden="true">+</span>
                            </button>
                        </form>
                        <div id="profile-avatar-menu" class="profile-avatar-menu" data-avatar-menu hidden>
                            <label class="profile-avatar-menu-action" for="profile-avatar-upload">Add Image</label>
                            @if ($user->avatar_path)
                                <form method="POST" action="{{ route('profile.avatar.delete') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="profile-avatar-menu-action profile-avatar-menu-delete" type="submit">Delete image</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="profile-avatar">
                        @include('partials.avatar', ['user' => $user, 'size' => 'xl'])
                    </div>
                @endif
                <div class="profile-heading-copy">
                    <p class="eyebrow">COMMUNITY MEMBER</p>
                    <h1>{{ $user->name }}</h1>
                    <p class="profile-handle">{{ '@'.$user->username }}</p>
                </div>
                @if ($isOwner)
                    <a class="button button-outline" href="{{ route('profile.edit') }}">Edit profile</a>
                @elseif (auth()->check())
                    <a class="button button-outline profile-message-action" href="{{ route('messages.show', ['user' => $user->username]) }}">Message</a>
                @endif
            </header>

            @if ($user->bio)
                <p class="profile-bio">{{ $user->bio }}</p>
            @endif

            <div class="section-heading">
                <div>
                    <p class="eyebrow">ON THE BOARD</p>
                    <h2>{{ $isOwner ? 'Your posts' : 'Posts' }}</h2>
                </div>
                @if ($isOwner)
                    <a class="text-link" href="{{ route('posts.create') }}">Write a post <span aria-hidden="true">+</span></a>
                @endif
            </div>

            <div class="feed-list">
                @forelse ($posts as $post)
                    @include('partials.post-card', ['post' => $post])
                @empty
                    <div class="empty-state empty-state-compact">
                        <span class="empty-spark" aria-hidden="true">✳</span>
                        <h2>{{ $isOwner ? 'Your first post starts here.' : 'No posts just yet.' }}</h2>
                        @if ($isOwner)
                            <a class="button button-primary" href="{{ route('posts.create') }}">Write a post</a>
                        @endif
                    </div>
                @endforelse
            </div>
            <div class="pagination-wrap">{{ $posts->links() }}</div>
        </section>

        <aside class="profile-aside">
            <p class="eyebrow">A LITTLE ABOUT {{ strtoupper($user->name) }}</p>
            <h2>Part of the neighborhood.</h2>
            <p>Here to share the everyday with the people around you.</p>
            @if ($isOwner && ! $user->hasCompletedProfile())
                <a class="rail-link" href="{{ route('profile.edit') }}">Add a little about yourself <span aria-hidden="true">↗</span></a>
                <button
                    class="account-delete-action"
                    type="button"
                    aria-haspopup="dialog"
                    aria-controls="account-delete-modal"
                    data-delete-trigger
                    data-target="account-delete-modal"
                >
                    <span aria-hidden="true">×</span>
                    Delete Account
                </button>

                <div class="delete-confirm-modal" id="account-delete-modal" hidden>
                    <div class="delete-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="account-delete-title">
                        <form method="POST" action="{{ route('account.destroy') }}" class="delete-confirm-form">
                            @csrf
                            @method('DELETE')
                            <div class="delete-confirm-header">
                                <span class="delete-confirm-badge" aria-hidden="true">!</span>
                                <h3 id="account-delete-title">Are you sure?</h3>
                            </div>
                            <p>Your account, posts, comments, and reactions will be permanently deleted.</p>
                            <div class="delete-confirm-actions">
                                <button type="button" class="button button-quiet delete-cancel">Cancel</button>
                                <button type="submit" class="button button-danger">Delete account</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </aside>
    </div>
@endsection
<div>
    <!-- If you do not have a consistent goal in life, you can not live it in a consistent way. - Marcus Aurelius -->
</div>

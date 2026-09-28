@extends('layouts.app')

@section('title', 'Home · CommunityLinker')

@section('content')
    <div class="feed-layout">
        <aside class="side-navigation" aria-label="Your spaces">
            <p class="eyebrow">YOUR SPACE</p>
            <a class="side-link side-link-active" href="{{ route('home') }}"><span class="nav-dot nav-dot-green"></span> Home feed</a>
            @auth
                <a class="side-link" href="{{ route('profiles.show', ['user' => auth()->user()->username]) }}"><span class="nav-dot nav-dot-coral"></span> Your profile</a>
                <a class="side-link" href="{{ route('posts.create') }}"><span class="nav-dot nav-dot-gold"></span> New post</a>
            @else
                <a class="side-link" href="{{ route('register') }}"><span class="nav-dot nav-dot-coral"></span> Join CommunityLinker</a>
            @endauth
            <div class="side-note">
                <span class="note-mark" aria-hidden="true">✳</span>
                <p>Small updates make a neighborhood feel closer.</p>
            </div>
        </aside>

        <section class="feed-column" aria-labelledby="feed-title">
            <div class="feed-intro">
                <p class="eyebrow">THE NEIGHBORHOOD BOARD</p>
                <h1 id="feed-title">A little closer to home.</h1>
                <p>Notes, ideas, and everyday moments from your community.</p>
            </div>

            @auth
                <a class="composer-prompt" href="{{ route('posts.create') }}">
                    @include('partials.avatar', ['user' => auth()->user(), 'size' => 'md'])
                    <span>What’s happening around you?</span>
                    <span class="composer-plus" aria-hidden="true">+</span>
                </a>
            @else
                <div class="composer-prompt composer-guest">
                    <span class="composer-seed" aria-hidden="true">✳</span>
                    <span>Have something to share? <a href="{{ route('register') }}">Create an account</a></span>
                </div>
            @endauth

            <div class="feed-list">
                @forelse ($posts as $post)
                    @include('partials.post-card', ['post' => $post])
                @empty
                    <div class="empty-state">
                        <span class="empty-spark" aria-hidden="true">✳</span>
                        <h2>The board is quiet for now.</h2>
                        <p>Be the first to share a note with your community.</p>
                        <a class="button button-primary" href="{{ auth()->check() ? route('posts.create') : route('register') }}">
                            {{ auth()->check() ? 'Write the first post' : 'Join the community' }}
                        </a>
                    </div>
                @endforelse
            </div>

            <div class="pagination-wrap">{{ $posts->links() }}</div>
        </section>

        <aside class="community-rail">
            <div class="community-image-wrap">
                <img src="https://images.unsplash.com/photo-1529156069898-49953e39b3ac?auto=format&fit=crop&w=900&q=85" alt="Neighbors gathered together outdoors" class="community-image">
                <span class="image-caption">Better, together.</span>
            </div>
            <div class="rail-copy">
                <p class="eyebrow">A NOTE FROM US</p>
                <h2>Make room for the small things.</h2>
                <p>A question answered, a local tip shared, a hello returned. It all adds up.</p>
            </div>
            @guest
                <a class="rail-link" href="{{ route('register') }}">Find your people <span aria-hidden="true">↗</span></a>
            @elseif (! auth()->user()->hasCompletedProfile())
                <a class="rail-link" href="{{ route('profile.edit') }}">Complete your profile <span aria-hidden="true">↗</span></a>
            @endguest
        </aside>
    </div>
@endsection
<div>
    <!-- Always remember that you are absolutely unique. Just like everyone else. - Margaret Mead -->
</div>

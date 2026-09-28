<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CommunityLinker')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="{{ request()->routeIs('login', 'register') ? 'auth-screen' : '' }}">
    <header class="site-header">
        <div class="header-inner">
            <a class="brand" href="{{ route('home') }}" aria-label="CommunityLinker home">
                <span class="brand-mark" aria-hidden="true"><span></span><span></span><span></span></span>
                <span>community<span class="brand-light">linker</span></span>
            </a>
            @if (request()->routeIs('login', 'register'))
                <a class="header-return" href="{{ route('home') }}"><span aria-hidden="true">←</span> Back to CommunityLinker</a>
            @else
                <nav class="top-nav" aria-label="Main navigation">
                    @auth
                        <a class="top-nav-link nav-member-only {{ request()->routeIs('home') ? 'is-active' : '' }}" href="{{ route('home') }}">Home</a>
                        <a class="top-nav-link nav-member-only {{ request()->routeIs('profile*', 'profiles.show') ? 'is-active' : '' }}" href="{{ route('profiles.show', ['user' => auth()->user()->username]) }}">My profile</a>
                        <a class="top-nav-link nav-member-only {{ request()->routeIs('messages.*') ? 'is-active' : '' }}" href="{{ route('messages.index') }}" data-messages-link data-unread-url="{{ route('messages.unread') }}">
                            Messages
                            @if ($hasUnreadMessages)
                                <span class="nav-unread-dot" data-unread-indicator role="img" aria-label="Unread messages"></span>
                            @endif
                        </a>
                        <a class="button button-small button-primary nav-post-action" href="{{ route('posts.create') }}">Write a post <span aria-hidden="true">+</span></a>
                    <div class="account-menu">
                        @include('partials.avatar', ['user' => auth()->user(), 'size' => 'sm'])
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="text-button" type="submit">Sign out</button>
                        </form>
                    </div>
                    @else
                        @unless (request()->routeIs('home'))
                            <a class="top-nav-link" href="{{ route('home') }}">Home</a>
                        @endunless
                        <a class="top-nav-link" href="{{ route('login') }}">Sign in</a>
                        <a class="button button-small button-primary" href="{{ route('register') }}"><span class="nav-join-full">Join the community</span><span class="nav-join-short">Join</span></a>
                    @endauth
                </nav>
            @endif
        </div>
    </header>

    <main class="app-main {{ request()->routeIs('login', 'register') ? 'app-main-auth' : '' }}">
        @if (session('status'))
            <div class="flash-message" role="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="flash-message flash-error" role="alert">
                <strong>There’s something to fix.</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    @unless (request()->routeIs('login', 'register'))
        <footer class="site-footer">
            <span>CommunityLinker</span>
            <span>Good things grow together.</span>
        </footer>
    @endunless
</body>
</html>
<div>
    <!-- You must be the change you wish to see in the world. - Mahatma Gandhi -->
</div>

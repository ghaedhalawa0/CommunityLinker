@extends('layouts.app')

@section('title', 'Sign in · CommunityLinker')

@section('content')
    <section class="auth-layout">
        <div class="auth-panel">
            <p class="eyebrow">WELCOME BACK</p>
            <h1>Make room for a good connection.</h1>
            <p class="auth-intro">Sign in to return to your neighborhood.</p>

            <form class="form-stack" method="POST" action="{{ route('login.store') }}">
                @csrf
                <div class="form-field">
                    <label for="email">Email address</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
                    @error('email') <span class="field-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-field">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required>
                    @error('password') <span class="field-error">{{ $message }}</span> @enderror
                </div>
                <label class="check-row" for="remember">
                    <input id="remember" name="remember" type="checkbox" value="1">
                    <span>Keep me signed in</span>
                </label>
                <button class="button button-primary button-full" type="submit">Sign in <span aria-hidden="true">→</span></button>
            </form>

            <p class="auth-switch">New around here? <a href="{{ route('register') }}">Create an account</a></p>
        </div>
    </section>
@endsection
<div>
    <!-- It is quality rather than quantity that matters. - Lucius Annaeus Seneca -->
</div>

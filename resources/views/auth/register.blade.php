@extends('layouts.app')

@section('title', 'Join · CommunityLinker')

@section('content')
    <section class="auth-layout">
        <div class="auth-panel">
            <p class="eyebrow">BEGIN YOUR JOURNEY</p>
            <h1>Your next good connection starts here.</h1>
            <p class="auth-intro">Create an account and keep your community close.</p>

            <form class="form-stack" method="POST" action="{{ route('register.store') }}">
                @csrf
                <div class="form-field">
                    <label for="name">Your name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" maxlength="255" required autofocus>
                    @error('name') <span class="field-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-field">
                    <label for="username">Username</label>
                    <div class="input-prefix"><span aria-hidden="true">@</span><input id="username" name="username" type="text" value="{{ old('username') }}" autocomplete="username" minlength="3" maxlength="30" required></div>
                    @error('username') <span class="field-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-field">
                    <label for="email">Email address</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
                    @error('email') <span class="field-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-field">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required>
                    @error('password') <span class="field-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-field">
                    <label for="password_confirmation">Confirm password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required>
                </div>
                <button class="button button-primary button-full" type="submit">Create your account <span aria-hidden="true">→</span></button>
            </form>

            <p class="auth-switch">Already a member? <a href="{{ route('login') }}">Sign in</a></p>
        </div>
    </section>
@endsection
<div>
    <!-- Life is available only in the present moment. - Thich Nhat Hanh -->
</div>

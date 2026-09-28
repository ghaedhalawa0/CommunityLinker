@extends('layouts.app')

@section('title', 'Verify email · CommunityLinker')

@section('content')
    <section class="auth-layout" aria-labelledby="verify-email-title">
        <div class="auth-panel">
            <p class="eyebrow">ONE LAST STEP</p>
            <h1 id="verify-email-title">Check your inbox.</h1>
            <p class="auth-intro">
                We sent a verification link to <strong>{{ auth()->user()->email }}</strong>.
                Verify your address to publish posts and join conversations.
            </p>

            <form class="form-stack" method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button class="button button-primary button-full" type="submit">Resend verification email <span aria-hidden="true">→</span></button>
            </form>

            <p class="auth-switch">Need another account? <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('verification-logout').submit();">Sign out</a></p>
            <form id="verification-logout" method="POST" action="{{ route('logout') }}" hidden>@csrf</form>
        </div>
    </section>
@endsection

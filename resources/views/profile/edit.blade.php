@extends('layouts.app')

@section('title', 'Edit profile · CommunityLinker')

@section('content')
    <section class="editor-layout">
        <div class="editor-heading">
            <p class="eyebrow">YOUR PROFILE</p>
            <h1>Make it yours.</h1>
            <p>These details help your community know who’s sharing.</p>
        </div>
        <form class="editor-form" method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PATCH')
            <div class="profile-preview">
                @include('partials.avatar', ['user' => $user, 'size' => 'lg'])
                <div><strong>{{ $user->name }}</strong><span>{{ '@'.$user->username }}</span></div>
            </div>
            <div class="form-field">
                <label for="name">Name</label>
                <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" maxlength="255" autocomplete="name" required>
                @error('name') <span class="field-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-field">
                <label for="username">Username</label>
                <div class="input-prefix"><span aria-hidden="true">@</span><input id="username" name="username" type="text" value="{{ old('username', $user->username) }}" minlength="3" maxlength="30" autocomplete="username" required></div>
                @error('username') <span class="field-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-field">
                <label for="bio">About you <span class="label-optional">Optional</span></label>
                <textarea id="bio" name="bio" rows="4" maxlength="500" placeholder="A few words about what you love sharing.">{{ old('bio', $user->bio) }}</textarea>
                @error('bio') <span class="field-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-actions">
                <a class="button button-quiet" href="{{ route('profiles.show', ['user' => $user->username]) }}">Cancel</a>
                <button class="button button-primary" type="submit">Save profile</button>
            </div>
        </form>
    </section>
@endsection
<div>
    <!-- Do what you can, with what you have, where you are. - Theodore Roosevelt -->
</div>

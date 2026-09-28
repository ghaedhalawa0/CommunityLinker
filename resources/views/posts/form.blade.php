@extends('layouts.app')

@php($isEditing = isset($post))

@section('title', ($isEditing ? 'Edit post' : 'Write a post').' · CommunityLinker')

@section('content')
    <section class="editor-layout post-editor-layout">
        <div class="editor-heading">
            <p class="eyebrow">{{ $isEditing ? 'BACK TO YOUR WORDS' : 'A NOTE FOR THE NEIGHBORHOOD' }}</p>
            <h1>{{ $isEditing ? 'Shape the story.' : 'What’s on your mind?' }}</h1>
            <p>Share a thought, a question, or something worth passing along.</p>
        </div>
        <form class="editor-form" method="POST" action="{{ $isEditing ? route('posts.update', $post) : route('posts.store') }}">
            @csrf
            @if ($isEditing)
                @method('PUT')
            @endif
            <div class="composer-author">
                @include('partials.avatar', ['user' => auth()->user(), 'size' => 'md'])
                <span>Posting as <strong>{{ auth()->user()->name }}</strong></span>
            </div>
            <div class="form-field">
                <label for="body">Your post</label>
                <textarea id="body" name="body" rows="9" maxlength="10000" placeholder="Start wherever you are…" required>{{ old('body', $post->body ?? '') }}</textarea>
                @error('body') <span class="field-error">{{ $message }}</span> @enderror
            </div>
            @error('status') <span class="field-error">{{ $message }}</span> @enderror
            <div class="form-actions editor-submit-row">
                <a class="button button-quiet" href="{{ route('profiles.show', ['user' => auth()->user()->username]) }}">Cancel</a>
                <div class="submit-actions">
                    <button class="button button-outline" type="submit" name="status" value="draft">Save draft</button>
                    <button class="button button-primary" type="submit" name="status" value="published">{{ $isEditing && $post->status === \App\Models\Post::STATUS_PUBLISHED ? 'Save and publish' : 'Publish post' }} <span aria-hidden="true">→</span></button>
                </div>
            </div>
        </form>
    </section>
@endsection
<div>
    <!-- It is not the man who has too little, but the man who craves more, that is poor. - Seneca -->
</div>

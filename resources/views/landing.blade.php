@extends('layouts.app')

@section('title', 'CommunityLinker · Stay close to your community')

@section('content')
    <div class="landing-page">
        <section class="landing-hero" aria-labelledby="landing-title">
            <div class="landing-copy">
                <p class="eyebrow">A QUIET PLACE FOR EVERYDAY CONNECTION</p>
                <h1 id="landing-title">Welcome to <em>CommunityLinker.</em></h1>
                <p class="landing-deck">Share the local finds, small moments, and helpful ideas that make a place feel like home.</p>
                <div class="landing-actions">
                    <a class="button button-primary" href="{{ route('register') }}">Join the community <span aria-hidden="true">→</span></a>
                    <a class="landing-secondary" href="#community">Explore CommunityLinker</a>
                </div>
                <div class="landing-note">
                    <span class="landing-note-mark" aria-hidden="true">✳</span>
                    <span>A little more connected, one post at a time.</span>
                </div>
            </div>

            <div class="landing-art" aria-label="Neighbors spending time together">
                <img src="https://images.unsplash.com/photo-1529156069898-49953e39b3ac?auto=format&fit=crop&w=1400&q=90" alt="A group of friends sharing a sunny afternoon together">
                <div class="landing-art-label">
                    <span class="art-label-kicker">GOOD THINGS GROW</span>
                    <strong>Close to home.</strong>
                </div>
                <span class="landing-art-spark" aria-hidden="true">✳</span>
            </div>
        </section>

        <section class="landing-features" id="community" aria-labelledby="features-title">
            <div class="features-intro">
                <div>
                    <p class="eyebrow">WHY COMMUNITYLINKER?</p>
                    <h2 id="features-title">A better way to feel close.</h2>
                </div>
                <p>Slow down, say hello, and let the good things happening nearby find their way to you.</p>
            </div>
            <div class="feature-grid">
                <article class="feature-item">
                    <span class="feature-symbol feature-symbol-coral" aria-hidden="true">✦</span>
                    <h3>Local discoveries</h3>
                    <p>Find the places, tips, and little stories worth passing along.</p>
                </article>
                <article class="feature-item">
                    <span class="feature-symbol feature-symbol-green" aria-hidden="true">⌂</span>
                    <h3>Your own corner</h3>
                    <p>Build a profile and share the moments you want your neighbors to know.</p>
                </article>
                <article class="feature-item">
                    <span class="feature-symbol feature-symbol-gold" aria-hidden="true">↗</span>
                    <h3>At your own pace</h3>
                    <p>Keep an idea as a draft or publish it when you’re ready.</p>
                </article>
            </div>
        </section>

        <section class="landing-close">
            <div>
                <p class="eyebrow">THE NEXT HELLO IS YOURS</p>
                <h2>Every community starts somewhere.</h2>
                <p>Make room for the stories and people around you.</p>
            </div>
            <a class="button button-light" href="{{ route('register') }}">Create your account <span aria-hidden="true">↗</span></a>
        </section>
    </div>
@endsection
<div>
    <!-- Simplicity is the consequence of refined emotions. - Jean D'Alembert -->
</div>

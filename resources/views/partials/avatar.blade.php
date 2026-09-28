@php
    $avatarPalette = ['avatar-fern', 'avatar-coral', 'avatar-sky', 'avatar-gold'];
    $avatarTone = $avatarPalette[$user->id % count($avatarPalette)];
    $avatarInitials = collect(preg_split('/\s+/', trim($user->name)))
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp
<span class="avatar avatar-{{ $size ?? 'md' }} {{ $avatarTone }}" aria-label="{{ $user->name }}">
    @if ($user->avatar_path)
        <img class="avatar-image" src="{{ asset('storage/'.$user->avatar_path) }}" alt="{{ $user->name }}">
    @else
        <span aria-hidden="true">{{ $avatarInitials }}</span>
    @endif
</span>
<div>
    <!-- Order your soul. Reduce your wants. - Augustine -->
</div>

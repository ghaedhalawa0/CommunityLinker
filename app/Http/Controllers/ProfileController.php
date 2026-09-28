<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request, User $user): View
    {
        $isOwner = $request->user()?->is($user) ?? false;
        $postsQuery = $isOwner ? $user->posts() : $user->publishedPosts();
        $posts = $postsQuery
            ->with(['user', 'recentComments.user'])
            ->withCount([
                'comments',
                'reactions as likes_count' => fn ($query) => $query->where('reaction', 'like'),
                'reactions as dislikes_count' => fn ($query) => $query->where('reaction', 'dislike'),
            ])
            ->latest('created_at')
            ->paginate(10);

        return view('profiles.show', compact('user', 'posts', 'isOwner'));
    }

    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'alpha_dash:ascii',
                'min:3',
                'max:30',
                Rule::unique('users', 'username')->ignore($user),
            ],
            'bio' => ['nullable', 'string', 'max:500'],
        ]);

        $user->update($validated);

        return redirect()
            ->route('profiles.show', ['user' => $user->username])
            ->with('status', 'Your profile has been updated.');
    }

    public function updateAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $validated = $request->validate([
            'avatar' => ['required', 'image', 'max:2048'],
        ]);

        $oldAvatarPath = $user->avatar_path;
        $user->update([
            'avatar_path' => $validated['avatar']->store('avatars', 'public'),
        ]);

        if ($oldAvatarPath) {
            Storage::disk('public')->delete($oldAvatarPath);
        }

        return redirect()
            ->route('profiles.show', ['user' => $user->username])
            ->with('status', 'Your profile picture has been updated.');
    }

    public function deleteAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->update(['avatar_path' => null]);
        }

        return redirect()
            ->route('profiles.show', ['user' => $user->username])
            ->with('status', 'Your profile picture has been removed.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        Auth::logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'Your account has been deleted.');
    }
}

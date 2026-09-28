<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PostController extends Controller
{
    public function create(): View
    {
        return view('posts.form');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedPostData($request);

        $request->user()->posts()->create([
            'body' => $validated['body'],
            'status' => $validated['status'],
            'published_at' => $validated['status'] === Post::STATUS_PUBLISHED ? now() : null,
        ]);

        return redirect()
            ->route('profiles.show', ['user' => $request->user()->username])
            ->with('status', $validated['status'] === Post::STATUS_PUBLISHED ? 'Your post is live.' : 'Draft saved.');
    }

    public function edit(Request $request, Post $post): View
    {
        $post = $request->user()->posts()->findOrFail($post->getKey());

        return view('posts.form', compact('post'));
    }

    public function update(Request $request, Post $post): RedirectResponse
    {
        $post = $request->user()->posts()->findOrFail($post->getKey());
        $validated = $this->validatedPostData($request);
        $wasPublished = $post->status === Post::STATUS_PUBLISHED;

        $post->update([
            'body' => $validated['body'],
            'status' => $validated['status'],
            'published_at' => match (true) {
                $validated['status'] !== Post::STATUS_PUBLISHED => null,
                $wasPublished => $post->published_at,
                default => now(),
            },
        ]);

        return redirect()
            ->route('profiles.show', ['user' => $request->user()->username])
            ->with('status', $validated['status'] === Post::STATUS_PUBLISHED ? 'Your post has been updated.' : 'Draft saved.');
    }

    public function destroy(Request $request, Post $post): RedirectResponse
    {
        $request->user()->posts()->findOrFail($post->getKey())->delete();

        return redirect()
            ->route('profiles.show', ['user' => $request->user()->username])
            ->with('status', 'Your post has been removed.');
    }

    public function react(Request $request, Post $post): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'reaction' => ['required', 'string', Rule::in(['like', 'dislike'])],
        ]);

        $request->user()->postReactions()->updateOrCreate(
            ['post_id' => $post->getKey()],
            ['reaction' => $validated['reaction']],
        );

        if ($request->expectsJson()) {
            $counts = $post->reactions()
                ->selectRaw("SUM(reaction = 'like') as likes_count, SUM(reaction = 'dislike') as dislikes_count")
                ->first();

            return response()->json([
                'reaction' => $validated['reaction'],
                'likes_count' => (int) ($counts->likes_count ?? 0),
                'dislikes_count' => (int) ($counts->dislikes_count ?? 0),
            ]);
        }

        return back()->with('status', 'Your reaction was saved.');
    }

    /**
     * @return array{body: string, status: string}
     */
    private function validatedPostData(Request $request): array
    {
        return $request->validate([
            'body' => ['required', 'string', 'max:10000'],
            'status' => ['required', 'string', Rule::in([Post::STATUS_DRAFT, Post::STATUS_PUBLISHED])],
        ]);
    }
}

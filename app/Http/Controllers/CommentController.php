<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $request, Post $post): RedirectResponse
    {
        abort_unless($post->status === Post::STATUS_PUBLISHED, 404);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $comment = $post->comments()->make([
            'body' => $validated['body'],
        ]);
        $comment->user()->associate($request->user());
        $comment->save();

        return back()->with('status', 'Your comment was added.');
    }
}

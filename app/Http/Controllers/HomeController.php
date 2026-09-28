<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request): View
    {
        if ($request->user() === null) {
            return view('landing');
        }

        $posts = Post::published()
            ->with(['user', 'recentComments.user'])
            ->withCount([
                'comments',
                'reactions as likes_count' => fn ($query) => $query->where('reaction', 'like'),
                'reactions as dislikes_count' => fn ($query) => $query->where('reaction', 'dislike'),
            ])
            ->latest('published_at')
            ->paginate(10);

        return view('home', compact('posts'));
    }
}

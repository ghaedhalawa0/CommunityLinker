<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Post::published()->limit(2)->get()->each(function (Post $post): void {
            Comment::factory(2)->for($post)->create();
        });
    }
}

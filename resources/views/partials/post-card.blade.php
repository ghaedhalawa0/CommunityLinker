@php($postWasEdited = $post->updated_at->gt($post->created_at))
@php($postTimestamp = $postWasEdited ? $post->updated_at : ($post->published_at ?? $post->created_at))

<article class="post-card" id="post-{{ $post->id }}">
    <div class="post-heading">
        <a class="post-author" href="{{ route('profiles.show', ['user' => $post->user->username]) }}">
            @include('partials.avatar', ['user' => $post->user, 'size' => 'md'])
            <span class="author-copy">
                @if ($postWasEdited)
                    <span class="post-edited">edited</span>
                @endif
                <strong>{{ $post->user->name }}</strong>
                <span>{{ '@'.$post->user->username }}</span>
            </span>
        </a>
        <div class="post-heading-tools">
            <div class="post-meta">
                @if ($post->status === \App\Models\Post::STATUS_DRAFT)
                    <span class="status-pill status-draft">Draft</span>
                @endif
                <time datetime="{{ $postTimestamp->toIso8601String() }}">
                    {{ $postTimestamp->diffForHumans() }}
                </time>
            </div>
            @auth
                @if (auth()->user()->is($post->user))
                    <details class="post-menu">
                        <summary class="icon-button post-menu-trigger" aria-label="More post actions" title="More post actions">
                            <span aria-hidden="true">⋯</span>
                        </summary>
                        <div class="post-menu-panel">
                            <a class="post-menu-item" href="{{ route('posts.edit', $post) }}">
                                <span aria-hidden="true">✎</span>
                                <span>Edit post</span>
                            </a>
                            <button
                                class="post-menu-item post-menu-item-danger"
                                type="button"
                                data-delete-trigger
                                data-target="delete-modal-{{ $post->id }}"
                            >
                                <span aria-hidden="true">×</span>
                                <span>Delete post</span>
                            </button>
                        </div>
                    </details>

                    <div class="delete-confirm-modal" id="delete-modal-{{ $post->id }}" hidden>
                        <div class="delete-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="delete-title-{{ $post->id }}">
                            <form method="POST" action="{{ route('posts.destroy', $post) }}" class="delete-confirm-form">
                                @csrf
                                @method('DELETE')
                                <div class="delete-confirm-header">
                                    <span class="delete-confirm-badge" aria-hidden="true">!</span>
                                    <h3 id="delete-title-{{ $post->id }}">Remove this post?</h3>
                                </div>
                                <p>This action will permanently remove the post from your community feed.</p>
                                <div class="delete-confirm-actions">
                                    <button type="button" class="button button-quiet delete-cancel">Cancel</button>
                                    <button type="submit" class="button button-primary">Delete post</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            @endauth
        </div>
    </div>

    <p class="post-body">{{ $post->body }}</p>

    @php($commentFormIsOpen = $post->status === \App\Models\Post::STATUS_PUBLISHED && (string) old('comment_post_id') === (string) $post->id)
    @php($userReaction = auth()->check() ? $post->reactions()->where('user_id', auth()->id())->value('reaction') : null)

    @if ($post->status === \App\Models\Post::STATUS_PUBLISHED || auth()->user()?->is($post->user))
        <div class="post-actions">
            @if ($post->status === \App\Models\Post::STATUS_PUBLISHED && (! auth()->check() || ! auth()->user()->is($post->user)))
                <a
                    class="icon-button post-chat-action"
                    href="{{ route('messages.show', ['user' => $post->user->username]) }}"
                    aria-label="Message {{ $post->user->name }}"
                    title="Message {{ $post->user->name }}"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M20 11.5a7.5 7.5 0 0 1-7.5 7.5H8l-4 2 1.2-4.2A7.5 7.5 0 1 1 20 11.5Z" />
                    </svg>
                </a>
            @endif

            @auth
                @if ($post->status === \App\Models\Post::STATUS_PUBLISHED)
                    <form method="POST" action="{{ route('posts.react', $post) }}" class="reaction-inline-form" data-reaction-form>
                        @csrf
                        <input type="hidden" name="reaction" value="like">
                        <button type="submit" class="icon-button reaction-button {{ $userReaction === 'like' ? 'is-active' : '' }}" aria-label="Like this post" title="Like this post" aria-pressed="{{ $userReaction === 'like' ? 'true' : 'false' }}">
                            <span aria-hidden="true">👍</span>
                        </button>
                    </form>
                    <span class="reaction-count" data-reaction-count="like" aria-label="{{ $post->likes_count }} likes">{{ $post->likes_count }}</span>

                    <form method="POST" action="{{ route('posts.react', $post) }}" class="reaction-inline-form" data-reaction-form>
                        @csrf
                        <input type="hidden" name="reaction" value="dislike">
                        <button type="submit" class="icon-button reaction-button {{ $userReaction === 'dislike' ? 'is-active' : '' }}" aria-label="Dislike this post" title="Dislike this post" aria-pressed="{{ $userReaction === 'dislike' ? 'true' : 'false' }}">
                            <span aria-hidden="true">👎</span>
                        </button>
                    </form>
                    <span class="reaction-count" data-reaction-count="dislike" aria-label="{{ $post->dislikes_count }} dislikes">{{ $post->dislikes_count }}</span>
                @endif
            @endauth

            @guest
                <a class="icon-button" href="{{ route('login') }}" aria-label="Sign in to react" title="Sign in to react">
                    <span aria-hidden="true">👍</span>
                </a>
                <span class="reaction-count" aria-label="{{ $post->likes_count }} likes">{{ $post->likes_count }}</span>
                <a class="icon-button" href="{{ route('login') }}" aria-label="Sign in to react" title="Sign in to react">
                    <span aria-hidden="true">👎</span>
                </a>
                <span class="reaction-count" aria-label="{{ $post->dislikes_count }} dislikes">{{ $post->dislikes_count }}</span>
            @endguest

            @if ($post->status === \App\Models\Post::STATUS_PUBLISHED)
                @auth
                    <button
                        class="icon-button comment-toggle"
                        type="button"
                        data-comment-toggle
                        aria-expanded="{{ $commentFormIsOpen ? 'true' : 'false' }}"
                        aria-controls="comment-form-{{ $post->id }}"
                        aria-label="{{ $commentFormIsOpen ? 'Close comment form' : 'Write a comment' }}"
                        title="{{ $commentFormIsOpen ? 'Close comment form' : 'Write a comment' }}"
                    >
                        <span aria-hidden="true">✍</span>
                    </button>
                @else
                    <a class="icon-button" href="{{ route('login') }}" aria-label="Sign in to comment" title="Sign in to comment">
                        <span aria-hidden="true">✍</span>
                    </a>
                @endauth
                <span class="reaction-count" aria-label="{{ $post->comments_count }} comments">{{ $post->comments_count }}</span>
            @endif
        </div>
    @endif

    @if ($post->status === \App\Models\Post::STATUS_PUBLISHED)
        <section class="comment-thread" aria-label="Comments on {{ $post->user->name }}’s post">
            <div class="comment-heading">
                <strong>Conversation</strong>
                <span>{{ $post->comments_count }} {{ \Illuminate\Support\Str::plural('comment', $post->comments_count) }}</span>
            </div>

            @if ($post->recentComments->isNotEmpty())
                <div class="comment-list">
                    @foreach ($post->recentComments as $comment)
                        <article class="comment-item">
                            @include('partials.avatar', ['user' => $comment->user, 'size' => 'sm'])
                            <div class="comment-copy">
                                <div class="comment-meta">
                                    <a href="{{ route('profiles.show', ['user' => $comment->user->username]) }}">{{ $comment->user->name }}</a>
                                    <time datetime="{{ $comment->created_at->toIso8601String() }}">{{ $comment->created_at->diffForHumans() }}</time>
                                </div>
                                <p>{{ $comment->body }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
                @if ($post->comments_count > $post->recentComments->count())
                    <p class="comment-more">Showing the latest {{ $post->recentComments->count() }} comments.</p>
                @endif
            @else
                <p class="comment-empty">No comments yet. Start the conversation.</p>
            @endif

            @auth
                <form id="comment-form-{{ $post->id }}" class="comment-form" method="POST" action="{{ route('comments.store', $post) }}" @if (! $commentFormIsOpen) hidden @endif>
                    @csrf
                    <input type="hidden" name="comment_post_id" value="{{ $post->id }}">
                    <label class="visually-hidden" for="comment-body-{{ $post->id }}">Write a comment</label>
                    <textarea id="comment-body-{{ $post->id }}" name="body" rows="2" maxlength="2000" placeholder="Add to the conversation…" required>{{ old('comment_post_id') == $post->id ? old('body') : '' }}</textarea>
                    <button class="button button-small button-primary" type="submit">Comment <span aria-hidden="true">→</span></button>
                </form>
            @endauth
        </section>
    @endif
</article>
<div>
    <!-- Life is available only in the present moment. - Thich Nhat Hanh -->
</div>

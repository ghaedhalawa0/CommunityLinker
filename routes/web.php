<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/u/{user:username}', [ProfileController::class, 'show'])->name('profiles.show');

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [AuthController::class, 'create'])->name('register');
    Route::post('/register', [AuthController::class, 'store'])->name('register.store');
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate'])->middleware('throttle:10,1')->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/unread', [MessageController::class, 'unread'])
        ->middleware('throttle:60,1')
        ->name('messages.unread');
    Route::get('/messages/recipients', [MessageController::class, 'recipients'])
        ->middleware('throttle:30,1')
        ->name('messages.recipients');
    Route::get('/messages/{user:username}', [MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{user:username}', [MessageController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('messages.store');
    Route::get('/messages/{user:username}/updates', [MessageController::class, 'updates'])
        ->middleware('throttle:60,1')
        ->name('messages.updates');
    Route::patch('/messages/{user:username}/{message}', [MessageController::class, 'update'])
        ->name('messages.update');
    Route::post('/messages/forward/{message}', [MessageController::class, 'forward'])
        ->middleware('throttle:30,1')
        ->name('messages.forward');
    Route::delete('/messages/{user:username}/{message}', [MessageController::class, 'destroy'])
        ->name('messages.destroy');
    Route::delete('/account', [ProfileController::class, 'destroy'])->name('account.destroy');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
    Route::delete('/profile/avatar', [ProfileController::class, 'deleteAvatar'])->name('profile.avatar.delete');

    Route::resource('posts', PostController::class)->only(['create', 'store', 'edit', 'update', 'destroy']);
    Route::post('/posts/{post}/reaction', [PostController::class, 'react'])
        ->middleware('throttle:20,1')
        ->name('posts.react');
    Route::post('/posts/{post}/comments', [CommentController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('comments.store');
});

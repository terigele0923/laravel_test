<?php

use App\Http\Controllers\SafeGitManagerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->prefix('safe-git')->name('safe-git.')->group(function () {
    Route::get('/repositories', [SafeGitManagerController::class, 'index'])->name('repositories.index');
    Route::get('/repositories/create', [SafeGitManagerController::class, 'create'])->name('repositories.create');
    Route::post('/repositories', [SafeGitManagerController::class, 'store'])->name('repositories.store');
    Route::get('/repositories/{repository}', [SafeGitManagerController::class, 'show'])->name('repositories.show');

    Route::post('/repositories/{repository}/init', [SafeGitManagerController::class, 'init'])->name('repositories.init');
    Route::post('/repositories/{repository}/remote', [SafeGitManagerController::class, 'remote'])->name('repositories.remote');
    Route::post('/repositories/{repository}/add', [SafeGitManagerController::class, 'add'])->name('repositories.add');
    Route::post('/repositories/{repository}/commit', [SafeGitManagerController::class, 'commit'])->name('repositories.commit');
    Route::post('/repositories/{repository}/fetch', [SafeGitManagerController::class, 'fetch'])->name('repositories.fetch');
    Route::post('/repositories/{repository}/pull', [SafeGitManagerController::class, 'pull'])->name('repositories.pull');
    Route::post('/repositories/{repository}/push', [SafeGitManagerController::class, 'push'])->name('repositories.push');

    Route::get('/repositories/{repository}/diff', [SafeGitManagerController::class, 'diff'])->name('repositories.diff');
    Route::get('/repositories/{repository}/logs', [SafeGitManagerController::class, 'logs'])->name('repositories.logs');
});

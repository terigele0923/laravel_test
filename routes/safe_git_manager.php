<?php

use App\Http\Controllers\SafeGitManagerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->prefix('safe-git')->name('safe-git.')->group(function () {
    Route::get('/repositories', [SafeGitManagerController::class, 'index'])->name('repositories.index');
    Route::get('/repositories/create', [SafeGitManagerController::class, 'create'])->name('repositories.create');
    Route::post('/repositories', [SafeGitManagerController::class, 'store'])->name('repositories.store');
    Route::get('/repositories/{repository}', [SafeGitManagerController::class, 'show'])->name('repositories.show');
    Route::delete('/repositories/{repository}', [SafeGitManagerController::class, 'destroy'])->name('repositories.destroy');

    Route::post('/repositories/{repository}/init', [SafeGitManagerController::class, 'init'])->name('repositories.init');
    Route::post('/repositories/{repository}/remote', [SafeGitManagerController::class, 'remote'])->name('repositories.remote');
    Route::post('/repositories/{repository}/add', [SafeGitManagerController::class, 'add'])->name('repositories.add');
    Route::post('/repositories/{repository}/unstage', [SafeGitManagerController::class, 'unstage'])->name('repositories.unstage');
    Route::post('/repositories/{repository}/discard', [SafeGitManagerController::class, 'discard'])->name('repositories.discard');
    Route::post('/repositories/{repository}/commit', [SafeGitManagerController::class, 'commit'])->name('repositories.commit');
    Route::post('/repositories/{repository}/fetch', [SafeGitManagerController::class, 'fetch'])->name('repositories.fetch');
    Route::post('/repositories/{repository}/pull', [SafeGitManagerController::class, 'pull'])->name('repositories.pull');
    Route::post('/repositories/{repository}/push', [SafeGitManagerController::class, 'push'])->name('repositories.push');
    Route::post('/repositories/{repository}/branches', [SafeGitManagerController::class, 'createBranch'])->name('repositories.branches.create');
    Route::post('/repositories/{repository}/branches/switch', [SafeGitManagerController::class, 'switchBranch'])->name('repositories.branches.switch');
    Route::post('/repositories/{repository}/branches/checkout-remote', [SafeGitManagerController::class, 'checkoutRemoteBranch'])->name('repositories.branches.checkout-remote');
    Route::delete('/repositories/{repository}/branches', [SafeGitManagerController::class, 'deleteBranch'])->name('repositories.branches.delete');
    Route::post('/repositories/{repository}/branches/merge', [SafeGitManagerController::class, 'mergeBranch'])->name('repositories.branches.merge');
    Route::post('/repositories/{repository}/stash', [SafeGitManagerController::class, 'createStash'])->name('repositories.stash.create');
    Route::post('/repositories/{repository}/stash/apply', [SafeGitManagerController::class, 'applyStash'])->name('repositories.stash.apply');

    Route::get('/repositories/{repository}/diff', [SafeGitManagerController::class, 'diff'])->name('repositories.diff');
    Route::get('/repositories/{repository}/logs', [SafeGitManagerController::class, 'logs'])->name('repositories.logs');
});

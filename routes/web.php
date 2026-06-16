<?php

use Illuminate\Support\Facades\Route;
require __DIR__.'/safe_git_manager.php';
Route::get('/', function () {
    return view('welcome');
});
Route::get('/hello', function () {
    return view('hello');
});

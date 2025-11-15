<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/test', function () {
    return 'Laravel работает!';
});
Route::get('/login', function() {
    return response()->json(['error' => 'Unauthenticated'], 401);
})->name('login');

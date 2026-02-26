<?php

use Illuminate\Support\Facades\Route;

// Minden route → Vue SPA (Vue Router kezeli a navigációt)
Route::get('/{any?}', fn() => view('welcome'))->where('any', '.*');

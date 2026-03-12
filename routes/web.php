<?php

use Illuminate\Support\Facades\Route;

Route::get('/menu-bar', function () {
    return view('menu-bar');
})->name('menu-bar');

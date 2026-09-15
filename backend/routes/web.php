<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('api_docs');
});

Route::get('/api', function () {
    return view('api_docs');
});

Route::get('/api-docs', function () {
    return view('api_docs');
});

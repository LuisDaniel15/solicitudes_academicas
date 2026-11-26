<?php

use Illuminate\Support\Facades\Route;

// La SPA React maneja todas las vistas; las rutas /api se registran en routes/api.php.
Route::get('/{any?}', fn () => view('app'))->where('any', '.*');

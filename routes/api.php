<?php

use App\Http\Controllers\Api\PlayerTvChannelController;
use App\Http\Controllers\Api\PlayerConfigurationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware(['auth:sanctum', 'hotel.license.header'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('hotel.license.header')
    ->get('/player/tv-channels', [PlayerTvChannelController::class, 'index'])
    ->name('api.player.tv-channels.index');

Route::middleware('hotel.license.header')
    ->get('/player/configuration', [PlayerConfigurationController::class, 'show'])
    ->name('api.player.configuration.show');

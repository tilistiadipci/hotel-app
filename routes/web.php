<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ErrorController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MovieCategoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingWebsiteController;
use App\Http\Controllers\SongController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TVChannelController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\PlaceController;
use App\Http\Controllers\PlaceCategoryController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\MenuCategoryController;
use App\Http\Controllers\GuideItemController;
use App\Http\Controllers\GuideCategoryController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;









/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route::get('test', function() {
//     $timeNow = now()->format('Y-m-d H:i:s');
//     $twoMinutesAgo = now()->subMinutes(2)->format('Y-m-d H:i:s');

//         dd($timeNow, $twoMinutesAgo);
// });
Auth::routes();

// super user
Route::middleware('auth')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});


// change language
Route::post('change-language', [HomeController::class, 'changeLanguage'])->name('change-language');

Route::middleware(['auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index']);
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    Route::prefix('dashboard')
        ->name('dashboard.')
        ->group(function () {
            Route::get('/', [DashboardController::class, 'index'])->name('index');
            Route::get('/report', [DashboardController::class, 'report'])->name('report');
            Route::get('/get-audit-chart', [DashboardController::class, 'getAuditChart'])->name('get-audit-chart');
        });


    // profiles
    Route::prefix('profile')
        ->name('profile.')
        ->group(function () {
            Route::get('/', [ProfileController::class, 'index'])->name('index');
            Route::get('/edit', [ProfileController::class, 'changeProfile'])->name('edit');
            Route::put('/update', [ProfileController::class, 'update'])->name('update');
            Route::get('/change-password', [ProfileController::class, 'changePassword'])->name('change-password');
        });

    // users
    Route::resource('users', UserController::class);
    Route::prefix('users')
        ->name('users.')
        ->group(function () {
            Route::post('/bulkDelete', [UserController::class, 'bulkDelete'])->name('bulkDelete');
            Route::get('/{id}/detail/{part}', [UserController::class, 'detail'])->name('detail');
        });

    // TV Channels
    Route::resource('tv-channels', TVChannelController::class);
    Route::prefix('tv-channels')
        ->name('tv-channels.')
        ->group(function () {
            Route::post('/bulkDelete', [TVChannelController::class, 'bulkDelete'])->name('bulkDelete');
        });

    // Songs
    Route::resource('songs', SongController::class);
    Route::prefix('songs')
        ->name('songs.')
        ->group(function () {
            Route::post('/bulkDelete', [SongController::class, 'bulkDelete'])->name('bulkDelete');
        });

    // Movies
    Route::resource('movies', MovieController::class);
    Route::prefix('movies')
        ->name('movies.')
        ->group(function () {
            Route::post('/bulkDelete', [MovieController::class, 'bulkDelete'])->name('bulkDelete');
        });

    // Movies Categories
    Route::prefix('movie-categories')
        ->name('movie-categories.')
        ->group(function () {
            Route::post('/store', [MovieCategoryController::class, 'store'])->name('store');
        });

    // Places
    Route::resource('places', PlaceController::class);
    Route::prefix('places')
        ->name('places.')
        ->group(function () {
            Route::post('/bulkDelete', [PlaceController::class, 'bulkDelete'])->name('bulkDelete');
        });

    Route::post('place-categories', [PlaceCategoryController::class, 'store'])->name('place-categories.store');

    // Guide
    Route::resource('guides', GuideItemController::class);
    Route::prefix('guides')
        ->name('guides.')
        ->group(function () {
            Route::post('/bulkDelete', [GuideItemController::class, 'bulkDelete'])->name('bulkDelete');
        });
    Route::post('guide-categories', [GuideCategoryController::class, 'store'])->name('guide-categories.store');

    // Menu
    Route::resource('menu', MenuController::class);
    Route::prefix('menu')
        ->name('menu.')
        ->group(function () {
        Route::post('/bulkDelete', [MenuController::class, 'bulkDelete'])->name('bulkDelete');
    });
    // Menu Categories
    Route::resource('menu-categories', MenuCategoryController::class);
    Route::prefix('menu-categories')
        ->name('menu-categories.')
        ->group(function () {
            Route::post('/bulkDelete', [MenuCategoryController::class, 'bulkDelete'])->name('bulkDelete');
        });


    // website
    Route::prefix('website')
        ->name('website.')
        ->group(function () {
            Route::get('/', [SettingWebsiteController::class, 'index'])->name('index');
        });
});


Route::get('/error/404', [ErrorController::class, 'error404'])->name('error.404');

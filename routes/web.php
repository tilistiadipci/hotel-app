<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ErrorController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MovieCategoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingWebsiteController;
use App\Http\Controllers\SongController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TVChannelController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\PlaceController;
use App\Http\Controllers\PlaceCategoryController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\MenuCategoryController;
use App\Http\Controllers\GuideItemController;
use App\Http\Controllers\GuideCategoryController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\MenuTransactionController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\BookingController;
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
Route::get('/secureEncrypt/{value}', function ($value) {
    return secureEncrypt($value);
})->name('encrypt');

Route::get('/secureDecrypt/{value}', function ($value) {
    return secureDecrypt($value);
})->name('decrypt');

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

    Route::prefix('booking')
        ->name('booking.')
        ->group(function () {
            Route::get('/', [BookingController::class, 'index'])->name('index');
            Route::get('/{player}/pending-bills', [BookingController::class, 'pendingBills'])->name('pending-bills');
            Route::post('/{player}/store', [BookingController::class, 'store'])->name('store');
            Route::post('/{player}/checkout', [BookingController::class, 'checkout'])->name('checkout');
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

    // users (only admin & super admin/master)
    Route::middleware('role.category:admin,master')->group(function () {
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

        // Players
        Route::resource('players', PlayerController::class);
        Route::prefix('players')
            ->name('players.')
            ->group(function () {
                Route::post('/bulkDelete', [PlayerController::class, 'bulkDelete'])->name('bulkDelete');
            });

        // Movies (custom routes first to avoid conflict with resource show)
        Route::prefix('movies')
            ->name('movies.')
            ->group(function () {
                Route::get('/stream/{filename}', [MovieController::class, 'stream'])->name('stream');
                Route::post('/bulkDelete', [MovieController::class, 'bulkDelete'])->name('bulkDelete');
            });
        Route::resource('movies', MovieController::class);

        // Movies Categories
        Route::resource('movie-categories', MovieCategoryController::class);
        Route::prefix('movie-categories')
            ->name('movie-categories.')
            ->group(function () {
                Route::post('/bulkDelete', [MovieCategoryController::class, 'bulkDelete'])->name('bulkDelete');
            });

        // Places
        Route::resource('places', PlaceController::class);
        Route::prefix('places')
            ->name('places.')
            ->group(function () {
                Route::post('/bulkDelete', [PlaceController::class, 'bulkDelete'])->name('bulkDelete');
            });

        // Place Categories
        Route::resource('place-categories', PlaceCategoryController::class);
        Route::prefix('place-categories')
            ->name('place-categories.')
            ->group(function () {
                Route::post('/bulkDelete', [PlaceCategoryController::class, 'bulkDelete'])->name('bulkDelete');
            });

        // Guide
        Route::resource('guides', GuideItemController::class);
        Route::prefix('guides')
            ->name('guides.')
            ->group(function () {
                Route::post('/bulkDelete', [GuideItemController::class, 'bulkDelete'])->name('bulkDelete');
            });
        // Guide Categories
        Route::resource('guide-categories', GuideCategoryController::class);
        Route::prefix('guide-categories')
            ->name('guide-categories.')
            ->group(function () {
                Route::post('/bulkDelete', [GuideCategoryController::class, 'bulkDelete'])->name('bulkDelete');
            });

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

        // Media Library
        Route::prefix('media')
            ->name('media.')
            ->group(function () {
                Route::get('/library', [MediaController::class, 'library'])->name('library');
                Route::post('/bulkDelete', [MediaController::class, 'bulkDelete'])->name('bulkDelete');
                Route::post('/bulkUpdate', [MediaController::class, 'bulkUpdate'])->name('bulkUpdate');
                Route::match(['get', 'post'], '/upload-chunk', [MediaController::class, 'uploadChunk'])->name('uploadChunk');
            });
        Route::resource('media', MediaController::class)
            ->parameters(['media' => 'uuid'])
            ->only(['index', 'store', 'destroy']);

        Route::prefix('themes')
            ->name('themes.')
            ->group(function () {
                Route::post('/{theme}/set-default', [ThemeController::class, 'setDefault'])->name('set-default');
            });
        Route::resource('themes', ThemeController::class)->only(['index', 'edit', 'update']);

        Route::prefix('settings')
            ->name('settings')
            ->group(function () {
                Route::get('/', [SettingWebsiteController::class, 'index'])->name('.index');
                Route::post('/update', [SettingWebsiteController::class, 'update'])->name('.update');
            });
    });

    Route::prefix('transactions')
        ->name('transactions')
        ->group(function () {
            Route::get('/', [MenuTransactionController::class, 'index'])->name('.index');
            Route::post('/status/{id}', [MenuTransactionController::class, 'updateStatus'])->name('.status');
        });
});


Route::get('/error/404', [ErrorController::class, 'error404'])->name('error.404');

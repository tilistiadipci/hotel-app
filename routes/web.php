<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\BookingPlayerDurationReportController;
use App\Http\Controllers\BookingPlayerReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ErrorController;
use App\Http\Controllers\GuideCategoryController;
use App\Http\Controllers\GuideItemController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HotelAdminController;
use App\Http\Controllers\HotelController;
use App\Http\Controllers\HotelRegistrationController;
use App\Http\Controllers\HotelSettingController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\ManagerDashboardController;
use App\Http\Controllers\ManagerHotelContextController;
use App\Http\Controllers\ManagerHotelController;
use App\Http\Controllers\ManagerHotelSettingsController;
use App\Http\Controllers\ManagerHotelUserController;
use App\Http\Controllers\ManagerPortfolioController;
use App\Http\Controllers\ManagerPortfolioReportController;
use App\Http\Controllers\ManagerTvChannelAccessController;
use App\Http\Controllers\MasterPaketController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\MenuCategoryController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\MenuTenantController;
use App\Http\Controllers\MenuTransactionController;
use App\Http\Controllers\MenuTransactionReportController;
use App\Http\Controllers\MovieCategoryController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\MqttDocumentationController;
use App\Http\Controllers\PlaceCategoryController;
use App\Http\Controllers\PlaceController;
use App\Http\Controllers\PlatformDashboardController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\PlayerContentController;
use App\Http\Controllers\PlayerGroupController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicLandingPageController;
use App\Http\Controllers\RunningTextController;
use App\Http\Controllers\SettingWebsiteController;
use App\Http\Controllers\SongController;
use App\Http\Controllers\SongPlaylistController;
use App\Http\Controllers\SuperadminHotelContextController;
use App\Http\Controllers\SuperadminMasterDataController;
use App\Http\Controllers\SuperadminMediaLibraryController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\TVChannelController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarningController;
use App\Http\Controllers\WilayahIndonesiaController;
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

Route::get('/', [PublicLandingPageController::class, 'show'])->name('landing.show');
Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::get('/docs/mqtt', MqttDocumentationController::class)
    ->middleware(['auth', 'role.category:master,superadmin'])
    ->name('docs.mqtt');

Route::middleware(['auth', 'role.category:master,superadmin'])
    ->prefix('superadmin')
    ->name('platform.')
    ->group(function () {
        Route::get('/dashboard', [PlatformDashboardController::class, 'index'])->name('dashboard');
        Route::get('/landing-page', [LandingPageController::class, 'edit'])->name('landing-page.edit');
        Route::put('/landing-page', [LandingPageController::class, 'update'])->name('landing-page.update');
        Route::post('/hotels/license-key/generate', [HotelController::class, 'generateLicenseKey'])->name('hotels.license-key.generate');
        Route::put('/hotels/{hotel}/settings', [HotelSettingController::class, 'update'])->name('hotels.settings.update');
        Route::put('/hotels/{hotel}/tv-channels', [HotelController::class, 'updateTvChannels'])->name('hotels.tv-channels.update');
        Route::resource('hotels', HotelController::class)->except('destroy');
        Route::resource('hotel-admins', HotelAdminController::class)
            ->parameters(['hotel-admins' => 'hotelAdmin'])
            ->except('show');
        Route::resource('managers', ManagerController::class)->except('show');
        Route::resource('master-paket', MasterPaketController::class)
            ->parameters(['master-paket' => 'masterPaket'])
            ->except('show');
        Route::get('/registrations', [HotelRegistrationController::class, 'index'])->name('registrations.index');
        Route::get('/registrations/{registration}/review', [HotelRegistrationController::class, 'review'])->name('registrations.review');
        Route::patch('/registrations/{registration}', [HotelRegistrationController::class, 'update'])->name('registrations.update');

        // Master data (dicopy ke tiap hotel baru) - reuses the normal hotel
        // settings/theme/tv-channels/media screens, pointed at the Master hotel.
        Route::get('/master-settings', [SuperadminMasterDataController::class, 'settings'])->name('master-settings.index');
        Route::get('/master-theme', [SuperadminMasterDataController::class, 'themes'])->name('master-theme.index');
        Route::get('/master-tv-channels', [SuperadminMasterDataController::class, 'tvChannels'])->name('master-tv-channels.index');
        Route::get('/media-library', [SuperadminMediaLibraryController::class, 'index'])->name('media-library.index');
        Route::post('/hotel-context', [SuperadminHotelContextController::class, 'update'])->name('hotel-context.update');
    });

Route::middleware(['auth', 'role.category:manager'])
    ->prefix('manager')
    ->name('manager.')
    ->group(function () {
        Route::get('/dashboard', [ManagerDashboardController::class, 'index'])->name('dashboard');
        Route::get('/portfolio', [ManagerPortfolioController::class, 'index'])->name('portfolio');
        Route::resource('hotel-users', ManagerHotelUserController::class)
            ->parameters(['hotel-users' => 'hotelUser'])
            ->except('show');
        Route::get('/reports/checkins', [ManagerPortfolioReportController::class, 'checkins'])->name('reports.checkins.index');
        Route::get('/reports/checkins/data', [ManagerPortfolioReportController::class, 'checkinsData'])->name('reports.checkins.data');
        Route::get('/reports/checkins/export', [ManagerPortfolioReportController::class, 'checkinsExport'])->name('reports.checkins.export');
        Route::get('/reports/player-usage', [ManagerPortfolioReportController::class, 'playerUsage'])->name('reports.player-usage.index');
        Route::get('/reports/player-usage/data', [ManagerPortfolioReportController::class, 'playerUsageData'])->name('reports.player-usage.data');
        Route::get('/reports/player-usage/export', [ManagerPortfolioReportController::class, 'playerUsageExport'])->name('reports.player-usage.export');
        Route::get('/reports/player-usage/chart', [ManagerPortfolioReportController::class, 'playerUsageChart'])->name('reports.player-usage.chart');
        Route::get('/tv-channels', [ManagerTvChannelAccessController::class, 'index'])->name('tv-channels.index');
        Route::put('/tv-channels/{hotel}', [ManagerTvChannelAccessController::class, 'update'])->name('tv-channels.update');
        Route::post('/hotels/{hotel}/activate', [ManagerHotelContextController::class, 'update'])->name('hotels.activate');
        Route::get('/hotels/{hotel}/settings', [ManagerHotelSettingsController::class, 'edit'])->name('hotels.settings.edit');
        Route::put('/hotels/{hotel}/settings', [ManagerHotelSettingsController::class, 'update'])->name('hotels.settings.update');
        Route::patch('/hotels/{hotel}/status', [ManagerHotelController::class, 'updateStatus'])->name('hotels.status');
        Route::post('/hotel-context/clear', [ManagerHotelContextController::class, 'clear'])->name('hotel-context.clear');
    });

// change language
Route::post('change-language', [HomeController::class, 'changeLanguage'])
    ->middleware(['manager.hotel.access', 'hotel.resolve'])
    ->name('change-language');

Route::middleware(['auth', 'role.category:manager,admin,operator,user', 'manager.hotel.access', 'hotel.resolve', 'hotel.license'])->group(function () {
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

    Route::prefix('license')
        ->name('licenses.')
        ->group(function () {
            Route::get('/', [LicenseController::class, 'index'])->name('index');
        });

    // users (only admin & super admin/master)
    Route::middleware('role.category:manager,admin')->group(function () {
        Route::resource('users', UserController::class);
        Route::prefix('users')
            ->name('users.')
            ->group(function () {
                Route::post('/bulkDelete', [UserController::class, 'bulkDelete'])->name('bulkDelete');
                Route::get('/{id}/detail/{part}', [UserController::class, 'detail'])->name('detail');
            });

        // Songs
        Route::middleware('setting.active:menu_music_status')->group(function () {
            Route::resource('songs', SongController::class);
            Route::prefix('songs')
                ->name('songs.')
                ->group(function () {
                    Route::post('/bulkDelete', [SongController::class, 'bulkDelete'])->name('bulkDelete');
                    Route::get('/import/template', [SongController::class, 'downloadImportTemplate'])->name('import.template');
                    Route::post('/import', [SongController::class, 'import'])->name('import');
                });

            Route::resource('song-playlists', SongPlaylistController::class);
            Route::prefix('song-playlists')
                ->name('song-playlists.')
                ->group(function () {
                    Route::post('/bulkDelete', [SongPlaylistController::class, 'bulkDelete'])->name('bulkDelete');
                });
        });

        // Players
        Route::resource('players', PlayerController::class);
        Route::prefix('players')
            ->name('players.')
            ->group(function () {
                Route::post('/bulkDelete', [PlayerController::class, 'bulkDelete'])->name('bulkDelete');
                Route::post('/{player}/token', [PlayerController::class, 'regenerateToken'])->name('token');
                Route::get('/{player}/content', [PlayerContentController::class, 'edit'])->name('content.edit');
                Route::put('/{player}/content', [PlayerContentController::class, 'update'])->name('content.update');
            });

        // Player Groups
        Route::resource('player-groups', PlayerGroupController::class);
        Route::prefix('player-groups')
            ->name('player-groups.')
            ->group(function () {
                Route::post('/bulkDelete', [PlayerGroupController::class, 'bulkDelete'])->name('bulkDelete');
            });

        // Movies (custom routes first to avoid conflict with resource show)
        Route::middleware('setting.active:menu_vod_status')->group(function () {
            Route::prefix('movies')
                ->name('movies.')
                ->group(function () {
                    Route::get('/stream/{filename}', [MovieController::class, 'stream'])->name('stream');
                    Route::post('/bulkDelete', [MovieController::class, 'bulkDelete'])->name('bulkDelete');
                    Route::get('/import/template', [MovieController::class, 'downloadImportTemplate'])->name('import.template');
                    Route::post('/import', [MovieController::class, 'import'])->name('import');
                });
            Route::resource('movies', MovieController::class);

            // Movies Categories
            Route::resource('movie-categories', MovieCategoryController::class);
            Route::prefix('movie-categories')
                ->name('movie-categories.')
                ->group(function () {
                    Route::post('/bulkDelete', [MovieCategoryController::class, 'bulkDelete'])->name('bulkDelete');
                });
        });

        // Places
        Route::middleware('setting.active:menu_nearby_status')->group(function () {
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
        });

        // Guide
        Route::middleware('setting.active:menu_guide_status')->group(function () {
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
        });

        // Running Texts
        Route::resource('running-texts', RunningTextController::class);
        Route::prefix('running-texts')
            ->name('running-texts.')
            ->group(function () {
                Route::post('/bulkDelete', [RunningTextController::class, 'bulkDelete'])->name('bulkDelete');
                Route::post('/preview-rss', [RunningTextController::class, 'previewRss'])->name('preview-rss');
            });

        Route::middleware('setting.active:warning_broadcast_status')->group(function () {
            Route::get('warnings', [WarningController::class, 'index'])->name('warnings.index');
            Route::get('warnings/create', [WarningController::class, 'create'])->name('warnings.create');
            Route::post('warnings', [WarningController::class, 'store'])->name('warnings.store');
        });

        // Menu
        Route::middleware('setting.active:menu_shopping_status')->group(function () {
            Route::resource('menu', MenuController::class);
            Route::prefix('menu')
                ->name('menu.')
                ->group(function () {
                    Route::post('/bulkDelete', [MenuController::class, 'bulkDelete'])->name('bulkDelete');
                });
            Route::resource('menu-tenants', MenuTenantController::class);
            Route::prefix('menu-tenants')
                ->name('menu-tenants.')
                ->group(function () {
                    Route::post('/bulkDelete', [MenuTenantController::class, 'bulkDelete'])->name('bulkDelete');
                });
            // Menu Categories
            Route::resource('menu-categories', MenuCategoryController::class);
            Route::prefix('menu-categories')
                ->name('menu-categories.')
                ->group(function () {
                    Route::post('/bulkDelete', [MenuCategoryController::class, 'bulkDelete'])->name('bulkDelete');
                });
        });

        Route::prefix('settings')
            ->name('settings')
            ->group(function () {
                Route::get('/', [SettingWebsiteController::class, 'index'])->name('.index');
                Route::post('/update', [SettingWebsiteController::class, 'update'])->name('.update');
            });

        Route::get('/wilayah-indonesia/search', [WilayahIndonesiaController::class, 'search'])
            ->name('wilayah-indonesia.search');

        Route::prefix('reports')
            ->name('reports.')
            ->group(function () {
                // laporan booking player
                Route::get('/booking-players', [BookingPlayerReportController::class, 'index'])
                    ->name('booking-players.index');
                Route::get('/booking-players/data', [BookingPlayerReportController::class, 'data'])
                    ->name('booking-players.data');
                Route::get('/booking-players/export', [BookingPlayerReportController::class, 'export'])
                    ->name('booking-players.export');

                // laporan durasi pemakaian player
                Route::get('/player-durations', [BookingPlayerDurationReportController::class, 'index'])
                    ->name('player-durations.index');
                Route::get('/player-durations/data', [BookingPlayerDurationReportController::class, 'data'])
                    ->name('player-durations.data');
                Route::get('/player-durations/export', [BookingPlayerDurationReportController::class, 'export'])
                    ->name('player-durations.export');
                Route::get('/player-durations/chart', [BookingPlayerDurationReportController::class, 'chart'])
                    ->name('player-durations.chart');

                // laporan menu transaksi
                Route::get('/menu-transactions', [MenuTransactionReportController::class, 'index'])
                    ->name('menu-transactions.index');
                Route::get('/menu-transactions/data', [MenuTransactionReportController::class, 'data'])
                    ->name('menu-transactions.data');
                Route::get('/menu-transactions/export', [MenuTransactionReportController::class, 'export'])
                    ->name('menu-transactions.export');
            });
    });

    Route::prefix('transactions')
        ->name('transactions')
        ->group(function () {
            Route::get('/', [MenuTransactionController::class, 'index'])->name('.index');
            Route::post('/status/{id}', [MenuTransactionController::class, 'updateStatus'])->name('.status');
            Route::post('/cancel/{id}', [MenuTransactionController::class, 'cancel'])->name('.cancel');
        });
});

// Media Library, Themes, and TV Channels are also reachable by superadmin
// "acting as" a hotel (defaults to the Master hotel) - see
// DefaultSuperadminHotelContext + SuperadminHotelContextController.
Route::middleware(['auth', 'role.category:manager,admin,master,superadmin', 'hotel.context.default', 'manager.hotel.access', 'hotel.resolve', 'hotel.license'])->group(function () {
    Route::prefix('media')
        ->name('media.')
        ->group(function () {
            Route::get('/library', [MediaController::class, 'library'])->name('library');
            Route::post('/bulkDelete', [MediaController::class, 'bulkDelete'])->name('bulkDelete');
            Route::post('/bulkUpdate', [MediaController::class, 'bulkUpdate'])->name('bulkUpdate');
            Route::match(['get', 'post'], '/upload-chunk', [MediaController::class, 'uploadChunk'])->name('uploadChunk');
            Route::get('/sync-preview', [MediaController::class, 'syncPreview'])->name('syncPreview');
            Route::post('/sync-item', [MediaController::class, 'syncItem'])->name('syncItem');
            Route::post('/sync', [MediaController::class, 'sync'])->name('sync');
            Route::post('/sync-clear', [MediaController::class, 'syncClear'])->name('syncClear');
            Route::post('/sync-clear-issues', [MediaController::class, 'syncClearIssues'])->name('syncClearIssues');
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

    Route::prefix('tv-channels')
        ->name('tv-channels.')
        ->group(function () {
            Route::get('/import', [TVChannelController::class, 'importForm'])->name('import');
            Route::get('/import/preview', [TVChannelController::class, 'staleImportPreview'])->name('import.preview.stale');
            Route::post('/import/preview', [TVChannelController::class, 'importPreview'])->name('import.preview');
            Route::get('/import/preview/{token}', [TVChannelController::class, 'showImportPreview'])->name('import.preview.show');
            Route::post('/import/store', [TVChannelController::class, 'importStore'])->name('import.store');
            Route::get('/{uid}/assignment', [TVChannelController::class, 'editAssignment'])->name('assignment.edit');
            Route::patch('/{uid}/assignment', [TVChannelController::class, 'updateAssignment'])->name('assignment.update');
            Route::post('/bulkDelete', [TVChannelController::class, 'bulkDelete'])->name('bulkDelete');
        });
    Route::resource('tv-channels', TVChannelController::class);
});

Route::get('/error/404', [ErrorController::class, 'error404'])->name('error.404');

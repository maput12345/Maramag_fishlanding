<?php

use App\Http\Controllers\Admin\ApplicationManagementController;
use App\Http\Controllers\ApplicationPortalController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Broker\FinancialStatementController;
use App\Http\Controllers\Broker\FishTypesController;
use App\Http\Controllers\Broker\FishBoxController;
use App\Http\Controllers\Broker\FishPricesController;
use App\Http\Controllers\Admin\FishManagementController;
use App\Http\Controllers\Broker\SalesController;
use App\Http\Controllers\Broker\FishboxManagementController;
use App\Http\Controllers\BrokerDashboardController;
use App\Http\Controllers\SalesManagementController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    if (Auth::check()) {
        $user = Auth::user();
        if (in_array($user->role, ['admin', 'staff'], true)) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->role === 'broker') {
            return redirect()->route('broker.dashboard');
        }

        return redirect()->route('applications.index');
    }

    return redirect()->route('login');
});

Auth::routes();

// Custom logout route
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('login')->with('message', 'You have been logged out successfully.');
})->name('logout')->middleware('auth');

// Profile routes - available to all authenticated users
Route::middleware(['auth'])->group(function () {
    Route::controller(ProfileController::class)->prefix('profile')->name('profile.')->group(function () {
        Route::get('/', 'show')->name('show');
        Route::put('/', 'update')->name('update');
    });

    Route::controller(ApplicationPortalController::class)->prefix('applications')->name('applications.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/my-applications', 'myApplications')->name('my-applications');
        Route::get('/openings/{opening}', 'create')->name('create');
        Route::post('/openings/{opening}', 'store')->name('store');
        Route::get('/{application}/edit', 'edit')->name('edit');
        Route::patch('/{application}', 'update')->name('update');
        Route::get('/{application}', 'show')->name('show');
    });
});

// Admin routes
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/dashboard', [App\Http\Controllers\AdminDashboardController::class, 'index'])->name('admin.dashboard');

    // User Management routes - grouped by controller
    Route::controller(UserManagementController::class)->prefix('admin/users')->name('admin.users.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}/edit', 'edit')->name('edit');
        Route::put('/{id}', 'update')->name('update');
        Route::patch('/{id}/activate', 'activate')->name('activate');
        Route::patch('/{id}/deactivate', 'deactivate')->name('deactivate');
        Route::delete('/{id}', 'destroy')->name('destroy');
    });

    Route::post('/admin/brokers/{broker}/switch-view', [UserManagementController::class, 'startBrokerView'])
        ->name('admin.broker-view.start');
    Route::delete('/admin/broker-view', [UserManagementController::class, 'stopBrokerView'])
        ->name('admin.broker-view.stop');
    Route::post('/admin/broker-view/support-actions', [UserManagementController::class, 'enableBrokerSupportActions'])
        ->name('admin.broker-view.support.enable');
    Route::delete('/admin/broker-view/support-actions', [UserManagementController::class, 'disableBrokerSupportActions'])
        ->name('admin.broker-view.support.disable');


    // Sales Management routes - grouped by controller
    Route::controller(SalesManagementController::class)->prefix('admin/sales')->name('admin.sales.')->group(function () {
        Route::get('/tracking', 'fishboxTracking')->name('tracking');
        Route::get('/brokers/{broker}/receipt-data', 'brokerReceiptData')->name('receipt-data');
        Route::get('/', 'index')->name('index');
    });

    Route::controller(ApplicationManagementController::class)->prefix('admin/applications')->name('admin.applications.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{application}', 'show')->name('show');
        Route::patch('/{application}/review-draft', 'saveReviewDraft')->name('review-draft');
        Route::patch('/{application}/review', 'review')->name('review');
        Route::post('/{application}/winner', 'selectWinner')->name('winner');
    });

    Route::controller(ApplicationManagementController::class)->prefix('admin/stalls')->name('admin.stalls.')->group(function () {
        Route::get('/', 'stallsIndex')->name('index');
        Route::get('/requirements', 'stallsRequirements')->name('requirements.index');
        Route::get('/overview', 'stallsOverview')->name('overview');
        Route::post('/', 'storeStall')->name('store');
        Route::post('/requirements', 'storeRequirementType')->name('requirements.store');
        Route::post('/openings', 'storeOpening')->name('openings.store');
        Route::patch('/openings/{opening}', 'updateOpening')->name('openings.update');
        Route::patch('/openings/{opening}/status', 'updateOpeningStatus')->name('openings.status');
    });
});

// Broker routes
Route::middleware(['auth', 'broker'])->group(function () {
    Route::get('/broker/dashboard', [BrokerDashboardController::class, 'index'])->name('broker.dashboard');
    Route::get('/broker/analytics', [SalesManagementController::class, 'analytics'])->name('broker.sales.analytics');
    Route::get('/broker/transaction', [SalesManagementController::class, 'transaction'])->name('broker.transaction');
    Route::get('/broker/sales', [SalesManagementController::class, 'sales'])->name('broker.sales.sales');
    Route::get('/broker/financial-statement', [FinancialStatementController::class, 'index'])->name('broker.financial-statements.index');
    Route::post('/broker/financial-statement/entries', [FinancialStatementController::class, 'store'])->name('broker.financial-statements.entries.store');
    Route::delete('/broker/financial-statement/entries/{entry}', [FinancialStatementController::class, 'destroy'])->name('broker.financial-statements.entries.destroy');

    // Fishbox Management routes for brokers
    Route::controller(FishboxManagementController::class)->prefix('broker')->name('broker.')->group(function () {
        Route::get('/inventory', 'index')->name('inventory.index');
    });

    // Fish Types Management routes for brokers
    Route::controller(FishTypesController::class)->prefix('broker')->name('broker.')->group(function () {
        Route::post('/fish-types', 'store')->name('fish-types.store');
        Route::put('/fish-types/{id}', 'update')->name('fish-types.update');
        Route::delete('/fish-types/{id}', 'destroy')->name('fish-types.destroy');
    });

    // Fish Price Management routes for brokers
    Route::controller(FishPricesController::class)->prefix('broker')->name('broker.')->group(function () {
        Route::post('/fish-prices', 'store')->name('fish-prices.store');
        Route::put('/fish-prices/{id}', 'update')->name('fish-prices.update');
        Route::delete('/fish-prices/{id}', 'destroy')->name('fish-prices.destroy');
    });

    // Fish Box Management routes for brokers
    Route::controller(FishBoxController::class)->prefix('broker')->name('broker.')->group(function () {
        Route::get('/fish-box-tracking', 'tracking')->name('fish-boxes.tracking');
        Route::post('/fish-boxes', 'store')->name('fish-boxes.store');
        Route::put('/fish-boxes/{id}', 'update')->name('fish-boxes.update');
        Route::delete('/fish-boxes/{id}', 'destroy')->name('fish-boxes.destroy');
        Route::post('/fish-boxes/bulk-restock', 'bulkRestock')->name('fish-boxes.bulk-restock');
        Route::post('/fish-boxes/return-to-stock', 'returnToStock')->name('fish-boxes.return-to-stock');
        Route::post('/fish-boxes/return-via-qr', 'returnFishBoxViaQr')->name('fish-boxes.return-via-qr');
        Route::patch('/fish-boxes/{id}/mark-missing', 'markAsMissing')->name('fish-boxes.mark-missing');
        Route::patch('/fish-boxes/{id}/return', 'returnFishBox')->name('fish-boxes.return');
    });

    // Sales Management routes
    Route::controller(SalesController::class)->prefix('broker')->name('broker.')->group(function () {
        Route::post('/sales', 'store')->name('sales.store');
        Route::put('/sales/{id}', 'update')->name('sales.update');
        Route::delete('/sales/{id}', 'destroy')->name('sales.destroy');
        Route::post('/sales-payments', 'storePayment')->name('sales-payments.store');
        Route::delete('/sales-payments/{id}', 'destroyPayment')->name('sales-payments.destroy');
        Route::get('/sales/fish-boxes/{qrCode}', 'getFishBoxByQRCode')->name('fish-boxes.qr');
    });

});

<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HouseController;
use App\Http\Controllers\Admin\ParticipantController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicRegistrationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicRegistrationController::class, 'landing'])->name('public.landing');
Route::get('/daftar', [PublicRegistrationController::class, 'create'])->name('public.register');
Route::post('/daftar', [PublicRegistrationController::class, 'store'])->name('public.register.store');
Route::get('/berjaya/{registration_code}', [PublicRegistrationController::class, 'success'])->name('public.success');
Route::get('/semak', [PublicRegistrationController::class, 'check'])->name('public.check');
Route::post('/semak', [PublicRegistrationController::class, 'lookup'])->name('public.lookup');
Route::get('/status/{registration_code}', [PublicRegistrationController::class, 'status'])->name('public.status');

Route::redirect('/dashboard', '/admin/dashboard')->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/admin/dashboard', DashboardController::class)->name('admin.dashboard');
    Route::resource('/admin/participants', ParticipantController::class)->names('admin.participants');
    Route::resource('/admin/houses', HouseController::class)->names('admin.houses');
    Route::resource('/admin/sports', SportController::class)->names('admin.sports');
    Route::get('/admin/reports', [ReportController::class, 'index'])->name('admin.reports.index');
    Route::get('/admin/reports/export', [ReportController::class, 'export'])->name('admin.reports.export');
    Route::get('/admin/reports/print', [ReportController::class, 'print'])->name('admin.reports.print');
    Route::get('/admin/audit-logs', [AuditLogController::class, 'index'])->name('admin.audit-logs.index');
    Route::get('/admin/settings', [SettingController::class, 'edit'])->name('admin.settings.edit');
    Route::put('/admin/settings', [SettingController::class, 'update'])->name('admin.settings.update');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

<?php

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ChecklistKanbanController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\RequestStatusController;
use App\Http\Controllers\SkRatePerjalananController;
use App\Http\Controllers\SpjChecklistController;
use App\Http\Controllers\SuperkendisController;
use App\Http\Controllers\TemplateController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // FPA / Permintaan Routes
    Route::get('/requests/check-nomor-fpa', [RequestController::class, 'checkNomorFpa'])->name('requests.check-nomor-fpa');
    Route::resource('requests', RequestController::class);

    // Checklist SPJ Routes
    Route::get('/checklists/{id}/edit', [SpjChecklistController::class, 'edit'])->name('checklists.edit');
    Route::put('/checklists/{id}', [SpjChecklistController::class, 'update'])->name('checklists.update');
    Route::patch('/checklists/{id}/status', [ChecklistKanbanController::class, 'updateStatus'])->name('checklists.status');
    Route::post('/checklists/{id}/upload', [FileUploadController::class, 'upload'])->name('checklists.upload');
    Route::get('/checklists/{id}/download', [FileUploadController::class, 'download'])->name('checklists.download');

    // Status SPJ Routes
    Route::post('/requests/{id}/status', [RequestStatusController::class, 'update'])->name('requests.status.update');
    Route::patch('/requests/{id}/status-ajax', [RequestStatusController::class, 'updateAjax'])->name('requests.status.ajax');

    // Superkendis Routes
    Route::get('/requests/{requestId}/superkendis', [SuperkendisController::class, 'index'])->name('requests.superkendis');
    Route::post('/requests/{requestId}/superkendis/generate/{pelaksanaId}', [SuperkendisController::class, 'generate'])->name('requests.superkendis.generate');
    Route::post('/requests/{requestId}/superkendis/bulk', [SuperkendisController::class, 'bulk'])->name('requests.superkendis.bulk');
    Route::post('/requests/{requestId}/superkendis/bulk-separate', [SuperkendisController::class, 'bulk'])->name('requests.superkendis.bulk-separate');
    Route::post('/requests/{requestId}/superkendis/bulk-merged', [SuperkendisController::class, 'bulk'])->name('requests.superkendis.bulk-merged');

    // Calendar Routes
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::get('/calendar/events', [CalendarController::class, 'events'])->name('calendar.events');

    // Repository Template Routes
    Route::get('/templates/{id}/download', [TemplateController::class, 'download'])->name('templates.download');
    Route::resource('templates', TemplateController::class);

    // SK Rate Perjalanan Routes
    Route::resource('sk-rates', SkRatePerjalananController::class)->except(['show']);
});

require __DIR__.'/auth.php';

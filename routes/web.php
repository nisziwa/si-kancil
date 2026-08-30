<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // FPA / Permintaan Routes
    Route::resource('requests', App\Http\Controllers\RequestController::class);

    // Checklist SPJ Routes
    Route::get('/checklists/{id}/edit', [App\Http\Controllers\SpjChecklistController::class, 'edit'])->name('checklists.edit');
    Route::put('/checklists/{id}', [App\Http\Controllers\SpjChecklistController::class, 'update'])->name('checklists.update');
    Route::patch('/checklists/{id}/status', [App\Http\Controllers\ChecklistKanbanController::class, 'updateStatus'])->name('checklists.status');
    Route::post('/checklists/{id}/upload', [App\Http\Controllers\FileUploadController::class, 'upload'])->name('checklists.upload');
    Route::get('/checklists/{id}/download', [App\Http\Controllers\FileUploadController::class, 'download'])->name('checklists.download');
    
    // Status SPJ Routes
    Route::post('/requests/{id}/status', [App\Http\Controllers\RequestStatusController::class, 'update'])->name('requests.status.update');
    Route::patch('/requests/{id}/status-ajax', [App\Http\Controllers\RequestStatusController::class, 'updateAjax'])->name('requests.status.ajax');

    // Calendar Routes
    Route::get('/calendar', [App\Http\Controllers\CalendarController::class, 'index'])->name('calendar.index');
    Route::get('/calendar/events', [App\Http\Controllers\CalendarController::class, 'events'])->name('calendar.events');

    // Repository Template Routes
    Route::get('/templates/{id}/download', [App\Http\Controllers\TemplateController::class, 'download'])->name('templates.download');
    Route::resource('templates', App\Http\Controllers\TemplateController::class);
});

require __DIR__.'/auth.php';

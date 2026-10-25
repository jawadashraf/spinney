<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Yeastar\CallJournalController;
use App\Http\Controllers\Api\Yeastar\LookupContactController;
use App\Http\Controllers\Api\Yeastar\SearchContactController;
use App\Http\Controllers\Api\Yeastar\UnknownContactController;
use App\Http\Controllers\Api\Yeastar\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
 * Yeastar P-Series PBX (Custom CRM Integration template, see docs/yeaster call setup/Spinney.xml).
 */
Route::prefix('yeastar')
    ->name('yeastar.')
    ->middleware(['yeastar', 'throttle:120,1'])
    ->group(function (): void {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('contacts', LookupContactController::class)->name('contacts.lookup');
        Route::get('contacts/search', SearchContactController::class)->name('contacts.search');
        Route::post('contacts', UnknownContactController::class)->name('contacts.unknown');
        Route::post('call-journal', [CallJournalController::class, 'store'])->name('call-journal.store');
        Route::put('call-journal/{log}', [CallJournalController::class, 'update'])->name('call-journal.update');
    });

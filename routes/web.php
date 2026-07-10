<?php

declare(strict_types=1);

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\Register;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PrivacyPolicyController;
use App\Http\Controllers\TermsOfServiceController;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Jetstream\Http\Controllers\TeamInvitationController;

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

// Guest routes are handled by Filament/Fortify on the same domain.
// We manually define the Filament route names that are expected by the app panel.
// We use a different URI to avoid conflicts with existing routes while providing the names.
Route::livewire('/filament/login', Login::class)->name('filament.app.auth.login');
Route::post('/filament/login', [AuthenticatedSessionController::class, 'store'])->name('filament.app.auth.login.store');
// Route::livewire('/filament/register', App\Filament\Pages\Auth\Register::class)->name('filament.app.auth.register');
// Route::post('/filament/register', [Laravel\Fortify\Http\Controllers\RegisteredUserController::class, 'store'])->name('filament.app.auth.register.store');
Route::post('/filament/logout', [AuthenticatedSessionController::class, 'destroy'])->name('filament.app.auth.logout');

// Aliases for common route names used in blade views
Route::livewire('/login', Login::class)->name('login');
Route::livewire('/register', Register::class)->name('register');
Route::livewire('/forgot-password', RequestPasswordReset::class)->name('password.request');

Route::get('/', HomeController::class);

Route::get('/terms-of-service', TermsOfServiceController::class)->name('terms.show');
Route::get('/privacy-policy', PrivacyPolicyController::class)->name('policy.show');

Route::redirect('/dashboard', '/')->name('dashboard');

Route::get('/team-invitations/{invitation}', [TeamInvitationController::class, 'accept'])
    ->middleware(['signed', 'verified', 'auth', AuthenticateSession::class])
    ->name('team-invitations.accept');

// Community redirects
Route::get('/discord', function () {
    return redirect()->away(config('services.discord.invite_url'));
})->name('discord');

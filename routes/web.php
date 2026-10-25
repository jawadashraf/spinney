<?php

declare(strict_types=1);

use App\Filament\Pages\Auth\Login;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OutreachUnsubscribeController;
use App\Http\Controllers\PrivacyPolicyController;
use App\Http\Controllers\TermsOfServiceController;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
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
// Self-registration is closed; the name stays so older pages that link to it send people to log in.
Route::redirect('/register', '/login')->name('register');
Route::livewire('/forgot-password', RequestPasswordReset::class)->name('password.request');

Route::get('/', HomeController::class);

Route::get('/terms-of-service', TermsOfServiceController::class)->name('terms.show');
Route::get('/privacy-policy', PrivacyPolicyController::class)->name('policy.show');

Route::redirect('/dashboard', '/')->name('dashboard');

// Signed links in outreach emails. The POST has no CSRF token because mail clients send it for one-click unsubscribe.
Route::get('/outreach/unsubscribe/{recipient}', [OutreachUnsubscribeController::class, 'show'])
    ->middleware('signed')
    ->name('outreach.unsubscribe');
Route::post('/outreach/unsubscribe/{recipient}', [OutreachUnsubscribeController::class, 'store'])
    ->middleware(['signed', 'throttle:10,1'])
    ->withoutMiddleware(PreventRequestForgery::class)
    ->name('outreach.unsubscribe.store');

Route::get('/team-invitations/{invitation}', [TeamInvitationController::class, 'accept'])
    ->middleware(['signed', 'verified', 'auth', AuthenticateSession::class])
    ->name('team-invitations.accept');

// Community redirects
Route::get('/discord', function () {
    return redirect()->away(config('services.discord.invite_url'));
})->name('discord');

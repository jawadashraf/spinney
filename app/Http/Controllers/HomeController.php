<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Filament\Resources\CompanyResource;
use Illuminate\Support\Facades\Auth;

final readonly class HomeController
{
    public function __invoke(): mixed
    {
        if (Auth::check()) {
            return redirect()->intended(
                CompanyResource::getUrl('index')
            );
        }

        return view('home.index2');
    }
}

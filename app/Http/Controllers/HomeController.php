<?php

declare(strict_types=1);

namespace App\Http\Controllers;

final readonly class HomeController
{
    public function __invoke(): mixed
    {
        // Option 1: Return a plain variant of the home page
        return view('home.index2');

        // Option 2: Redirect to the login page
        // return redirect()->route('login');
    }
}

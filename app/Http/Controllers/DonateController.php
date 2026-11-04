<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The public donate page.
 */
final readonly class DonateController
{
    public function show(Request $request): View
    {
        return view('donate.show', ['cancelled' => $request->boolean('cancelled')]);
    }
}

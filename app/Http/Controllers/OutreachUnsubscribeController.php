<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\OutreachEmailRecipient;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Unsubscribes an outreach recipient through the signed link in each email.
 *
 * Opening the link only shows a confirmation button, so email security scanners that follow links
 * do not unsubscribe anyone. Mail clients' one-click unsubscribe (RFC 8058) posts to the same URL.
 */
final readonly class OutreachUnsubscribeController
{
    public function show(Request $request, OutreachEmailRecipient $recipient): View
    {
        return view('outreach.unsubscribe', [
            'recipient' => $recipient,
            'unsubscribed' => $this->isUnsubscribed($recipient),
            'action' => $request->fullUrl(),
        ]);
    }

    public function store(Request $request, OutreachEmailRecipient $recipient): View
    {
        if (! $this->isUnsubscribed($recipient)) {
            $subscriber = $recipient->contact ?? $recipient->company;
            $subscriber->forceFill(['unsubscribed_at' => now()])->save();
        }

        return view('outreach.unsubscribe', [
            'recipient' => $recipient,
            'unsubscribed' => true,
            'action' => $request->fullUrl(),
        ]);
    }

    private function isUnsubscribed(OutreachEmailRecipient $recipient): bool
    {
        $subscriber = $recipient->contact ?? $recipient->company;

        return $subscriber->unsubscribed_at !== null;
    }
}

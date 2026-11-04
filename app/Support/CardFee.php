<?php

declare(strict_types=1);

namespace App\Support;

/**
 * The amount a donor can add so the charity still receives their whole gift after Stripe's fee, worked out the
 * same way as the donate page on spinneyhill.org.uk: the gift plus the fixed fee, grossed up by the percentage fee.
 */
final class CardFee
{
    public static function toCover(int $giftPence): int
    {
        if ($giftPence <= 0) {
            return 0;
        }

        $percent = min((float) config('services.stripe.fee_percent'), 20.0);
        $fixedPence = (int) config('services.stripe.fee_fixed_pence');

        return (int) ceil(round(($giftPence + $fixedPence) / (1 - $percent / 100), 6)) - $giftPence;
    }

    /**
     * "£10" for whole pounds, "£10.50" otherwise.
     */
    public static function format(int $pence): string
    {
        return '£'.($pence % 100 === 0 ? number_format($pence / 100) : number_format($pence / 100, 2));
    }
}

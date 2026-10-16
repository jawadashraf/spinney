<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Daily Sending Limit
    |--------------------------------------------------------------------------
    |
    | How many recipients the mailbox may send to in a rolling 24 hours (the
    | Hostinger free mailbox allows about 100). Every email the application
    | sends counts, and a share is always kept for system notifications.
    |
    */

    'daily_limit' => (int) env('OUTREACH_DAILY_LIMIT', 100),

    'reserved_for_notifications' => (int) env('OUTREACH_RESERVED_FOR_NOTIFICATIONS', 30),

    /*
    |--------------------------------------------------------------------------
    | Default Template
    |--------------------------------------------------------------------------
    |
    | The Fin Mail template that supplies the layout and theme when an
    | outreach email is not based on another template.
    |
    */

    'template_key' => 'outreach',

    /*
    |--------------------------------------------------------------------------
    | Reply-To Address
    |--------------------------------------------------------------------------
    |
    | Replies to outreach emails go here. Defaults to the "from" address.
    |
    */

    'reply_to' => env('OUTREACH_REPLY_TO'),
];

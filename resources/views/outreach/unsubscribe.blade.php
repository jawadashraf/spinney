<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Unsubscribe · Spinney Hill Support Centre</title>
    <style>
        body { margin: 0; font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background: #f3f4f6; color: #111827; }
        main { max-width: 32rem; margin: 4rem auto; padding: 2rem; background: #fff; border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); }
        h1 { font-size: 1.25rem; margin: 0 0 1rem; }
        p { line-height: 1.5; color: #374151; }
        button { margin-top: 1rem; padding: 0.625rem 1.25rem; border: 0; border-radius: 0.5rem; background: #4f46e5; color: #fff; font-size: 1rem; cursor: pointer; }
        @media (max-width: 36rem) { main { margin: 1rem; } }
    </style>
</head>
<body>
<main>
    @if ($unsubscribed)
        <h1>You have been unsubscribed</h1>
        <p>{{ $recipient->email }} will no longer receive outreach emails from Spinney Hill Support Centre.</p>
        <p>If this was a mistake, reply to any of our emails and we will add you back.</p>
    @else
        <h1>Unsubscribe from Spinney Hill emails?</h1>
        <p>{{ $recipient->email }} will stop receiving outreach emails from Spinney Hill Support Centre.</p>
        <form method="POST" action="{{ $action }}">
            <button type="submit">Unsubscribe</button>
        </form>
    @endif
</main>
</body>
</html>

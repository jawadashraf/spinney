<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Team;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates the Yeastar PBX by the API key it sends as HTTP basic credentials,
 * and scopes the request to the team the phone system belongs to.
 */
final class AuthenticateYeastar
{
    public const string TEAM_ATTRIBUTE = 'yeastar_team';

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = (string) config('services.yeastar.api_key');
        $team = Team::query()->find(config('services.yeastar.team_id'));

        if ($apiKey === '' || ! $team instanceof Team || ! $this->hasValidKey($request, $apiKey)) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        setPermissionsTeamId($team->id);
        $request->attributes->set(self::TEAM_ATTRIBUTE, $team);

        return $next($request);
    }

    /**
     * Yeastar base64-encodes the key on its own; other clients send it as the username or password.
     */
    private function hasValidKey(Request $request, string $apiKey): bool
    {
        $header = (string) $request->header('Authorization');

        if (! str_starts_with($header, 'Basic ')) {
            return false;
        }

        $credential = base64_decode(mb_substr($header, 6), true);

        if ($credential === false) {
            return false;
        }

        $candidates = [$credential, ...explode(':', $credential, 2)];

        foreach ($candidates as $candidate) {
            if (hash_equals($apiKey, $candidate)) {
                return true;
            }
        }

        return false;
    }
}

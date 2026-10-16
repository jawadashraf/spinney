<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Yeastar;

use App\Actions\Calls\LogPhoneSystemCall;
use App\Http\Middleware\AuthenticateYeastar;
use App\Http\Requests\Yeastar\StoreCallJournalRequest;
use App\Http\Requests\Yeastar\UpdateCallJournalRequest;
use App\Models\PbxCallLog;
use App\Models\Team;
use Illuminate\Http\JsonResponse;

final readonly class CallJournalController
{
    public function __construct(private LogPhoneSystemCall $logPhoneSystemCall) {}

    /**
     * Log a finished call. The returned id comes back as CrmActId on CallJournalUpdate.
     */
    public function store(StoreCallJournalRequest $request): JsonResponse
    {
        /** @var Team $team */
        $team = $request->attributes->get(AuthenticateYeastar::TEAM_ATTRIBUTE);

        /** @var array{linked_id: string, direction: string, disposition: string, started_at: int|string} $data */
        $data = $request->validated();

        $log = $this->logPhoneSystemCall->handle($team, $data);

        return response()->json(['id' => (string) $log->id], $log->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Store the recording link once the PBX has it. Recordings are kept as links only.
     */
    public function update(UpdateCallJournalRequest $request, string $log): JsonResponse
    {
        /** @var Team $team */
        $team = $request->attributes->get(AuthenticateYeastar::TEAM_ATTRIBUTE);

        $pbxCallLog = PbxCallLog::query()->where('team_id', $team->id)->findOrFail($log);

        if ($request->filled('recording_url')) {
            $pbxCallLog->update(['recording_url' => $request->string('recording_url')->toString()]);
        }

        return response()->json(['id' => (string) $pbxCallLog->id]);
    }
}

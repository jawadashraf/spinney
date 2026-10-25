<?php

declare(strict_types=1);

namespace App\Actions\Calls;

use App\Models\Company;
use App\Models\PbxCallLog;
use App\Models\People;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final readonly class LinkPhoneSystemCalls
{
    /**
     * Link a call from an unknown number, and every other unmatched call from that number, to a person
     * or company. Only links the calls: a past call never changes a liaison call. With $saveNumber the
     * number is also saved on a contact who has none, so their future calls match on their own.
     *
     * @return int The number of calls linked.
     */
    public function handle(PbxCallLog $log, People|Company $contact, bool $saveNumber = false): int
    {
        return DB::transaction(function () use ($log, $contact, $saveNumber): int {
            $calls = PbxCallLog::query()
                ->unmatched()
                ->where('team_id', $log->team_id)
                ->where(fn (Builder $query): Builder => $query
                    ->whereKey($log->id)
                    ->when(filled($log->contact_number), fn (Builder $query): Builder => $query->orWhere('contact_number', $log->contact_number)));

            $linked = $calls->count();

            if ($saveNumber && blank($contact->phone) && filled($log->contact_number)) {
                $contact->update(['phone' => $log->contact_number]);
            }

            $calls->update($contact instanceof Company ? ['company_id' => $contact->id] : ['people_id' => $contact->id]);

            return $linked;
        });
    }
}

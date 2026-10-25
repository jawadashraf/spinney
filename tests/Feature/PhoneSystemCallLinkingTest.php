<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Enquiry;
use App\Models\PbxCallLog;
use App\Models\People;
use App\Models\Team;

beforeEach(function () {
    $this->team = Team::query()->where('name', 'Spinney Hill')->firstOrFail();
});

it('links earlier unmatched calls when a person is saved with that number', function () {
    $calls = PbxCallLog::factory()->count(2)->create(['team_id' => $this->team->id, 'contact_number' => '+447438356414']);

    $relative = People::factory()->create(['team_id' => $this->team->id, 'type' => 'relative', 'phone' => '07438 356414']);

    expect($calls->map->fresh()->pluck('people_id')->all())->toBe([$relative->id, $relative->id]);
});

it('links earlier unmatched calls when a number is added to an existing company', function () {
    $company = Company::factory()->create(['team_id' => $this->team->id, 'phone' => null]);
    $call = PbxCallLog::factory()->create(['team_id' => $this->team->id, 'contact_number' => '+447438356414']);

    $company->update(['phone' => '07438 356414']);

    expect($call->fresh()->company_id)->toBe($company->id);
});

it('does not move calls already linked to someone else', function () {
    $other = People::factory()->create(['team_id' => $this->team->id]);
    $linked = PbxCallLog::factory()->create(['team_id' => $this->team->id, 'contact_number' => '+447438356414', 'people_id' => $other->id]);
    $enquiryCall = PbxCallLog::factory()->create(['team_id' => $this->team->id, 'contact_number' => '+447438356414', 'enquiry_id' => Enquiry::factory()->create()->id]);

    People::factory()->create(['team_id' => $this->team->id, 'phone' => '+447438356414']);

    expect($linked->fresh()->people_id)->toBe($other->id)
        ->and($enquiryCall->fresh()->people_id)->toBeNull();
});

it('does not link calls from another team', function () {
    $call = PbxCallLog::factory()->create(['team_id' => Team::factory()->create()->id, 'contact_number' => '+447438356414']);

    People::factory()->create(['team_id' => $this->team->id, 'phone' => '+447438356414']);

    expect($call->fresh()->people_id)->toBeNull();
});

it('deletes unmatched calls older than the retention period and keeps everything else', function () {
    config(['services.yeastar.unmatched_retention_days' => 90]);
    $person = People::factory()->create(['team_id' => $this->team->id]);

    $oldUnmatched = PbxCallLog::factory()->create(['team_id' => $this->team->id, 'started_at' => now()->subDays(91)]);
    $recentUnmatched = PbxCallLog::factory()->create(['team_id' => $this->team->id, 'started_at' => now()->subDays(89)]);
    $oldLinked = PbxCallLog::factory()->create(['team_id' => $this->team->id, 'started_at' => now()->subDays(200), 'people_id' => $person->id]);
    $oldEnquiry = PbxCallLog::factory()->create(['team_id' => $this->team->id, 'started_at' => now()->subDays(200), 'enquiry_id' => Enquiry::factory()->create()->id]);

    $this->artisan('model:prune', ['--model' => [PbxCallLog::class]])->assertSuccessful();

    expect(PbxCallLog::query()->pluck('id')->sort()->values()->all())
        ->toBe(collect([$recentUnmatched->id, $oldLinked->id, $oldEnquiry->id])->sort()->values()->all())
        ->and(PbxCallLog::query()->find($oldUnmatched->id))->toBeNull();
});

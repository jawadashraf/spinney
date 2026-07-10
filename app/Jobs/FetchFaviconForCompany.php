<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Company;
use App\Models\CustomField;
use AshAllenDesign\FaviconFetcher\Facades\Favicon;
use Exception;
use Illuminate\Contracts\Broadcasting\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;

#[DeleteWhenMissingModels]
final class FetchFaviconForCompany implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public readonly Company $company)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $customFieldDomain = CustomField::query()
                ->whereBelongsTo($this->company->team)
                ->where('entity_type', $this->company::class)
                ->where('code', 'domain_name')
                ->first();
            $domainName = $this->company->getCustomFieldValue($customFieldDomain);

            if ($domainName === null) {
                return;
            }

            $favicon = Favicon::driver('high-quality')->fetch($domainName);
            $url = $favicon?->getFaviconUrl();

            if ($url === null) {
                return;
            }

            $path = parse_url($url, PHP_URL_PATH);
            $extension = $path ? pathinfo($path, PATHINFO_EXTENSION) : '';

            $filename = match ($extension) {
                'svg' => 'logo.svg',
                'png' => 'logo.png',
                'webp' => 'logo.webp',
                'jpg', 'jpeg' => 'logo.jpg',
                default => 'logo.png',
            };

            $logo = $this->company
                ->addMediaFromUrl($url)
                ->usingFileName($filename)
                ->usingName('company_logo')
                ->withCustomProperties([
                    'original_size' => $favicon->getIconSize(),
                    'icon_type' => $favicon->getIconType(),
                    'fetched_at' => now()->toIso8601String(),
                ])
                ->toMediaCollection('logo');

            $this->company->clearMediaCollectionExcept('logo', $logo);
        } catch (Exception $exception) {
            report($exception);
        }
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return (string) $this->company->getKey();
    }
}

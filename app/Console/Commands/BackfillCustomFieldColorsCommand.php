<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CustomFields\OpportunityField as OpportunityCustomField;
use App\Enums\CustomFields\TaskField as TaskCustomField;
use App\Models\CustomField;
use Illuminate\Console\Command;

final class BackfillCustomFieldColorsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'custom-fields:backfill-colors
                            {--team= : Specific team ID to backfill (optional)}
                            {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill colors for existing custom field options (Task status/priority and Opportunity stages)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🎨 Backfilling custom field colors...');

        $dryRun = $this->option('dry-run');
        $specificTeam = $this->option('team');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        }

        // Get fields to update
        $query = CustomField::with('options')
            ->whereIn('name', ['Status', 'Priority', 'Stage'])
            ->whereIn('entity_type', [\App\Models\Task::class, \App\Models\Opportunity::class])
            ->where('type', 'select');

        if ($specificTeam) {
            $query->where('team_id', $specificTeam);
        }

        $fields = $query->get();

        $this->info("Found {$fields->count()} fields to process");

        $updatedFields = 0;
        $updatedOptions = 0;

        foreach ($fields as $field) {
            $colorMapping = $this->getColorMappingForField($field);

            if ($colorMapping === null) {
                continue;
            }

            $this->info("Processing: {$field->name} for {$field->entity_type} (Team {$field->team_id})");

            // Enable colors on the field if not already enabled
            $settings = (array) $field->settings;
            if (! ($settings['enable_option_colors'] ?? false)) {
                if (! $dryRun) {
                    $settings['enable_option_colors'] = true;
                    $field->update([
                        'settings' => $settings,
                    ]);
                }
                $this->line('  ✓ Enabled color options for field');
                $updatedFields++;
            } else {
                $this->line('  ℹ Field already has color options enabled');
            }

            // Apply colors to options
            foreach ($field->options as $option) {
                $color = $colorMapping[$option->name] ?? null;
                if ($color !== null) {
                    $optionSettings = (array) $option->settings;
                    $currentColor = $optionSettings['color'] ?? null;
                    if ($currentColor !== $color) {
                        if (! $dryRun) {
                            $optionSettings['color'] = $color;
                            $option->update([
                                'settings' => $optionSettings,
                            ]);
                        }
                        $this->line("  ✓ Set color for '{$option->name}': $color");
                        $updatedOptions++;
                    } else {
                        $this->line("  ℹ '{$option->name}' already has correct color: $color");
                    }
                } else {
                    $this->line("  ⚠ No color mapping found for option: '{$option->name}'");
                }
            }
        }

        if ($dryRun) {
            $this->info('🔍 DRY RUN COMPLETE:');
            $this->info("  - Would enable colors on $updatedFields fields");
            $this->info("  - Would update colors on $updatedOptions options");
        } else {
            $this->info('✅ BACKFILL COMPLETE:');
            $this->info("  - Enabled colors on $updatedFields fields");
            $this->info("  - Updated colors on $updatedOptions options");
        }

        return self::SUCCESS;
    }

    /**
     * Get color mapping for a field based on its configuration
     */
    /**
     * @return array<int|string, string>|null
     */
    private function getColorMappingForField(CustomField $field): ?array
    {
        return match ([$field->entity_type, $field->name]) {
            [\App\Models\Task::class, 'Status'] => TaskCustomField::STATUS->getOptionColors(),
            [\App\Models\Task::class, 'Priority'] => TaskCustomField::PRIORITY->getOptionColors(),
            [\App\Models\Opportunity::class, 'Stage'] => OpportunityCustomField::STAGE->getOptionColors(),
            default => null,
        };
    }
}

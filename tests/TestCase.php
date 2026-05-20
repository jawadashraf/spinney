<?php

declare(strict_types=1);

namespace Tests;

use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $configCache = dirname(__DIR__).'/bootstrap/cache/config.php';
        if (file_exists($configCache)) {
            @unlink($configCache);
        }

        return parent::createApplication();
    }

    #[\Override]
    public function actingAs(Authenticatable $user, $driver = null)
    {
        parent::actingAs($user, $driver);

        if ($user instanceof User) {
            $team = $user->currentTeam ?? Team::first();
            if ($team) {
                Filament::setTenant($team);
            }
        }

        return $this;
    }
}

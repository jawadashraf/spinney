<?php

declare(strict_types=1);

namespace Tests;

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
}

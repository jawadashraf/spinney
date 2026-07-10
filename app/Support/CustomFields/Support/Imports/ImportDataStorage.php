<?php

declare(strict_types=1);

namespace App\Support\CustomFields\Support\Imports;

use Illuminate\Database\Eloquent\Model;
use WeakMap;

final class ImportDataStorage
{
    /** @var WeakMap<object, array<string, mixed>>|null */
    private static ?WeakMap $storage = null;

    private static function init(): void
    {
        /** @var WeakMap<object, array<string, mixed>> $storage */
        $storage = new WeakMap;
        self::$storage ??= $storage;
    }

    public static function set(Model $record, string $fieldCode, mixed $value): void
    {
        self::init();

        $data = self::$storage[$record] ?? [];
        $data[$fieldCode] = $value;
        /** @var array<string, mixed> $cleanData */
        $cleanData = $data;
        self::$storage[$record] = $cleanData;
    }

    /**
     * @return array<string, mixed>
     */
    public static function pull(Model $record): array
    {
        self::init();

        $data = self::$storage[$record] ?? [];
        unset(self::$storage[$record]);

        return $data;
    }

    public static function has(Model $record): bool
    {
        self::init();

        return isset(self::$storage[$record]);
    }
}

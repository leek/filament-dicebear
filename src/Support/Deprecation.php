<?php

declare(strict_types=1);

namespace Leek\FilamentDiceBear\Support;

/**
 * Emits each 1.x compatibility notice once per process. Legacy options are
 * translated on every avatar render, so un-deduplicated notices would flood
 * Laravel's `deprecations` log channel.
 */
final class Deprecation
{
    /** @var array<string, true> */
    private static array $triggered = [];

    public static function trigger(string $message, mixed ...$args): void
    {
        $message = $args === [] ? $message : sprintf($message, ...$args);

        if (isset(self::$triggered[$message])) {
            return;
        }

        self::$triggered[$message] = true;

        trigger_deprecation('leek/filament-dicebear', '2.0', $message.' Support will be removed in 3.0.');
    }

    /** @internal For tests. */
    public static function reset(): void
    {
        self::$triggered = [];
    }
}

<?php

declare(strict_types=1);

namespace Leek\FilamentDiceBear;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class DiceBearServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-dicebear';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile();
    }
}

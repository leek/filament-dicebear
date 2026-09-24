<?php

declare(strict_types=1);

namespace Leek\FilamentDiceBear;

use Leek\FilamentDiceBear\Support\StyleRegistry;
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

    public function packageRegistered(): void
    {
        $this->app->singleton(StyleRegistry::class);
    }
}

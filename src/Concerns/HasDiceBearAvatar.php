<?php

declare(strict_types=1);

namespace Leek\FilamentDiceBear\Concerns;

use Leek\FilamentDiceBear\DiceBearPlugin;
use Leek\FilamentDiceBear\DiceBearProvider;
use Leek\FilamentDiceBear\Enums\DiceBearStyle;

/**
 * Adds DiceBear avatar support to Eloquent models implementing HasAvatar.
 *
 * Override `dicebearAvatarStyle()` and `dicebearAvatarOptions()` to customize
 * the avatar per model. Override `getCustomAvatarUrl()` to check for uploaded
 * photos before falling back to DiceBear.
 */
trait HasDiceBearAvatar
{
    public function getFilamentAvatarUrl(): ?string
    {
        return $this->getCustomAvatarUrl() ?? $this->getDiceBearAvatarUrl();
    }

    /**
     * Override this to return an uploaded photo URL when available.
     * Return null to fall back to DiceBear.
     */
    protected function getCustomAvatarUrl(): ?string
    {
        return null;
    }

    /**
     * Generate a DiceBear avatar URL for this model.
     */
    public function getDiceBearAvatarUrl(): string
    {
        $plugin = DiceBearPlugin::make()
            ->style($this->dicebearAvatarStyle())
            ->options($this->dicebearAvatarOptions());

        return app(DiceBearProvider::class)->get($this, $plugin);
    }

    /**
     * The DiceBear style to use for this model's avatars.
     */
    public function dicebearAvatarStyle(): DiceBearStyle
    {
        return DiceBearStyle::Initials;
    }

    /**
     * Style-specific options for this model's avatars.
     *
     * @return array<string, mixed>
     */
    public function dicebearAvatarOptions(): array
    {
        return [];
    }
}

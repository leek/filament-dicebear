<?php

declare(strict_types=1);

namespace Leek\FilamentDiceBear\Enums;

/**
 * Raster formats are only produced by the HTTP API; the local driver renders SVG.
 */
enum OutputFormat: string
{
    case Svg = 'svg';
    case Png = 'png';
    case Jpg = 'jpg';
    case Webp = 'webp';
    case Avif = 'avif';

    public function mimeType(): string
    {
        return match ($this) {
            self::Svg => 'image/svg+xml',
            self::Png => 'image/png',
            self::Jpg => 'image/jpeg',
            self::Webp => 'image/webp',
            self::Avif => 'image/avif',
        };
    }

    public function isRaster(): bool
    {
        return $this !== self::Svg;
    }
}

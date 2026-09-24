<?php

declare(strict_types=1);

namespace Leek\FilamentDiceBear\Enums;

/**
 * Values of the `animationVariant` option shared by DiceBear's animated styles.
 */
enum AnimationSpeed: string
{
    case None = 'none';
    case Slowest = 'slowest';
    case Slow = 'slow';
    case Medium = 'medium';
    case Fast = 'fast';
    case Fastest = 'fastest';
}

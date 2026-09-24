<?php

declare(strict_types=1);

namespace Leek\FilamentDiceBear\Enums;

enum RenderDriver: string
{
    /** Render in-process with the native `dicebear/core` PHP library. */
    case Local = 'local';

    /** Fetch from the DiceBear HTTP API (public or self-hosted). */
    case Http = 'http';
}

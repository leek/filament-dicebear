<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Driver
    |--------------------------------------------------------------------------
    |
    | 'local' renders avatars in-process with the native DiceBear PHP core
    | (no network calls, all options supported). 'http' fetches them from the
    | DiceBear HTTP API (public or self-hosted). Raster formats always use
    | the HTTP API.
    |
    */

    'driver' => 'local',

    /*
    |--------------------------------------------------------------------------
    | Output Format
    |--------------------------------------------------------------------------
    |
    | 'svg', 'png', 'jpg', 'webp' or 'avif'. Anything other than 'svg' is
    | rendered by the HTTP API.
    |
    */

    'format' => 'svg',

    /*
    |--------------------------------------------------------------------------
    | Default Style
    |--------------------------------------------------------------------------
    |
    | The DiceBear avatar style to use by default. Accepts a DiceBearStyle
    | enum value string (e.g., 'initials', 'adventurer', 'thumbs') or the
    | name of a custom style registered below.
    |
    | @see https://www.dicebear.com/styles/
    |
    */

    'style' => 'initials',

    /*
    |--------------------------------------------------------------------------
    | Custom Styles
    |--------------------------------------------------------------------------
    |
    | Map of style name => path to a DiceBear definition JSON file. Custom
    | styles are always rendered by the local driver.
    |
    | @see https://www.dicebear.com/specification/definition-schema/
    |
    */

    'custom_styles' => [
        // 'brand' => resource_path('avatars/brand.json'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Animation
    |--------------------------------------------------------------------------
    |
    | Animated styles (thumbs, glass, planets, ...) are static by default.
    | Enable to animate them at a seed-random speed, or pin a speed:
    | 'none', 'slowest', 'slow', 'medium', 'fast', 'fastest'.
    | Animations respect prefers-reduced-motion.
    |
    */

    'animation' => [
        'enabled' => false,
        'speed' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Core Options
    |--------------------------------------------------------------------------
    |
    | Any DiceBear core option by its v10 name, applied to every avatar.
    | Values may be fixed or [min, max] ranges where supported.
    |
    | @see https://www.dicebear.com/guides/core-options/
    |
    */

    'options' => [
        // 'size' => 128,
        // 'borderRadius' => 50,
        // 'scale' => 0.9,
        // 'flip' => 'horizontal',
        // 'backgroundColor' => ['b6e3f4', 'c0aede'],
        // 'backgroundColorFill' => 'linear',
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP API
    |--------------------------------------------------------------------------
    |
    | Used by the 'http' driver and as a fallback URL. Override the base URL
    | when using a self-hosted DiceBear instance.
    |
    | @see https://www.dicebear.com/guides/host-the-http-api-yourself/
    |
    */

    'api_version' => '10.x',

    'base_url' => 'https://api.dicebear.com',

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | When enabled, rendered avatars are stored on the configured disk. The
    | path is relative to the disk root.
    |
    */

    'cache' => [
        'enabled' => true,
        'disk' => 'public',
        'path' => 'avatars/dicebear',
    ],

];

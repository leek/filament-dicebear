<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Style
    |--------------------------------------------------------------------------
    |
    | The DiceBear avatar style to use by default. Accepts a DiceBearStyle
    | enum value string (e.g., 'initials', 'adventurer', 'thumbs').
    |
    | @see https://www.dicebear.com/styles/
    |
    */

    'style' => 'initials',

    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    |
    | The DiceBear API version segment used in the URL.
    |
    */

    'api_version' => '9.x',

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the DiceBear API. Override this when using a
    | self-hosted DiceBear instance.
    |
    | @see https://www.dicebear.com/guides/host-the-http-api-yourself/
    |
    */

    'base_url' => 'https://api.dicebear.com',

    /*
    |--------------------------------------------------------------------------
    | Universal Options
    |--------------------------------------------------------------------------
    |
    | These correspond to DiceBear's universal query parameters.
    | Set to null to omit from the request (uses DiceBear's defaults).
    |
    */

    'size' => null,

    'radius' => null,

    'scale' => null,

    'rotate' => null,

    'flip' => null,

    'background_color' => null,

    'background_type' => null,

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | When enabled, fetched SVGs are stored on the configured disk to avoid
    | repeated API calls. The path is relative to the disk root.
    |
    */

    'cache' => [
        'enabled' => true,
        'disk' => 'public',
        'path' => 'avatars/dicebear',
    ],

];

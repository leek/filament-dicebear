<?php

use DiceBear\Avatar;
use DiceBear\Style;
use Illuminate\Support\Facades\Cache;
use Leek\FilamentDiceBear\Support\StyleRegistry;

it('renders the same avatar from a definition cached by an earlier request', function () {
    $fresh = (string) new Avatar((new StyleRegistry)->get('thumbs'), ['seed' => 'cached']);

    $cachedKeys = array_filter(
        array_keys((fn () => $this->storage)->call(Cache::store()->getStore())),
        fn (string $key) => str_starts_with($key, 'filament-dicebear:style:'),
    );

    expect($cachedKeys)->toHaveCount(1);

    $fromCache = (string) new Avatar((new StyleRegistry)->get('thumbs'), ['seed' => 'cached']);

    expect($fromCache)->toBe($fresh);
});

it('validates the definition again when the cached entry is unusable', function () {
    (new StyleRegistry)->get('thumbs');

    $store = Cache::store()->getStore();
    $key = array_values(array_filter(
        array_keys((fn () => $this->storage)->call($store)),
        fn (string $key) => str_starts_with($key, 'filament-dicebear:style:'),
    ))[0];
    Cache::forever($key, 'O:8:"stdClass":0:{}');

    $svg = (string) new Avatar((new StyleRegistry)->get('thumbs'), ['seed' => 'cached']);

    expect($svg)->toStartWith('<svg')
        ->and(unserialize(Cache::get($key)))->toBeInstanceOf(Style::class);
});

it('skips the cache when definition caching is disabled', function () {
    config(['filament-dicebear.definition_cache.enabled' => false]);

    (new StyleRegistry)->get('thumbs');

    $keys = array_keys((fn () => $this->storage)->call(Cache::store()->getStore()));

    expect(array_filter($keys, fn (string $key) => str_starts_with($key, 'filament-dicebear:style:')))->toBeEmpty();
});

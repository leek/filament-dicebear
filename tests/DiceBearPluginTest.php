<?php

use Leek\FilamentDiceBear\DiceBearPlugin;
use Leek\FilamentDiceBear\Enums\DiceBearStyle;

it('has the correct plugin id', function () {
    expect(DiceBearPlugin::make()->getId())->toBe('dicebear');
});

it('defaults to initials style from config', function () {
    expect(DiceBearPlugin::make()->getStyle())->toBe(DiceBearStyle::Initials);
});

it('accepts style as enum', function () {
    $plugin = DiceBearPlugin::make()->style(DiceBearStyle::Adventurer);

    expect($plugin->getStyle())->toBe(DiceBearStyle::Adventurer);
});

it('accepts style as string', function () {
    $plugin = DiceBearPlugin::make()->style('thumbs');

    expect($plugin->getStyle())->toBe(DiceBearStyle::Thumbs);
});

it('builds correct API URL', function () {
    $plugin = DiceBearPlugin::make()->style(DiceBearStyle::Bottts);

    expect($plugin->buildApiUrl())->toBe('https://api.dicebear.com/9.x/bottts/svg');
});

it('builds API URL with custom base URL', function () {
    $plugin = DiceBearPlugin::make()
        ->baseUrl('https://dicebear.example.com')
        ->style(DiceBearStyle::Initials);

    expect($plugin->buildApiUrl())->toBe('https://dicebear.example.com/9.x/initials/svg');
});

it('builds API URL with custom version', function () {
    $plugin = DiceBearPlugin::make()
        ->apiVersion('8.x')
        ->style(DiceBearStyle::Rings);

    expect($plugin->buildApiUrl())->toBe('https://api.dicebear.com/8.x/rings/svg');
});

it('builds API URL with style override parameter', function () {
    $plugin = DiceBearPlugin::make()->style(DiceBearStyle::Initials);

    expect($plugin->buildApiUrl(DiceBearStyle::Adventurer))
        ->toBe('https://api.dicebear.com/9.x/adventurer/svg');
});

it('builds query params with seed only when no options set', function () {
    $plugin = DiceBearPlugin::make();

    expect($plugin->buildQueryParams('test-seed'))->toBe(['seed' => 'test-seed']);
});

it('builds query params with all universal options', function () {
    $plugin = DiceBearPlugin::make()
        ->size(128)
        ->radius(50)
        ->scale(80)
        ->rotate(45)
        ->flip()
        ->backgroundColor('ff0000')
        ->backgroundType('gradientLinear');

    $params = $plugin->buildQueryParams('my-seed');

    expect($params)->toBe([
        'seed' => 'my-seed',
        'size' => 128,
        'radius' => 50,
        'scale' => 80,
        'rotate' => 45,
        'flip' => 'true',
        'backgroundColor' => 'ff0000',
        'backgroundType' => 'gradientLinear',
    ]);
});

it('merges style-specific options', function () {
    $plugin = DiceBearPlugin::make()
        ->options(['eyes' => 'happy,hearts', 'mouth' => 'smile01']);

    $params = $plugin->buildQueryParams('seed');

    expect($params)->toBe([
        'seed' => 'seed',
        'eyes' => 'happy,hearts',
        'mouth' => 'smile01',
    ]);
});

it('merges extra options passed to buildQueryParams', function () {
    $plugin = DiceBearPlugin::make()
        ->options(['eyes' => 'happy']);

    $params = $plugin->buildQueryParams('seed', ['hair' => 'short01']);

    expect($params)->toBe([
        'seed' => 'seed',
        'eyes' => 'happy',
        'hair' => 'short01',
    ]);
});

it('falls back to config for cache settings', function () {
    $plugin = DiceBearPlugin::make();

    expect($plugin->getCache())->toBeTrue();
    expect($plugin->getDisk())->toBe('public');
    expect($plugin->getCachePath())->toBe('avatars/dicebear');
});

it('allows overriding cache settings', function () {
    $plugin = DiceBearPlugin::make()
        ->cache(false)
        ->disk('s3')
        ->cachePath('custom/path');

    expect($plugin->getCache())->toBeFalse();
    expect($plugin->getDisk())->toBe('s3');
    expect($plugin->getCachePath())->toBe('custom/path');
});

it('supports custom seed resolver', function () {
    $resolver = fn ($record) => 'custom-'.$record->email;
    $plugin = DiceBearPlugin::make()->seedUsing($resolver);

    expect($plugin->getSeedResolver())->toBe($resolver);
});

it('returns null for options not set', function () {
    $plugin = DiceBearPlugin::make();

    expect($plugin->getSize())->toBeNull();
    expect($plugin->getRadius())->toBeNull();
    expect($plugin->getScale())->toBeNull();
    expect($plugin->getRotate())->toBeNull();
    expect($plugin->getFlip())->toBeNull();
    expect($plugin->getBackgroundColor())->toBeNull();
    expect($plugin->getBackgroundType())->toBeNull();
});

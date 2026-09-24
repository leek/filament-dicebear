<?php

use Leek\FilamentDiceBear\DiceBearPlugin;
use Leek\FilamentDiceBear\Enums\AnimationSpeed;
use Leek\FilamentDiceBear\Enums\DiceBearStyle;
use Leek\FilamentDiceBear\Enums\OutputFormat;
use Leek\FilamentDiceBear\Enums\RenderDriver;

it('has the correct plugin id', function () {
    expect(DiceBearPlugin::make()->getId())->toBe('dicebear');
});

it('defaults to initials style from config', function () {
    expect(DiceBearPlugin::make()->getStyle())->toBe(DiceBearStyle::Initials);
});

it('accepts style as enum or string', function () {
    expect(DiceBearPlugin::make()->style(DiceBearStyle::Adventurer)->getStyle())->toBe(DiceBearStyle::Adventurer);
    expect(DiceBearPlugin::make()->style('thumbs')->getStyle())->toBe(DiceBearStyle::Thumbs);
});

it('falls back to initials for unknown style names', function () {
    expect(DiceBearPlugin::make()->style('nope')->getStyleName())->toBe('initials');
});

it('defaults to the local driver and svg', function () {
    $plugin = DiceBearPlugin::make();

    expect($plugin->getDriver())->toBe(RenderDriver::Local);
    expect($plugin->getFormat())->toBe(OutputFormat::Svg);
});

it('uses the http driver for raster formats', function () {
    expect(DiceBearPlugin::make()->format('png')->getDriver())->toBe(RenderDriver::Http);
});

it('forces local svg rendering for custom styles', function () {
    $plugin = DiceBearPlugin::make()
        ->driver('http')
        ->format(OutputFormat::Png)
        ->customStyle('brand', __DIR__.'/fixtures/brand.json');

    expect($plugin->getStyleName())->toBe('brand');
    expect($plugin->getStyle())->toBe(DiceBearStyle::Initials);
    expect($plugin->getDriver())->toBe(RenderDriver::Local);
    expect($plugin->getFormat())->toBe(OutputFormat::Svg);
});

it('resolves custom styles from config', function () {
    config()->set('filament-dicebear.custom_styles.brand', __DIR__.'/fixtures/brand.json');

    expect(DiceBearPlugin::make()->style('brand')->getStyleName())->toBe('brand');
});

it('builds v10 API URLs', function () {
    expect(DiceBearPlugin::make()->style(DiceBearStyle::Bottts)->buildApiUrl())
        ->toBe('https://api.dicebear.com/10.x/bottts/svg');

    expect(DiceBearPlugin::make()->baseUrl('https://dicebear.example.com/')->apiVersion('9.x')->format('webp')->buildApiUrl(DiceBearStyle::Rings))
        ->toBe('https://dicebear.example.com/9.x/rings/webp');
});

it('builds options with seed only when nothing is set', function () {
    expect(DiceBearPlugin::make()->buildOptions('test-seed'))->toBe(['seed' => 'test-seed']);
});

it('builds all core options with v10 names', function () {
    $plugin = DiceBearPlugin::make()
        ->size(128)
        ->borderRadius(50)
        ->scale(0.9)
        ->rotate([-10, 10])
        ->translateX(5)
        ->translateY(-5)
        ->flip()
        ->title('Avatar')
        ->idRandomization()
        ->fontFamily(['Inter', 'sans-serif'])
        ->fontWeight(700)
        ->tags('!mood:negative')
        ->backgroundColor(['b6e3f4', 'c0aede'])
        ->backgroundColorFill('radial')
        ->backgroundColorFillStops(3)
        ->backgroundColorAngle([0, 90])
        ->backgroundColorOrder('fixed');

    expect($plugin->buildOptions('s'))->toBe([
        'seed' => 's',
        'size' => 128,
        'borderRadius' => 50,
        'scale' => 0.9,
        'rotate' => [-10, 10],
        'translateX' => 5,
        'translateY' => -5,
        'flip' => 'horizontal',
        'title' => 'Avatar',
        'idRandomization' => true,
        'fontFamily' => ['Inter', 'sans-serif'],
        'fontWeight' => 700,
        'tags' => '!mood:negative',
        'backgroundColor' => ['b6e3f4', 'c0aede'],
        'backgroundColorFill' => 'radial',
        'backgroundColorFillStops' => 3,
        'backgroundColorAngle' => [0, 90],
        'backgroundColorOrder' => 'fixed',
    ]);
});

it('translates deprecated v9 methods and values', function () {
    $plugin = DiceBearPlugin::make()
        ->radius(20)
        ->backgroundType('gradientLinear')
        ->flip(false);

    expect($plugin->buildOptions('s'))->toBe([
        'seed' => 's',
        'borderRadius' => 20,
        'backgroundColorFill' => 'linear',
        'flip' => 'none',
    ]);
});

it('reads legacy flat config keys', function () {
    config()->set('filament-dicebear.radius', 50);
    config()->set('filament-dicebear.background_type', 'gradientLinear');
    config()->set('filament-dicebear.flip', true);

    expect(DiceBearPlugin::make()->buildOptions('s'))->toBe([
        'seed' => 's',
        'borderRadius' => 50,
        'flip' => 'horizontal',
        'backgroundColorFill' => 'linear',
    ]);
});

it('merges config options under plugin options', function () {
    config()->set('filament-dicebear.options', ['size' => 64, 'borderRadius' => 10]);

    expect(DiceBearPlugin::make()->size(128)->buildOptions('s'))->toBe([
        'seed' => 's',
        'size' => 128,
        'borderRadius' => 10,
    ]);
});

it('enables animation via the animation tag', function () {
    expect(DiceBearPlugin::make()->animated()->buildOptions('s'))
        ->toBe(['seed' => 's', 'tags' => ['animation']]);

    expect(DiceBearPlugin::make()->tags(['!mood:negative'])->animated()->buildOptions('s')['tags'])
        ->toBe(['!mood:negative', 'animation']);
});

it('keeps the animation tag when style options set tags', function () {
    $plugin = DiceBearPlugin::make()->animated()->options(['tags' => ['!mood:negative']]);

    expect($plugin->buildOptions('s')['tags'])->toBe(['!mood:negative', 'animation']);
    expect($plugin->buildOptions('s', ['tags' => 'hairLength:long,!eyewear'])['tags'])
        ->toBe(['hairLength:long', '!eyewear', 'animation']);
});

it('lets an explicit animationVariant option win over animationSpeed', function () {
    $plugin = DiceBearPlugin::make()->animationSpeed('slow')->options(['animationVariant' => 'fast']);

    expect($plugin->buildOptions('s')['animationVariant'])->toBe('fast');
});

it('pins animation speed via animationVariant', function () {
    expect(DiceBearPlugin::make()->animated()->animationSpeed(AnimationSpeed::Slow)->buildOptions('s'))
        ->toBe(['seed' => 's', 'animationVariant' => 'slow']);

    expect(DiceBearPlugin::make()->animationSpeed('fastest')->buildOptions('s')['animationVariant'])->toBe('fastest');
});

it('reads animation settings from config', function () {
    config()->set('filament-dicebear.animation.enabled', true);

    expect(DiceBearPlugin::make()->isAnimated())->toBeTrue();
    expect(DiceBearPlugin::make()->animated(false)->buildOptions('s'))->toBe(['seed' => 's']);

    config()->set('filament-dicebear.animation.speed', 'medium');
    expect(DiceBearPlugin::make()->getAnimationSpeed())->toBe(AnimationSpeed::Medium);
});

it('merges style-specific and extra options', function () {
    $plugin = DiceBearPlugin::make()->options(['eyesVariant' => ['happy', 'hearts']]);

    expect($plugin->buildOptions('s', ['mouthVariant' => 'smile01']))->toBe([
        'seed' => 's',
        'eyesVariant' => ['happy', 'hearts'],
        'mouthVariant' => 'smile01',
    ]);
});

it('serializes options as HTTP query params', function () {
    $plugin = DiceBearPlugin::make()
        ->size(64)
        ->idRandomization()
        ->backgroundColor(['ff0000', '00ff00'])
        ->options(['hairVariant' => ['short01' => 2, 'long01' => 1]]);

    expect($plugin->buildQueryParams('s'))->toBe([
        'seed' => 's',
        'size' => 64,
        'idRandomization' => 'true',
        'backgroundColor' => 'ff0000,00ff00',
        'hairVariant' => 'short01,long01',
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

    expect(DiceBearPlugin::make()->seedUsing($resolver)->getSeedResolver())->toBe($resolver);
});

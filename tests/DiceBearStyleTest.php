<?php

use Leek\FilamentDiceBear\Enums\DiceBearStyle;
use Leek\FilamentDiceBear\Enums\StyleCategory;
use Leek\FilamentDiceBear\Support\StyleRegistry;

it('has 61 styles', function () {
    expect(DiceBearStyle::cases())->toHaveCount(61);
});

it('matches the definitions shipped by dicebear/styles', function () {
    $shipped = array_map(
        fn (string $file) => basename($file, '.json'),
        glob(StyleRegistry::definitionsPath().'/*.json'),
    );

    $enum = array_map(fn (DiceBearStyle $style) => $style->value, DiceBearStyle::cases());

    sort($shipped);
    sort($enum);

    expect($enum)->toBe($shipped);
});

it('flags exactly the styles that ship an animation component', function () {
    $registry = app(StyleRegistry::class);

    $animated = array_filter(
        DiceBearStyle::cases(),
        fn (DiceBearStyle $style) => isset($registry->describe($style->value)['animationVariant']),
    );

    expect(array_values($animated))->toEqualCanonicalizing(DiceBearStyle::animated());
});

it('derives labels from slugs', function (DiceBearStyle $style, string $label) {
    expect($style->label())->toBe($label);
})->with([
    [DiceBearStyle::Initials, 'Initials'],
    [DiceBearStyle::BotttsNeutral, 'Bottts Neutral'],
    [DiceBearStyle::PixelArtNeutral, 'Pixel Art Neutral'],
    [DiceBearStyle::InitialFace, 'Initial Face'],
    [DiceBearStyle::VoxelBot, 'Voxel Bot'],
]);

it('categorizes styles like dicebear.com', function () {
    expect(DiceBearStyle::Glass->category())->toBe(StyleCategory::Minimalist);
    expect(DiceBearStyle::Waves->isMinimalist())->toBeTrue();
    expect(DiceBearStyle::Thumbs->category())->toBe(StyleCategory::Characters);
    expect(DiceBearStyle::Adventurer->isMinimalist())->toBeFalse();
    expect(DiceBearStyle::Planets->category())->toBe(StyleCategory::Scenes);

    expect(array_filter(DiceBearStyle::cases(), fn ($s) => $s->isMinimalist()))->toHaveCount(20);
    expect(array_filter(DiceBearStyle::cases(), fn ($s) => $s->category() === StyleCategory::Scenes))->toHaveCount(3);
});

it('knows which styles are animated', function () {
    expect(DiceBearStyle::Thumbs->isAnimated())->toBeTrue();
    expect(DiceBearStyle::Planets->isAnimated())->toBeTrue();
    expect(DiceBearStyle::Initials->isAnimated())->toBeFalse();
    expect(DiceBearStyle::animated())->toHaveCount(19);
});

it('can be created from string value', function () {
    expect(DiceBearStyle::from('adventurer'))->toBe(DiceBearStyle::Adventurer);
    expect(DiceBearStyle::from('bottts-neutral'))->toBe(DiceBearStyle::BotttsNeutral);
    expect(DiceBearStyle::from('voxel-art'))->toBe(DiceBearStyle::VoxelArt);
});

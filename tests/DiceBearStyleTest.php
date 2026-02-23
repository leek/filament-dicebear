<?php

use Leek\FilamentDiceBear\Enums\DiceBearStyle;

it('has 31 styles', function () {
    expect(DiceBearStyle::cases())->toHaveCount(31);
});

it('has all expected style slugs', function (DiceBearStyle $style, string $expectedSlug) {
    expect($style->value)->toBe($expectedSlug);
})->with([
    [DiceBearStyle::Glass, 'glass'],
    [DiceBearStyle::Icons, 'icons'],
    [DiceBearStyle::Identicon, 'identicon'],
    [DiceBearStyle::Initials, 'initials'],
    [DiceBearStyle::Rings, 'rings'],
    [DiceBearStyle::Shapes, 'shapes'],
    [DiceBearStyle::Thumbs, 'thumbs'],
    [DiceBearStyle::Adventurer, 'adventurer'],
    [DiceBearStyle::AdventurerNeutral, 'adventurer-neutral'],
    [DiceBearStyle::Avataaars, 'avataaars'],
    [DiceBearStyle::AvataaarsNeutral, 'avataaars-neutral'],
    [DiceBearStyle::BigEars, 'big-ears'],
    [DiceBearStyle::BigEarsNeutral, 'big-ears-neutral'],
    [DiceBearStyle::BigSmile, 'big-smile'],
    [DiceBearStyle::Bottts, 'bottts'],
    [DiceBearStyle::BotttsNeutral, 'bottts-neutral'],
    [DiceBearStyle::Croodles, 'croodles'],
    [DiceBearStyle::CroodlesNeutral, 'croodles-neutral'],
    [DiceBearStyle::Dylan, 'dylan'],
    [DiceBearStyle::FunEmoji, 'fun-emoji'],
    [DiceBearStyle::Lorelei, 'lorelei'],
    [DiceBearStyle::LoreleiNeutral, 'lorelei-neutral'],
    [DiceBearStyle::Micah, 'micah'],
    [DiceBearStyle::Miniavs, 'miniavs'],
    [DiceBearStyle::Notionists, 'notionists'],
    [DiceBearStyle::NotionistsNeutral, 'notionists-neutral'],
    [DiceBearStyle::OpenPeeps, 'open-peeps'],
    [DiceBearStyle::Personas, 'personas'],
    [DiceBearStyle::PixelArt, 'pixel-art'],
    [DiceBearStyle::PixelArtNeutral, 'pixel-art-neutral'],
    [DiceBearStyle::ToonHead, 'toon-head'],
]);

it('returns labels for all styles', function () {
    foreach (DiceBearStyle::cases() as $style) {
        expect($style->label())->toBeString()->not->toBeEmpty();
    }
});

it('correctly identifies minimalist styles', function () {
    $minimalist = [
        DiceBearStyle::Glass,
        DiceBearStyle::Icons,
        DiceBearStyle::Identicon,
        DiceBearStyle::Initials,
        DiceBearStyle::Rings,
        DiceBearStyle::Shapes,
        DiceBearStyle::Thumbs,
    ];

    foreach ($minimalist as $style) {
        expect($style->isMinimalist())->toBeTrue();
    }

    expect(DiceBearStyle::Adventurer->isMinimalist())->toBeFalse();
    expect(DiceBearStyle::Bottts->isMinimalist())->toBeFalse();
    expect(DiceBearStyle::PixelArt->isMinimalist())->toBeFalse();
});

it('can be created from string value', function () {
    expect(DiceBearStyle::from('adventurer'))->toBe(DiceBearStyle::Adventurer);
    expect(DiceBearStyle::from('bottts-neutral'))->toBe(DiceBearStyle::BotttsNeutral);
    expect(DiceBearStyle::from('initials'))->toBe(DiceBearStyle::Initials);
});

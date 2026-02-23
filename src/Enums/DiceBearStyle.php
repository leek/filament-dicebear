<?php

declare(strict_types=1);

namespace Leek\FilamentDiceBear\Enums;

enum DiceBearStyle: string
{
    // Minimalist
    case Glass = 'glass';
    case Icons = 'icons';
    case Identicon = 'identicon';
    case Initials = 'initials';
    case Rings = 'rings';
    case Shapes = 'shapes';
    case Thumbs = 'thumbs';

    // Characters
    case Adventurer = 'adventurer';
    case AdventurerNeutral = 'adventurer-neutral';
    case Avataaars = 'avataaars';
    case AvataaarsNeutral = 'avataaars-neutral';
    case BigEars = 'big-ears';
    case BigEarsNeutral = 'big-ears-neutral';
    case BigSmile = 'big-smile';
    case Bottts = 'bottts';
    case BotttsNeutral = 'bottts-neutral';
    case Croodles = 'croodles';
    case CroodlesNeutral = 'croodles-neutral';
    case Dylan = 'dylan';
    case FunEmoji = 'fun-emoji';
    case Lorelei = 'lorelei';
    case LoreleiNeutral = 'lorelei-neutral';
    case Micah = 'micah';
    case Miniavs = 'miniavs';
    case Notionists = 'notionists';
    case NotionistsNeutral = 'notionists-neutral';
    case OpenPeeps = 'open-peeps';
    case Personas = 'personas';
    case PixelArt = 'pixel-art';
    case PixelArtNeutral = 'pixel-art-neutral';
    case ToonHead = 'toon-head';

    public function label(): string
    {
        return match ($this) {
            self::Glass => 'Glass',
            self::Icons => 'Icons',
            self::Identicon => 'Identicon',
            self::Initials => 'Initials',
            self::Rings => 'Rings',
            self::Shapes => 'Shapes',
            self::Thumbs => 'Thumbs',
            self::Adventurer => 'Adventurer',
            self::AdventurerNeutral => 'Adventurer Neutral',
            self::Avataaars => 'Avataaars',
            self::AvataaarsNeutral => 'Avataaars Neutral',
            self::BigEars => 'Big Ears',
            self::BigEarsNeutral => 'Big Ears Neutral',
            self::BigSmile => 'Big Smile',
            self::Bottts => 'Bottts',
            self::BotttsNeutral => 'Bottts Neutral',
            self::Croodles => 'Croodles',
            self::CroodlesNeutral => 'Croodles Neutral',
            self::Dylan => 'Dylan',
            self::FunEmoji => 'Fun Emoji',
            self::Lorelei => 'Lorelei',
            self::LoreleiNeutral => 'Lorelei Neutral',
            self::Micah => 'Micah',
            self::Miniavs => 'Miniavs',
            self::Notionists => 'Notionists',
            self::NotionistsNeutral => 'Notionists Neutral',
            self::OpenPeeps => 'Open Peeps',
            self::Personas => 'Personas',
            self::PixelArt => 'Pixel Art',
            self::PixelArtNeutral => 'Pixel Art Neutral',
            self::ToonHead => 'Toon Head',
        };
    }

    public function isMinimalist(): bool
    {
        return in_array($this, [
            self::Glass,
            self::Icons,
            self::Identicon,
            self::Initials,
            self::Rings,
            self::Shapes,
            self::Thumbs,
        ]);
    }
}

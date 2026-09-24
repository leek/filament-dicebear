<?php

declare(strict_types=1);

namespace Leek\FilamentDiceBear\Enums;

enum DiceBearStyle: string
{
    // Minimalist
    case Blobs = 'blobs';
    case Disco = 'disco';
    case Glass = 'glass';
    case Glyphs = 'glyphs';
    case Icons = 'icons';
    case Identicon = 'identicon';
    case InitialFace = 'initial-face';
    case Initials = 'initials';
    case Loops = 'loops';
    case Patchwork = 'patchwork';
    case Rings = 'rings';
    case ShapeGrid = 'shape-grid';
    case Shapes = 'shapes';
    case Slice = 'slice';
    case Squircles = 'squircles';
    case Stack = 'stack';
    case Stripes = 'stripes';
    case Triangles = 'triangles';
    case Waves = 'waves';
    case Weave = 'weave';

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
    case Cameo = 'cameo';
    case Clay = 'clay';
    case Critters = 'critters';
    case Croodles = 'croodles';
    case CroodlesNeutral = 'croodles-neutral';
    case Cutouts = 'cutouts';
    case Dylan = 'dylan';
    case FunEmoji = 'fun-emoji';
    case Gaze = 'gaze';
    case LineFace = 'line-face';
    case Lorelei = 'lorelei';
    case LoreleiNeutral = 'lorelei-neutral';
    case Marbles = 'marbles';
    case Micah = 'micah';
    case Miniavs = 'miniavs';
    case Moods = 'moods';
    case Notionists = 'notionists';
    case NotionistsNeutral = 'notionists-neutral';
    case OpenPeeps = 'open-peeps';
    case Personas = 'personas';
    case PixelArt = 'pixel-art';
    case PixelArtNeutral = 'pixel-art-neutral';
    case Pixelbot = 'pixelbot';
    case Shadows = 'shadows';
    case Sprouts = 'sprouts';
    case Thumbs = 'thumbs';
    case ToonHead = 'toon-head';
    case VoxelArt = 'voxel-art';
    case VoxelBot = 'voxel-bot';

    // Scenes
    case Constellation = 'constellation';
    case Landscape = 'landscape';
    case Planets = 'planets';

    public function label(): string
    {
        return ucwords(str_replace('-', ' ', $this->value));
    }

    public function category(): StyleCategory
    {
        return match ($this) {
            self::Blobs, self::Disco, self::Glass, self::Glyphs, self::Icons,
            self::Identicon, self::InitialFace, self::Initials, self::Loops,
            self::Patchwork, self::Rings, self::ShapeGrid, self::Shapes,
            self::Slice, self::Squircles, self::Stack, self::Stripes,
            self::Triangles, self::Waves, self::Weave => StyleCategory::Minimalist,

            self::Constellation, self::Landscape, self::Planets => StyleCategory::Scenes,

            default => StyleCategory::Characters,
        };
    }

    public function isMinimalist(): bool
    {
        return $this->category() === StyleCategory::Minimalist;
    }

    /**
     * Whether the style ships an opt-in animation (enabled via the `animation`
     * tag or an explicit `animationVariant`).
     */
    public function isAnimated(): bool
    {
        return in_array($this, self::animated(), true);
    }

    /**
     * @return list<self>
     */
    public static function animated(): array
    {
        return [
            self::Blobs,
            self::Clay,
            self::Constellation,
            self::Critters,
            self::Gaze,
            self::Glass,
            self::InitialFace,
            self::Landscape,
            self::Loops,
            self::Moods,
            self::Pixelbot,
            self::Planets,
            self::Shapes,
            self::Sprouts,
            self::Squircles,
            self::Thumbs,
            self::VoxelArt,
            self::VoxelBot,
            self::Waves,
        ];
    }
}

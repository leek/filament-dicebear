# Upgrade Guide

## From 1.x to 2.0

Version 2 targets [DiceBear v10](https://www.dicebear.com/guides/dicebear-for-ai-assistants/). Avatars are now rendered in-process by DiceBear's native PHP core instead of being fetched from the HTTP API.

### Rendering is local by default

`composer require` now installs `dicebear/core` and `dicebear/styles`. No network calls are made unless you opt back in:

```php
DiceBearPlugin::make()->driver('http')
```

### API version defaults to `10.x`

If you pinned `->apiVersion('9.x')` or `'api_version' => '9.x'`, update it. The v9 API ignores v10 option names without any error.

### Option names and values

Everything below still works in 2.x but is deprecated and **will be removed in 3.0**. Each use raises an `E_USER_DEPRECATED` notice once per process. To see them, point Laravel's `deprecations` log channel somewhere (`LOG_DEPRECATIONS_CHANNEL=single`).

The old methods still work but are deprecated:

| 1.x | 2.0 |
|-----|-----|
| `->radius(50)` | `->borderRadius(50)` |
| `->backgroundType('gradientLinear')` | `->backgroundColorFill('linear')` (`'solid'`, `'linear'`, `'radial'`) |
| `->flip()` | `->flip()` still works and means `'horizontal'`. It also accepts `'vertical'`, `'both'` or `'none'` |
| `->backgroundColor('ff0000,00ff00')` | `->backgroundColor(['ff0000', '00ff00'])` (comma strings still work) |

**`scale` changed units.** v9 used a percentage (`80`). v10 uses a factor from 0 to 10 (`0.8`). Update any `->scale()` calls. They are passed through unchanged, so `scale(80)` now means 80×.

**Style-specific options end in `Variant`.** For example `eyes` is now `eyesVariant` and `hair` is now `hairVariant`. With the local driver, old names are renamed for you when the style has a matching component. Use the new names anyway. Arrays are preferred over comma-separated strings.

The getters `getSize()`, `getRadius()`, `getScale()`, `getRotate()`, `getFlip()`, `getBackgroundColor()` and `getBackgroundType()` were removed. Use `getCoreOption('borderRadius')` or `getCoreOptions()` instead.

### Config file

If you published the config, re-publish it or merge in the new keys: `driver`, `format`, `custom_styles`, `animation` and `options`. The old flat keys (`size`, `radius`, `scale`, `rotate`, `flip`, `background_color`, `background_type`) are still read and translated, but new options belong in `options` under their v10 names.

### Styles

- 30 new styles were added (61 total). The enum's `label()` output is unchanged for existing styles.
- `isMinimalist()` now follows dicebear.com's categories. `Thumbs` is a character style in v10. Use `category()` for the full picture (Minimalist, Characters, Scenes).
- `HasDiceBearAvatar::dicebearAvatarStyle()` may now return a string for custom styles. Existing `: DiceBearStyle` overrides still work.

### Cache

Cache filenames now always include a hash covering the options and the DiceBear version, for example `avatars/dicebear/thumbs/42-1a2b3c4d5e6f.svg`. Old 1.x files are never served. Delete them with:

```php
Storage::disk('public')->deleteDirectory('avatars/dicebear');
```

### Errors

Invalid options with the local driver throw `DiceBear\Error\OptionsValidationError` internally. The provider calls `report()` on it and falls back to the HTTP API URL, so check your logs after upgrading.

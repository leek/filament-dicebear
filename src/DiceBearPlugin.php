<?php

declare(strict_types=1);

namespace Leek\FilamentDiceBear;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Leek\FilamentDiceBear\Enums\AnimationSpeed;
use Leek\FilamentDiceBear\Enums\DiceBearStyle;
use Leek\FilamentDiceBear\Enums\OutputFormat;
use Leek\FilamentDiceBear\Enums\RenderDriver;
use Leek\FilamentDiceBear\Support\Deprecation;

class DiceBearPlugin implements Plugin
{
    /**
     * v9 option names mapped to their v10 equivalents.
     *
     * @deprecated 2.0 Translation of v9 option names will be removed in 3.0.
     */
    protected const LEGACY_OPTIONS = [
        'radius' => 'borderRadius',
        'backgroundType' => 'backgroundColorFill',
    ];

    /**
     * Legacy config keys (v1 flat config) mapped to v10 option names.
     *
     * @deprecated 2.0 Reading the flat 1.x config keys will be removed in 3.0; use `options`.
     */
    protected const LEGACY_CONFIG_KEYS = [
        'size' => 'size',
        'radius' => 'borderRadius',
        'scale' => 'scale',
        'rotate' => 'rotate',
        'flip' => 'flip',
        'background_color' => 'backgroundColor',
        'background_type' => 'backgroundColorFill',
    ];

    protected DiceBearStyle|string|null $style = null;

    /** @var array<string, string> style name => definition file path */
    protected array $customStyles = [];

    protected ?RenderDriver $driver = null;

    protected ?OutputFormat $format = null;

    protected ?string $apiVersion = null;

    protected ?string $baseUrl = null;

    /** @var array<string, mixed> Core options keyed by their v10 name. */
    protected array $coreOptions = [];

    protected ?bool $animated = null;

    protected ?AnimationSpeed $animationSpeed = null;

    /** @var array<string, mixed> */
    protected array $options = [];

    protected ?Closure $seedResolver = null;

    protected ?string $disk = null;

    protected ?bool $cacheEnabled = null;

    protected ?string $cachePath = null;

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public function getId(): string
    {
        return 'dicebear';
    }

    public function register(Panel $panel): void
    {
        //
    }

    public function boot(Panel $panel): void
    {
        //
    }

    // ── Style ────────────────────────────────────────────────────────────

    public function style(DiceBearStyle|string $style): static
    {
        $this->style = $style;

        return $this;
    }

    /**
     * Register a custom style from a DiceBear definition JSON file and optionally
     * select it. Custom styles are always rendered by the local driver.
     *
     * @see https://www.dicebear.com/specification/definition-schema/
     */
    public function customStyle(string $name, string $definitionPath, bool $use = true): static
    {
        $this->customStyles[$name] = $definitionPath;

        if ($use) {
            $this->style = $name;
        }

        return $this;
    }

    public function getCustomStylePath(?string $name = null): ?string
    {
        $name ??= $this->getStyleName();

        return $this->customStyles[$name]
            ?? config("filament-dicebear.custom_styles.{$name}");
    }

    /**
     * The built-in style in use. Falls back to Initials for custom or unknown names;
     * use getStyleName() for the exact name.
     */
    public function getStyle(): DiceBearStyle
    {
        return DiceBearStyle::tryFrom($this->getStyleName()) ?? DiceBearStyle::Initials;
    }

    public function getStyleName(): string
    {
        $style = $this->style ?? config('filament-dicebear.style', 'initials');

        if ($style instanceof DiceBearStyle) {
            return $style->value;
        }

        if (DiceBearStyle::tryFrom($style) !== null || $this->getCustomStylePath($style) !== null) {
            return $style;
        }

        return DiceBearStyle::Initials->value;
    }

    // ── Rendering ────────────────────────────────────────────────────────

    public function driver(RenderDriver|string $driver): static
    {
        $this->driver = $driver instanceof RenderDriver ? $driver : RenderDriver::from($driver);

        return $this;
    }

    /**
     * The driver that will actually render: raster formats require the HTTP
     * API, and custom styles require the local core.
     */
    public function getDriver(): RenderDriver
    {
        if ($this->getCustomStylePath() !== null) {
            return RenderDriver::Local;
        }

        if ($this->getFormat()->isRaster()) {
            return RenderDriver::Http;
        }

        return $this->driver ?? RenderDriver::tryFrom((string) config('filament-dicebear.driver', 'local')) ?? RenderDriver::Local;
    }

    /**
     * Output format. PNG, JPG, WebP and AVIF are rendered by the HTTP API.
     */
    public function format(OutputFormat|string $format): static
    {
        $this->format = $format instanceof OutputFormat ? $format : OutputFormat::from($format);

        return $this;
    }

    public function getFormat(): OutputFormat
    {
        if ($this->getCustomStylePath() !== null) {
            return OutputFormat::Svg;
        }

        return $this->format ?? OutputFormat::tryFrom((string) config('filament-dicebear.format', 'svg')) ?? OutputFormat::Svg;
    }

    // ── HTTP API ─────────────────────────────────────────────────────────

    public function apiVersion(string $version): static
    {
        $this->apiVersion = $version;

        return $this;
    }

    public function getApiVersion(): string
    {
        return $this->apiVersion ?? config('filament-dicebear.api_version', '10.x');
    }

    public function baseUrl(string $url): static
    {
        $this->baseUrl = $url;

        return $this;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl ?? config('filament-dicebear.base_url', 'https://api.dicebear.com');
    }

    // ── Core Options ─────────────────────────────────────────────────────
    //
    // Options typed `int|float|array` accept a fixed value or a [min, max]
    // range the PRNG samples from. List options accept an array to randomize.
    //
    // @see https://www.dicebear.com/guides/core-options/

    /** Output size in pixels (1–4096). */
    public function size(int $size): static
    {
        return $this->setCoreOption('size', $size);
    }

    /** Border radius in percent of the canvas (0–50; 50 is a circle). */
    public function borderRadius(int|float|array $radius): static
    {
        return $this->setCoreOption('borderRadius', $radius);
    }

    /**
     * @deprecated 2.0 Use borderRadius(). Will be removed in 3.0.
     */
    public function radius(int|float|array $radius): static
    {
        Deprecation::trigger('DiceBearPlugin::radius() is deprecated, use borderRadius() instead.');

        return $this->borderRadius($radius);
    }

    /** Uniform scale factor (0–10; 1 is original size). */
    public function scale(int|float|array $scale): static
    {
        return $this->setCoreOption('scale', $scale);
    }

    /** Rotation in degrees (−360–360). */
    public function rotate(int|float|array $degrees): static
    {
        return $this->setCoreOption('rotate', $degrees);
    }

    /** Horizontal translation in percent of the canvas width (−1000–1000). */
    public function translateX(int|float|array $percent): static
    {
        return $this->setCoreOption('translateX', $percent);
    }

    /** Vertical translation in percent of the canvas height (−1000–1000). */
    public function translateY(int|float|array $percent): static
    {
        return $this->setCoreOption('translateY', $percent);
    }

    /**
     * 'none', 'horizontal', 'vertical' or 'both' (or a list to randomize).
     * `true` means horizontal, `false` means none.
     *
     * @param  bool|string|list<string>  $flip
     */
    public function flip(bool|string|array $flip = true): static
    {
        return $this->setCoreOption('flip', $flip);
    }

    /**
     * Accessible title. The SVG gets role="img" and a <title> element.
     * Not supported by the public HTTP API.
     */
    public function title(string $title): static
    {
        return $this->setCoreOption('title', $title);
    }

    /**
     * Suffix SVG ids with a random value to avoid collisions when several inline
     * avatars share a page. Makes output non-deterministic.
     * Not supported by the public HTTP API.
     */
    public function idRandomization(bool $enabled = true): static
    {
        return $this->setCoreOption('idRandomization', $enabled);
    }

    /**
     * Font family for text-based styles (e.g. initials). Not supported by the public HTTP API.
     *
     * @param  string|list<string>  $family
     */
    public function fontFamily(string|array $family): static
    {
        return $this->setCoreOption('fontFamily', $family);
    }

    /**
     * Font weight (1–1000) for text-based styles. Not supported by the public HTTP API.
     *
     * @param  int|list<int>  $weight
     */
    public function fontWeight(int|array $weight): static
    {
        return $this->setCoreOption('fontWeight', $weight);
    }

    /**
     * Filter variants by tag: `category` or `category:value`, prefix `!` to disallow.
     *
     * @see https://www.dicebear.com/guides/filter-variants-with-tags/
     *
     * @param  string|list<string>  $tags
     */
    public function tags(string|array $tags): static
    {
        return $this->setCoreOption('tags', $tags);
    }

    // ── Background ───────────────────────────────────────────────────────

    /**
     * Hex color(s), `#` optional. Several colors are picked from by seed.
     *
     * @param  string|list<string>  $color
     */
    public function backgroundColor(string|array $color): static
    {
        return $this->setCoreOption('backgroundColor', $color);
    }

    /**
     * 'solid', 'linear' or 'radial' (or a list to randomize).
     *
     * @param  string|list<string>  $fill
     */
    public function backgroundColorFill(string|array $fill): static
    {
        return $this->setCoreOption('backgroundColorFill', $fill);
    }

    /**
     * @deprecated 2.0 Use backgroundColorFill(). 'gradientLinear' maps to 'linear'. Will be removed in 3.0.
     */
    public function backgroundType(string $type): static
    {
        Deprecation::trigger('DiceBearPlugin::backgroundType() is deprecated, use backgroundColorFill() instead.');

        return $this->backgroundColorFill($type === 'gradientLinear' ? 'linear' : $type);
    }

    /** Number of gradient stops (minimum 2). */
    public function backgroundColorFillStops(int|array $stops): static
    {
        return $this->setCoreOption('backgroundColorFillStops', $stops);
    }

    /** Gradient angle in degrees (−360–360). */
    public function backgroundColorAngle(int|float|array $degrees): static
    {
        return $this->setCoreOption('backgroundColorAngle', $degrees);
    }

    /** 'random' (default) or 'fixed' to use the given colors in order. */
    public function backgroundColorOrder(string $order): static
    {
        return $this->setCoreOption('backgroundColorOrder', $order);
    }

    // ── Animation ────────────────────────────────────────────────────────

    /**
     * Turn on the opt-in animation of animated styles at a seed-random speed.
     * Styles without animation are unaffected. Honors prefers-reduced-motion.
     *
     * @see DiceBearStyle::animated()
     */
    public function animated(bool $animated = true): static
    {
        $this->animated = $animated;

        return $this;
    }

    public function isAnimated(): bool
    {
        return $this->animated ?? (bool) config('filament-dicebear.animation.enabled', false);
    }

    /**
     * Fix the animation speed. Takes precedence over animated().
     */
    public function animationSpeed(AnimationSpeed|string $speed): static
    {
        $this->animationSpeed = $speed instanceof AnimationSpeed ? $speed : AnimationSpeed::from($speed);

        return $this;
    }

    public function getAnimationSpeed(): ?AnimationSpeed
    {
        if ($this->animationSpeed !== null) {
            return $this->animationSpeed;
        }

        $configured = config('filament-dicebear.animation.speed');

        return $configured === null ? null : AnimationSpeed::tryFrom((string) $configured);
    }

    // ── Generic Options ──────────────────────────────────────────────────

    /**
     * Set any core option by its v10 name.
     */
    public function setCoreOption(string $key, mixed $value): static
    {
        $this->coreOptions[$key] = $value;

        return $this;
    }

    /**
     * A core option set on the plugin, falling back to config.
     */
    public function getCoreOption(string $key): mixed
    {
        return $this->getCoreOptions()[$key] ?? null;
    }

    /**
     * Core options from config and the plugin, translated to v10 names and values.
     *
     * @return array<string, mixed>
     */
    public function getCoreOptions(): array
    {
        $fromConfig = [];

        foreach (self::LEGACY_CONFIG_KEYS as $configKey => $option) {
            $value = config("filament-dicebear.{$configKey}");

            if ($value !== null) {
                Deprecation::trigger(
                    'The "filament-dicebear.%s" config key is deprecated, set "filament-dicebear.options.%s" instead.',
                    $configKey,
                    $option,
                );

                $fromConfig[$option] = $value;
            }
        }

        $fromConfig = array_merge($fromConfig, (array) config('filament-dicebear.options', []));

        return static::translateOptions(array_merge($fromConfig, $this->coreOptions));
    }

    /**
     * Style-specific options, e.g. ['eyesVariant' => ['happy', 'hearts'], 'skinColor' => 'f2d3b1'].
     *
     * @see https://www.dicebear.com/styles/
     *
     * @param  array<string, mixed>  $options
     */
    public function options(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    // ── Seed Resolver ────────────────────────────────────────────────────

    /**
     * Customize how the seed is derived from a model record.
     *
     * @param  Closure(mixed): string  $resolver
     */
    public function seedUsing(Closure $resolver): static
    {
        $this->seedResolver = $resolver;

        return $this;
    }

    public function getSeedResolver(): ?Closure
    {
        return $this->seedResolver;
    }

    // ── Caching ──────────────────────────────────────────────────────────

    public function cache(bool $enabled = true): static
    {
        $this->cacheEnabled = $enabled;

        return $this;
    }

    public function getCache(): bool
    {
        return $this->cacheEnabled ?? config('filament-dicebear.cache.enabled', true);
    }

    public function disk(string $disk): static
    {
        $this->disk = $disk;

        return $this;
    }

    public function getDisk(): string
    {
        return $this->disk ?? config('filament-dicebear.cache.disk', 'public');
    }

    public function cachePath(string $path): static
    {
        $this->cachePath = $path;

        return $this;
    }

    public function getCachePath(): string
    {
        return $this->cachePath ?? config('filament-dicebear.cache.path', 'avatars/dicebear');
    }

    // ── Building ─────────────────────────────────────────────────────────

    /**
     * Build the full DiceBear HTTP API URL for a given style.
     */
    public function buildApiUrl(DiceBearStyle|string|null $style = null): string
    {
        $resolvedStyle = $style instanceof DiceBearStyle
            ? $style->value
            : ($style ?? $this->getStyleName());

        return rtrim($this->getBaseUrl(), '/')
            .'/'.$this->getApiVersion()
            .'/'.$resolvedStyle
            .'/'.$this->getFormat()->value;
    }

    /**
     * Build the full option set (v10 names, native PHP types) for a seed.
     *
     * @param  array<string, mixed>  $extraOptions
     * @return array<string, mixed>
     */
    public function buildOptions(string $seed, array $extraOptions = []): array
    {
        $options = array_merge(['seed' => $seed], $this->getCoreOptions());

        if ($this->getAnimationSpeed() !== null) {
            $options['animationVariant'] = $this->getAnimationSpeed()->value;
        } elseif ($this->isAnimated()) {
            $tags = (array) ($options['tags'] ?? []);
            $options['tags'] = array_values(array_unique([...$tags, 'animation']));
        }

        return array_merge($options, static::translateOptions($this->getOptions()), static::translateOptions($extraOptions));
    }

    /**
     * Build the HTTP API query parameters for a seed. Lists are comma-joined;
     * variant weights are not expressible over HTTP and are dropped.
     *
     * @param  array<string, mixed>  $extraOptions
     * @return array<string, string|int|float>
     */
    public function buildQueryParams(string $seed, array $extraOptions = []): array
    {
        return array_map(static function (mixed $value): string|int|float {
            if (is_bool($value)) {
                return $value ? 'true' : 'false';
            }

            if (is_array($value)) {
                $items = array_is_list($value) ? $value : array_keys($value);

                return implode(',', array_map(fn ($item) => is_bool($item) ? ($item ? 'true' : 'false') : (string) $item, $items));
            }

            return $value;
        }, $this->buildOptions($seed, $extraOptions));
    }

    /**
     * Translate v9 option names and values to v10.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public static function translateOptions(array $options): array
    {
        $translated = [];

        foreach ($options as $key => $value) {
            if (isset(self::LEGACY_OPTIONS[$key])) {
                Deprecation::trigger('The "%s" option is deprecated, use "%s" instead.', $key, self::LEGACY_OPTIONS[$key]);

                $key = self::LEGACY_OPTIONS[$key];
            }

            if ($key === 'flip' && in_array($value, ['true', 'false', '1', '0'], true)) {
                Deprecation::trigger('Passing "%s" as the "flip" option is deprecated, use "horizontal" or "none" instead.', $value);
            }

            if ($key === 'backgroundColorFill' && $value === 'gradientLinear') {
                Deprecation::trigger('The "gradientLinear" background fill is deprecated, use "linear" instead.');
            }

            $translated[$key] = match ($key) {
                'flip' => match (true) {
                    $value === true, $value === 'true', $value === '1' => 'horizontal',
                    $value === false, $value === 'false', $value === '0' => 'none',
                    default => $value,
                },
                'backgroundColorFill' => $value === 'gradientLinear' ? 'linear' : $value,
                default => $value,
            };
        }

        return $translated;
    }
}

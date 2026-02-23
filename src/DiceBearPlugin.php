<?php

declare(strict_types=1);

namespace Leek\FilamentDiceBear;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Leek\FilamentDiceBear\Enums\DiceBearStyle;

class DiceBearPlugin implements Plugin
{
    protected DiceBearStyle|string|null $style = null;

    protected ?string $apiVersion = null;

    protected ?string $baseUrl = null;

    protected ?int $size = null;

    protected ?int $radius = null;

    protected ?int $scale = null;

    protected ?int $rotate = null;

    protected ?bool $flip = null;

    protected ?string $backgroundColor = null;

    protected ?string $backgroundType = null;

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

    public function getStyle(): DiceBearStyle
    {
        if ($this->style instanceof DiceBearStyle) {
            return $this->style;
        }

        if (is_string($this->style)) {
            return DiceBearStyle::from($this->style);
        }

        $configured = config('filament-dicebear.style', 'initials');

        return $configured instanceof DiceBearStyle
            ? $configured
            : DiceBearStyle::from($configured);
    }

    // ── API Version ──────────────────────────────────────────────────────

    public function apiVersion(string $version): static
    {
        $this->apiVersion = $version;

        return $this;
    }

    public function getApiVersion(): string
    {
        return $this->apiVersion ?? config('filament-dicebear.api_version', '9.x');
    }

    // ── Base URL ─────────────────────────────────────────────────────────

    public function baseUrl(string $url): static
    {
        $this->baseUrl = $url;

        return $this;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl ?? config('filament-dicebear.base_url', 'https://api.dicebear.com');
    }

    // ── Size ─────────────────────────────────────────────────────────────

    public function size(int $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size ?? config('filament-dicebear.size');
    }

    // ── Radius ───────────────────────────────────────────────────────────

    public function radius(int $radius): static
    {
        $this->radius = $radius;

        return $this;
    }

    public function getRadius(): ?int
    {
        return $this->radius ?? config('filament-dicebear.radius');
    }

    // ── Scale ────────────────────────────────────────────────────────────

    public function scale(int $scale): static
    {
        $this->scale = $scale;

        return $this;
    }

    public function getScale(): ?int
    {
        return $this->scale ?? config('filament-dicebear.scale');
    }

    // ── Rotate ───────────────────────────────────────────────────────────

    public function rotate(int $degrees): static
    {
        $this->rotate = $degrees;

        return $this;
    }

    public function getRotate(): ?int
    {
        return $this->rotate ?? config('filament-dicebear.rotate');
    }

    // ── Flip ─────────────────────────────────────────────────────────────

    public function flip(bool $flip = true): static
    {
        $this->flip = $flip;

        return $this;
    }

    public function getFlip(): ?bool
    {
        return $this->flip ?? config('filament-dicebear.flip');
    }

    // ── Background ───────────────────────────────────────────────────────

    public function backgroundColor(string $color): static
    {
        $this->backgroundColor = $color;

        return $this;
    }

    public function getBackgroundColor(): ?string
    {
        return $this->backgroundColor ?? config('filament-dicebear.background_color');
    }

    public function backgroundType(string $type): static
    {
        $this->backgroundType = $type;

        return $this;
    }

    public function getBackgroundType(): ?string
    {
        return $this->backgroundType ?? config('filament-dicebear.background_type');
    }

    // ── Extra Options ────────────────────────────────────────────────────

    /**
     * Style-specific passthrough options (e.g., eyes, hair, mouth).
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

    // ── URL Building ─────────────────────────────────────────────────────

    /**
     * Build the full DiceBear API URL for a given style.
     */
    public function buildApiUrl(DiceBearStyle|string|null $style = null): string
    {
        $resolvedStyle = $style instanceof DiceBearStyle
            ? $style->value
            : ($style ?? $this->getStyle()->value);

        return rtrim($this->getBaseUrl(), '/')
            .'/'.$this->getApiVersion()
            .'/'.$resolvedStyle
            .'/svg';
    }

    /**
     * Build the query parameters array for a DiceBear API call.
     *
     * @param  array<string, mixed>  $extraOptions
     * @return array<string, mixed>
     */
    public function buildQueryParams(string $seed, array $extraOptions = []): array
    {
        $params = ['seed' => $seed];

        if ($this->getSize() !== null) {
            $params['size'] = $this->getSize();
        }

        if ($this->getRadius() !== null) {
            $params['radius'] = $this->getRadius();
        }

        if ($this->getScale() !== null) {
            $params['scale'] = $this->getScale();
        }

        if ($this->getRotate() !== null) {
            $params['rotate'] = $this->getRotate();
        }

        if ($this->getFlip() === true) {
            $params['flip'] = 'true';
        }

        if ($this->getBackgroundColor() !== null) {
            $params['backgroundColor'] = $this->getBackgroundColor();
        }

        if ($this->getBackgroundType() !== null) {
            $params['backgroundType'] = $this->getBackgroundType();
        }

        return array_merge($params, $this->getOptions(), $extraOptions);
    }
}

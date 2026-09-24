<?php

declare(strict_types=1);

namespace Leek\FilamentDiceBear;

use DiceBear\Avatar;
use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Leek\FilamentDiceBear\Enums\RenderDriver;
use Leek\FilamentDiceBear\Support\StyleRegistry;

class DiceBearProvider implements AvatarProvider
{
    public function __construct(
        protected ?StyleRegistry $styles = null,
    ) {
        $this->styles ??= app(StyleRegistry::class);
    }

    public function get(Model|Authenticatable $record, ?DiceBearPlugin $plugin = null): string
    {
        $plugin ??= $this->resolvePlugin();
        $seed = $this->resolveSeed($record, $plugin);
        $options = $plugin->buildOptions($seed);

        $filename = $plugin->getCache() ? $this->cacheFilename($plugin, $options) : null;

        if ($filename !== null && ($cached = $this->getCached($plugin, $filename)) !== null) {
            return $cached;
        }

        $contents = $plugin->getDriver() === RenderDriver::Local
            ? $this->renderLocally($plugin, $options)
            : $this->fetchFromApi($plugin, $seed);

        if ($contents === null) {
            return $plugin->buildApiUrl().'?'.http_build_query($plugin->buildQueryParams($seed));
        }

        if ($filename !== null) {
            return $this->cacheAndReturn($plugin, $filename, $contents);
        }

        return $this->toDataUri($plugin, $contents);
    }

    /**
     * Render the SVG in-process with the native DiceBear PHP core.
     *
     * @param  array<string, mixed>  $options
     */
    protected function renderLocally(DiceBearPlugin $plugin, array $options): ?string
    {
        $name = $plugin->getStyleName();
        $path = $plugin->getCustomStylePath($name);

        try {
            $style = $this->styles->get($name, $path);

            return (string) new Avatar($style, $this->styles->normalize($name, $options, $path));
        } catch (\Throwable $e) {
            // Invalid options or definitions are configuration bugs; surface them
            // without breaking the page, then fall back to the HTTP API URL.
            report($e);

            return null;
        }
    }

    protected function fetchFromApi(DiceBearPlugin $plugin, string $seed): ?string
    {
        try {
            $response = Http::timeout(5)
                ->retry(2, 100)
                ->get($plugin->buildApiUrl(), $plugin->buildQueryParams($seed));

            return $response->successful() ? $response->body() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function resolvePlugin(): DiceBearPlugin
    {
        try {
            return DiceBearPlugin::get();
        } catch (\Throwable) {
            return DiceBearPlugin::make();
        }
    }

    protected function resolveSeed(Model|Authenticatable $record, DiceBearPlugin $plugin): string
    {
        $resolver = $plugin->getSeedResolver();

        if ($resolver !== null) {
            return (string) $resolver($record);
        }

        if ($record instanceof Model && isset($record->id)) {
            return (string) $record->id;
        }

        try {
            $name = Filament::getNameForDefaultAvatar($record);
        } catch (\Throwable) {
            $name = $record->name ?? $record->email ?? null;
        }

        return Str::slug($name ?: 'default');
    }

    protected function getCached(DiceBearPlugin $plugin, string $filename): ?string
    {
        try {
            if (Storage::disk($plugin->getDisk())->exists($filename)) {
                return Storage::disk($plugin->getDisk())->url($filename);
            }
        } catch (\Throwable) {
            // Storage failure — skip cache
        }

        return null;
    }

    protected function cacheAndReturn(DiceBearPlugin $plugin, string $filename, string $contents): string
    {
        try {
            Storage::disk($plugin->getDisk())->put($filename, $contents, [
                'ContentType' => $plugin->getFormat()->mimeType(),
            ]);

            return Storage::disk($plugin->getDisk())->url($filename);
        } catch (\Throwable) {
            return $this->toDataUri($plugin, $contents);
        }
    }

    /**
     * The hash covers the options and the renderer version, so upgrading
     * DiceBear or switching drivers never serves a stale avatar.
     *
     * @param  array<string, mixed>  $options
     */
    protected function cacheFilename(DiceBearPlugin $plugin, array $options): string
    {
        $seed = Str::slug((string) ($options['seed'] ?? 'default')) ?: 'default';
        $style = $plugin->getStyleName();

        $params = $options;
        unset($params['seed']);
        ksort($params);

        $renderer = $plugin->getDriver() === RenderDriver::Local
            ? $this->styles->version($plugin->getCustomStylePath($style))
            : 'http-'.$plugin->getBaseUrl().'-'.$plugin->getApiVersion();

        $hash = substr(md5(serialize([$renderer, $options['seed'] ?? '', $params])), 0, 12);

        return rtrim($plugin->getCachePath(), '/').'/'.$style.'/'.$seed.'-'.$hash.'.'.$plugin->getFormat()->value;
    }

    protected function toDataUri(DiceBearPlugin $plugin, string $contents): string
    {
        return 'data:'.$plugin->getFormat()->mimeType().';base64,'.base64_encode($contents);
    }
}

<?php

declare(strict_types=1);

namespace Leek\FilamentDiceBear;

use Exception;
use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Leek\FilamentDiceBear\Enums\DiceBearStyle;

class DiceBearProvider implements AvatarProvider
{
    public function get(Model|Authenticatable $record, ?DiceBearPlugin $plugin = null): string
    {
        $plugin ??= $this->resolvePlugin();
        $style = $plugin->getStyle();
        $seed = $this->resolveSeed($record, $plugin);

        if ($plugin->getCache()) {
            $cached = $this->getCached($plugin, $style, $seed);

            if ($cached !== null) {
                return $cached;
            }
        }

        $url = $plugin->buildApiUrl($style);
        $query = $plugin->buildQueryParams($seed);

        try {
            $response = Http::get($url, $query);

            if ($response->successful()) {
                if ($plugin->getCache()) {
                    return $this->cacheAndReturn($plugin, $style, $seed, $response->body());
                }

                return $this->toDataUri($response->body());
            }
        } catch (Exception) {
            // Silent fallback to direct URL
        }

        return $url.'?'.http_build_query($query);
    }

    protected function resolvePlugin(): DiceBearPlugin
    {
        try {
            return DiceBearPlugin::get();
        } catch (Exception) {
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
        } catch (Exception) {
            $name = $record->name ?? $record->email ?? null;
        }

        return Str::slug($name ?: 'default');
    }

    protected function getCached(DiceBearPlugin $plugin, DiceBearStyle $style, string $seed): ?string
    {
        $filename = $this->cacheFilename($plugin, $style, $seed);

        if (Storage::disk($plugin->getDisk())->exists($filename)) {
            return Storage::disk($plugin->getDisk())->url($filename);
        }

        return null;
    }

    protected function cacheAndReturn(DiceBearPlugin $plugin, DiceBearStyle $style, string $seed, string $svg): string
    {
        $filename = $this->cacheFilename($plugin, $style, $seed);

        Storage::disk($plugin->getDisk())->put($filename, $svg, [
            'ContentType' => 'image/svg+xml',
        ]);

        return Storage::disk($plugin->getDisk())->url($filename);
    }

    protected function cacheFilename(DiceBearPlugin $plugin, DiceBearStyle $style, string $seed): string
    {
        return rtrim($plugin->getCachePath(), '/').'/'.$style->value.'/'.$seed.'.svg';
    }

    protected function toDataUri(string $svg): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}

<?php

declare(strict_types=1);

namespace Leek\FilamentDiceBear;

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
        $query = $plugin->buildQueryParams($seed);

        if ($plugin->getCache()) {
            $cached = $this->getCached($plugin, $style, $query);

            if ($cached !== null) {
                return $cached;
            }
        }

        $url = $plugin->buildApiUrl($style);

        try {
            $response = Http::timeout(5)->retry(2, 100)->get($url, $query);

            if ($response->successful()) {
                if ($plugin->getCache()) {
                    return $this->cacheAndReturn($plugin, $style, $query, $response->body());
                }

                return $this->toDataUri($response->body());
            }
        } catch (\Throwable) {
            // Silent fallback to direct URL
        }

        return $url.'?'.http_build_query($query);
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

    /** @param array<string, mixed> $query */
    protected function getCached(DiceBearPlugin $plugin, DiceBearStyle $style, array $query): ?string
    {
        $filename = $this->cacheFilename($plugin, $style, $query);

        try {
            if (Storage::disk($plugin->getDisk())->exists($filename)) {
                return Storage::disk($plugin->getDisk())->url($filename);
            }
        } catch (\Throwable) {
            // Storage failure — skip cache
        }

        return null;
    }

    /** @param array<string, mixed> $query */
    protected function cacheAndReturn(DiceBearPlugin $plugin, DiceBearStyle $style, array $query, string $svg): string
    {
        $filename = $this->cacheFilename($plugin, $style, $query);

        try {
            Storage::disk($plugin->getDisk())->put($filename, $svg, [
                'ContentType' => 'image/svg+xml',
            ]);

            return Storage::disk($plugin->getDisk())->url($filename);
        } catch (\Throwable) {
            return $this->toDataUri($svg);
        }
    }

    /** @param array<string, mixed> $query */
    protected function cacheFilename(DiceBearPlugin $plugin, DiceBearStyle $style, array $query): string
    {
        $seed = Str::slug((string) ($query['seed'] ?? 'default')) ?: 'default';

        $params = $query;
        unset($params['seed']);
        ksort($params);

        $name = $params !== [] ? $seed.'-'.substr(md5(serialize($params)), 0, 8) : $seed;

        return rtrim($plugin->getCachePath(), '/').'/'.$style->value.'/'.$name.'.svg';
    }

    protected function toDataUri(string $svg): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}

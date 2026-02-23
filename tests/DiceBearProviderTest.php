<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Leek\FilamentDiceBear\DiceBearPlugin;
use Leek\FilamentDiceBear\DiceBearProvider;
use Leek\FilamentDiceBear\Enums\DiceBearStyle;

beforeEach(function () {
    Storage::fake('public');
});

it('fetches and caches SVG from DiceBear API', function () {
    Http::fake([
        'api.dicebear.com/*' => Http::response('<svg>test</svg>', 200),
    ]);

    $record = new class extends Model
    {
        protected $attributes = ['id' => 42];

        public $exists = true;
    };

    $plugin = DiceBearPlugin::make()->style(DiceBearStyle::Thumbs);
    $provider = new DiceBearProvider;

    $url = $provider->get($record, $plugin);

    expect($url)->toContain('/storage/avatars/dicebear/thumbs/42.svg');
    Storage::disk('public')->assertExists('avatars/dicebear/thumbs/42.svg');

    Http::assertSentCount(1);
});

it('returns cached SVG on subsequent calls', function () {
    Storage::disk('public')->put('avatars/dicebear/initials/99.svg', '<svg>cached</svg>');

    $record = new class extends Model
    {
        protected $attributes = ['id' => 99];

        public $exists = true;
    };

    $plugin = DiceBearPlugin::make()->style(DiceBearStyle::Initials);
    $provider = new DiceBearProvider;

    $url = $provider->get($record, $plugin);

    expect($url)->toContain('/storage/avatars/dicebear/initials/99.svg');
    Http::assertNothingSent();
});

it('returns data URI when caching is disabled', function () {
    Http::fake([
        'api.dicebear.com/*' => Http::response('<svg>inline</svg>', 200),
    ]);

    $record = new class extends Model
    {
        protected $attributes = ['id' => 1];

        public $exists = true;
    };

    $plugin = DiceBearPlugin::make()
        ->style(DiceBearStyle::Rings)
        ->cache(false);

    $provider = new DiceBearProvider;

    $url = $provider->get($record, $plugin);

    expect($url)->toStartWith('data:image/svg+xml;base64,');
    expect(base64_decode(str_replace('data:image/svg+xml;base64,', '', $url)))->toBe('<svg>inline</svg>');
    Storage::disk('public')->assertMissing('avatars/dicebear/rings/1.svg');
});

it('falls back to direct URL on API failure', function () {
    Http::fake([
        'api.dicebear.com/*' => Http::response('', 500),
    ]);

    $record = new class extends Model
    {
        protected $attributes = ['id' => 7];

        public $exists = true;
    };

    $plugin = DiceBearPlugin::make()->style(DiceBearStyle::Adventurer);
    $provider = new DiceBearProvider;

    $url = $provider->get($record, $plugin);

    expect($url)->toContain('api.dicebear.com/9.x/adventurer/svg');
    expect($url)->toContain('seed=7');
});

it('uses custom seed resolver', function () {
    Http::fake([
        'api.dicebear.com/*' => Http::response('<svg>custom</svg>', 200),
    ]);

    $record = new class extends Model
    {
        protected $attributes = ['id' => 1];

        public $email = 'test@example.com';

        public $exists = true;
    };

    $plugin = DiceBearPlugin::make()
        ->style(DiceBearStyle::Bottts)
        ->seedUsing(fn ($record) => $record->email);

    $provider = new DiceBearProvider;
    $provider->get($record, $plugin);

    Http::assertSent(fn ($request) => str_contains($request->url(), 'seed=test%40example.com')
        || str_contains($request->url(), 'seed=test@example.com'));
});

it('passes style-specific options to API', function () {
    Http::fake([
        'api.dicebear.com/*' => Http::response('<svg>opts</svg>', 200),
    ]);

    $record = new class extends Model
    {
        protected $attributes = ['id' => 5];

        public $exists = true;
    };

    $plugin = DiceBearPlugin::make()
        ->style(DiceBearStyle::BotttsNeutral)
        ->options(['eyes' => 'bulging,happy', 'mouth' => 'smile01']);

    $provider = new DiceBearProvider;
    $provider->get($record, $plugin);

    Http::assertSent(function ($request) {
        $url = $request->url();

        return str_contains($url, 'eyes=')
            && str_contains($url, 'mouth=');
    });
});

it('caches to custom disk and path', function () {
    Storage::fake('custom');

    Http::fake([
        'api.dicebear.com/*' => Http::response('<svg>custom-disk</svg>', 200),
    ]);

    $record = new class extends Model
    {
        protected $attributes = ['id' => 3];

        public $exists = true;
    };

    $plugin = DiceBearPlugin::make()
        ->style(DiceBearStyle::Glass)
        ->disk('custom')
        ->cachePath('my-avatars');

    $provider = new DiceBearProvider;
    $provider->get($record, $plugin);

    Storage::disk('custom')->assertExists('my-avatars/glass/3.svg');
});

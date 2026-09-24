<?php

use DiceBear\Error\OptionsValidationError;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Leek\FilamentDiceBear\DiceBearPlugin;
use Leek\FilamentDiceBear\DiceBearProvider;
use Leek\FilamentDiceBear\Enums\AnimationSpeed;
use Leek\FilamentDiceBear\Enums\DiceBearStyle;

beforeEach(function () {
    Storage::fake('public');
    Http::preventStrayRequests();
});

function record(int $id = 42): Model
{
    return new class(['id' => $id]) extends Model
    {
        protected $guarded = [];

        public $exists = true;

        public $email = 'test@example.com';
    };
}

function svgFrom(string $url): string
{
    return base64_decode(str_replace('data:image/svg+xml;base64,', '', $url));
}

// ── Local driver ─────────────────────────────────────────────────────────

it('renders locally and caches without network calls', function () {
    Http::fake();

    $url = (new DiceBearProvider)->get(record(), DiceBearPlugin::make()->style(DiceBearStyle::Thumbs));

    expect($url)->toMatch('#^/storage/avatars/dicebear/thumbs/42-[0-9a-f]{12}\.svg$#');

    $path = str_replace('/storage/', '', $url);
    Storage::disk('public')->assertExists($path);
    expect(Storage::disk('public')->get($path))->toStartWith('<svg');

    Http::assertNothingSent();
});

it('returns cached avatar on subsequent calls', function () {
    $plugin = DiceBearPlugin::make()->style(DiceBearStyle::Initials);
    $provider = new DiceBearProvider;

    $first = $provider->get(record(99), $plugin);
    Storage::disk('public')->put(str_replace('/storage/', '', $first), '<svg>cached</svg>');

    expect($provider->get(record(99), $plugin))->toBe($first);
    expect(Storage::disk('public')->get(str_replace('/storage/', '', $first)))->toBe('<svg>cached</svg>');
});

it('uses different cache files for different options', function () {
    $provider = new DiceBearProvider;

    $a = $provider->get(record(), DiceBearPlugin::make()->style('thumbs'));
    $b = $provider->get(record(), DiceBearPlugin::make()->style('thumbs')->animated());

    expect($a)->not->toBe($b);
});

it('returns data URI when caching is disabled', function () {
    $url = (new DiceBearProvider)->get(record(1), DiceBearPlugin::make()->style(DiceBearStyle::Rings)->cache(false));

    expect($url)->toStartWith('data:image/svg+xml;base64,');
    expect(svgFrom($url))->toStartWith('<svg');
    expect(Storage::disk('public')->allFiles())->toBeEmpty();
});

it('is deterministic per seed', function () {
    $plugin = DiceBearPlugin::make()->style(DiceBearStyle::Lorelei)->cache(false);
    $provider = new DiceBearProvider;

    expect($provider->get(record(1), $plugin))->toBe($provider->get(record(1), $plugin));
    expect($provider->get(record(1), $plugin))->not->toBe($provider->get(record(2), $plugin));
});

it('renders animated avatars when enabled', function () {
    $provider = new DiceBearProvider;
    $plugin = DiceBearPlugin::make()->style(DiceBearStyle::Thumbs)->cache(false);

    expect(svgFrom($provider->get(record(), $plugin)))->not->toContain('@keyframes');
    expect(svgFrom($provider->get(record(), (clone $plugin)->animated())))->toContain('@keyframes');
    expect(svgFrom($provider->get(record(), (clone $plugin)->animationSpeed(AnimationSpeed::Slow))))->toContain('@keyframes');
    expect(svgFrom($provider->get(record(), (clone $plugin)->animated()->animationSpeed(AnimationSpeed::None))))->not->toContain('@keyframes');
});

it('ignores animation on styles without one', function () {
    $url = (new DiceBearProvider)->get(record(), DiceBearPlugin::make()->style('initials')->animated()->cache(false));

    expect(svgFrom($url))->toStartWith('<svg')->not->toContain('@keyframes');
});

it('applies core options only the local driver supports', function () {
    $svg = svgFrom((new DiceBearProvider)->get(record(), DiceBearPlugin::make()
        ->style('initials')
        ->seedUsing(fn () => 'Jane Doe')
        ->title('Jane Doe')
        ->fontFamily('Inter')
        ->size(64)
        ->cache(false)));

    expect($svg)
        ->toContain('<title>Jane Doe</title>')
        ->toContain('role="img"')
        ->toContain('Inter')
        ->toContain('width="64"');
});

it('accepts HTTP-style and v9 option values locally', function () {
    Http::fake();

    $url = (new DiceBearProvider)->get(record(), DiceBearPlugin::make()
        ->style(DiceBearStyle::BotttsNeutral)
        ->radius(50)
        ->backgroundType('gradientLinear')
        ->options(['eyes' => 'bulging,happy', 'mouthProbability' => '50'])
        ->cache(false));

    expect($url)->toStartWith('data:image/svg+xml;base64,');
    Http::assertNothingSent();
});

it('reports invalid options and falls back to the API URL', function () {
    $reported = [];
    app(ExceptionHandler::class)->reportable(function (Throwable $e) use (&$reported) {
        $reported[] = $e;
    });

    $url = (new DiceBearProvider)->get(record(7), DiceBearPlugin::make()
        ->style(DiceBearStyle::Adventurer)
        ->options(['notAnOption' => true]));

    expect($url)->toStartWith('https://api.dicebear.com/10.x/adventurer/svg?')->toContain('seed=7');
    expect($reported)->toHaveCount(1);
    expect($reported[0])->toBeInstanceOf(OptionsValidationError::class);
});

it('renders custom styles from a definition file', function () {
    $url = (new DiceBearProvider)->get(record(), DiceBearPlugin::make()
        ->customStyle('brand', __DIR__.'/fixtures/brand.json'));

    expect($url)->toMatch('#^/storage/avatars/dicebear/brand/42-[0-9a-f]{12}\.svg$#');
    expect(Storage::disk('public')->get(str_replace('/storage/', '', $url)))->toContain('M 30 60 Q 50 80 70 60');
});

it('uses custom seed resolver', function () {
    $plugin = DiceBearPlugin::make()->seedUsing(fn ($record) => $record->email);

    expect((new DiceBearProvider)->get(record(), $plugin))->toContain('/initials/test-at-examplecom-');
});

it('caches to custom disk and path', function () {
    Storage::fake('custom');

    (new DiceBearProvider)->get(record(3), DiceBearPlugin::make()
        ->style(DiceBearStyle::Glass)
        ->disk('custom')
        ->cachePath('my-avatars'));

    expect(Storage::disk('custom')->allFiles('my-avatars/glass'))->toHaveCount(1);
});

// ── HTTP driver ──────────────────────────────────────────────────────────

it('fetches from the v10 HTTP API with serialized options', function () {
    Http::fake(['api.dicebear.com/*' => Http::response('<svg>remote</svg>', 200)]);

    $url = (new DiceBearProvider)->get(record(5), DiceBearPlugin::make()
        ->driver('http')
        ->style(DiceBearStyle::BotttsNeutral)
        ->animated()
        ->options(['eyesVariant' => ['bulging', 'happy']]));

    Http::assertSent(function ($request) {
        parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);

        return str_starts_with($request->url(), 'https://api.dicebear.com/10.x/bottts-neutral/svg?')
            && $query === ['seed' => '5', 'tags' => 'animation', 'eyesVariant' => 'bulging,happy'];
    });

    expect(Storage::disk('public')->get(str_replace('/storage/', '', $url)))->toBe('<svg>remote</svg>');
});

it('fetches raster formats from the HTTP API', function () {
    Http::fake(['api.dicebear.com/*' => Http::response('PNGDATA', 200)]);

    $url = (new DiceBearProvider)->get(record(), DiceBearPlugin::make()->format('png')->cache(false));

    expect($url)->toBe('data:image/png;base64,'.base64_encode('PNGDATA'));
    Http::assertSent(fn ($request) => str_contains($request->url(), '/10.x/initials/png?'));
});

it('falls back to direct URL on API failure', function () {
    Http::fake(['api.dicebear.com/*' => Http::response('', 500)]);

    $url = (new DiceBearProvider)->get(record(7), DiceBearPlugin::make()->driver('http')->style(DiceBearStyle::Adventurer));

    expect($url)->toBe('https://api.dicebear.com/10.x/adventurer/svg?seed=7');
    expect(Storage::disk('public')->allFiles())->toBeEmpty();
});

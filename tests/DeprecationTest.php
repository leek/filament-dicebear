<?php

use Illuminate\Database\Eloquent\Model;
use Leek\FilamentDiceBear\DiceBearPlugin;
use Leek\FilamentDiceBear\DiceBearProvider;
use Leek\FilamentDiceBear\Enums\DiceBearStyle;
use Leek\FilamentDiceBear\Support\Deprecation;

beforeEach(fn () => Deprecation::reset());

/**
 * @return list<string>
 */
function captureDeprecations(Closure $callback): array
{
    $messages = [];

    set_error_handler(function (int $level, string $message) use (&$messages) {
        $messages[] = $message;

        return true;
    }, E_USER_DEPRECATED);

    try {
        $callback();
    } finally {
        restore_error_handler();
    }

    return $messages;
}

it('flags deprecated methods', function (Closure $call, string $expected) {
    expect(captureDeprecations(fn () => $call(DiceBearPlugin::make())->buildOptions('s')))
        ->toBe(["Since leek/filament-dicebear 2.0: {$expected} Support will be removed in 3.0."]);
})->with([
    'radius()' => [fn ($p) => $p->radius(50), 'DiceBearPlugin::radius() is deprecated, use borderRadius() instead.'],
    'backgroundType()' => [fn ($p) => $p->backgroundType('gradientLinear'), 'DiceBearPlugin::backgroundType() is deprecated, use backgroundColorFill() instead.'],
]);

it('flags deprecated option names and values', function (array $options, string $expected) {
    expect(captureDeprecations(fn () => DiceBearPlugin::make()->options($options)->buildOptions('s')))
        ->toContain("Since leek/filament-dicebear 2.0: {$expected} Support will be removed in 3.0.");
})->with([
    'radius' => [['radius' => 50], 'The "radius" option is deprecated, use "borderRadius" instead.'],
    'backgroundType' => [['backgroundType' => 'solid'], 'The "backgroundType" option is deprecated, use "backgroundColorFill" instead.'],
    'gradientLinear' => [['backgroundColorFill' => 'gradientLinear'], 'The "gradientLinear" background fill is deprecated, use "linear" instead.'],
    'flip string' => [['flip' => 'true'], 'Passing "true" as the "flip" option is deprecated, use "horizontal" or "none" instead.'],
]);

it('flags legacy flat config keys', function () {
    config()->set('filament-dicebear.radius', 50);

    expect(captureDeprecations(fn () => DiceBearPlugin::make()->buildOptions('s')))
        ->toBe(['Since leek/filament-dicebear 2.0: The "filament-dicebear.radius" config key is deprecated, set "filament-dicebear.options.borderRadius" instead. Support will be removed in 3.0.']);
});

it('flags v9 component option names', function () {
    $messages = captureDeprecations(fn () => (new DiceBearProvider)->get(
        new class(['id' => 1]) extends Model
        {
            protected $guarded = [];
        },
        DiceBearPlugin::make()->style(DiceBearStyle::BotttsNeutral)->options(['eyes' => 'happy'])->cache(false),
    ));

    expect($messages)->toBe(['Since leek/filament-dicebear 2.0: The "eyes" option is deprecated, use "eyesVariant" instead. Support will be removed in 3.0.']);
});

it('does not flag supported usage', function () {
    expect(captureDeprecations(fn () => DiceBearPlugin::make()
        ->borderRadius(50)
        ->backgroundColorFill('linear')
        ->flip()
        ->flip(false)
        ->options(['eyesVariant' => ['happy']])
        ->buildOptions('s')))->toBe([]);
});

it('triggers each notice once per process', function () {
    $plugin = DiceBearPlugin::make()->options(['radius' => 50]);

    expect(captureDeprecations(function () use ($plugin) {
        $plugin->buildOptions('a');
        $plugin->buildOptions('b');
        $plugin->buildOptions('c');
    }))->toHaveCount(1);
});

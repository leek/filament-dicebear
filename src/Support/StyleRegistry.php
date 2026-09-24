<?php

declare(strict_types=1);

namespace Leek\FilamentDiceBear\Support;

use Composer\InstalledVersions;
use DiceBear\OptionsDescriptor;
use DiceBear\Style;
use InvalidArgumentException;

/**
 * Loads DiceBear style definitions on demand and memoizes them for the
 * lifetime of the process. Parsing a definition runs JSON Schema validation
 * (~25 ms per style), so each one is loaded at most once.
 */
class StyleRegistry
{
    /** @var array<string, Style> */
    protected array $styles = [];

    /** @var array<string, array<string, array<string, mixed>>> */
    protected array $descriptors = [];

    /**
     * Resolve a style by name, or from a custom definition file when a path is given.
     */
    public function get(string $name, ?string $definitionPath = null): Style
    {
        $path = $definitionPath ?? $this->builtInPath($name);

        return $this->styles[$path] ??= $this->load($path);
    }

    /**
     * The OptionsDescriptor field map for a style (keys are option names).
     *
     * @return array<string, array<string, mixed>>
     */
    public function describe(string $name, ?string $definitionPath = null): array
    {
        $path = $definitionPath ?? $this->builtInPath($name);

        return $this->descriptors[$path] ??= (new OptionsDescriptor($this->get($name, $definitionPath)))->toJSON();
    }

    /**
     * A token that changes whenever the rendered output of a style may change,
     * used to invalidate cached avatars on upgrades.
     */
    public function version(?string $definitionPath = null): string
    {
        if ($definitionPath !== null) {
            return 'custom-'.substr((string) md5_file($definitionPath), 0, 8);
        }

        return 'core-'.InstalledVersions::getPrettyVersion('dicebear/core')
            .'-styles-'.InstalledVersions::getPrettyVersion('dicebear/styles');
    }

    public function exists(string $name): bool
    {
        return is_file($this->builtInPath($name));
    }

    /**
     * Coerce HTTP-style option values (comma-separated strings, "true"/"false",
     * numeric strings) into the types the native core validates against, and
     * rename v9-era component options (`eyes`) to their v10 names (`eyesVariant`)
     * (deprecated, removed in 3.0).
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function normalize(string $name, array $options, ?string $definitionPath = null): array
    {
        $fields = $this->describe($name, $definitionPath);
        $normalized = [];

        foreach ($options as $key => $value) {
            if (! isset($fields[$key]) && isset($fields[$key.'Variant'])) {
                Deprecation::trigger('The "%1$s" option is deprecated, use "%1$sVariant" instead.', $key);

                $key .= 'Variant';
            }

            $normalized[$key] = $this->coerce($value, $fields[$key] ?? ($key === 'tags' ? ['list' => true] : []));
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $field
     */
    protected function coerce(mixed $value, array $field): mixed
    {
        if (is_string($value) && ($field['list'] ?? false) && str_contains($value, ',')) {
            return array_values(array_filter(array_map('trim', explode(',', $value)), fn ($v) => $v !== ''));
        }

        if (is_string($value) && ($field['type'] ?? null) === 'boolean') {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        if (is_string($value) && is_numeric($value) && in_array($field['type'] ?? null, ['number', 'range'], true)) {
            return $value + 0;
        }

        return $value;
    }

    protected function builtInPath(string $name): string
    {
        if (! preg_match('/^[a-z0-9-]+$/', $name)) {
            throw new InvalidArgumentException("Invalid DiceBear style name [{$name}].");
        }

        return static::definitionsPath().'/'.$name.'.json';
    }

    public static function definitionsPath(): string
    {
        return rtrim((string) InstalledVersions::getInstallPath('dicebear/styles'), '/').'/src';
    }

    protected function load(string $path): Style
    {
        $json = @file_get_contents($path);

        if ($json === false) {
            throw new InvalidArgumentException("DiceBear style definition not found at [{$path}].");
        }

        return Style::fromJson($json);
    }
}

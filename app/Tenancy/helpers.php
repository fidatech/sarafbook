<?php

/*
| Helpers of the tenancy layer and of the new design. Loaded by Composer (autoload.files).
*/

use App\Models\Central\Tenant;
use App\Tenancy\Tenancy;

if (! function_exists('tenancy')) {
    function tenancy(): Tenancy
    {
        return app(Tenancy::class);
    }
}

if (! function_exists('tenant')) {
    /** The Sarafi of this request, or one of its attributes. Null on the platform's own address. */
    function tenant(?string $attribute = null): mixed
    {
        $tenant = app(Tenancy::class)->tenant();

        return $attribute === null ? $tenant : $tenant?->getAttribute($attribute);
    }
}

if (! function_exists('feature')) {
    /** Is a switchable feature on for this Sarafi? Everything is on when tenancy is off. */
    function feature(string $name, bool $default = true): bool
    {
        if (! app(Tenancy::class)->initialized()) {
            return $default;
        }
        $features = (array) config('tenancy.features', []);

        return array_key_exists($name, $features) ? (bool) $features[$name] : $default;
    }
}

if (! function_exists('tenant_public_path')) {
    /**
     * Where a Sarafi's uploaded files that used to sit in public/ are kept (logos, attachments).
     * With tenancy off this is public_path(), exactly as before. With $fallback, a file that the
     * Sarafi has not uploaded is read from public/ instead (for example the default logo).
     */
    function tenant_public_path(string $path = '', bool $fallback = false): string
    {
        $path = ltrim($path, '/\\');
        $tenant = app(Tenancy::class)->tenant();
        if (! $tenant) {
            return public_path($path);
        }
        $own = $tenant->storagePath('public'.($path !== '' ? DIRECTORY_SEPARATOR.$path : ''));
        if ($fallback && ! file_exists($own) && is_file(public_path($path))) {
            return public_path($path);
        }
        if ($path !== '' && ! is_dir(dirname($own))) {
            @mkdir(dirname($own), 0755, true);
        }

        return $own;
    }
}

if (! function_exists('asset_v')) {
    /** URL of a file in public/ with its modification time, so browsers keep it for a year and still see a new version. */
    function asset_v(string $path): string
    {
        static $urls = [];

        return $urls[$path] ??= asset($path).'?v='.(is_file(public_path($path)) ? filemtime(public_path($path)) : '0');
    }
}

if (! function_exists('text_dir')) {
    /** Text direction of the current language: "rtl" for Dari and Pashto. */
    function text_dir(): string
    {
        return in_array(app()->getLocale(), ['fa', 'ps', 'ar'], true) ? 'rtl' : 'ltr';
    }
}

if (! function_exists('initial')) {
    /** First letter of a name, for the round avatars. */
    function initial(?string $name, string $empty = '?'): string
    {
        $name = trim((string) $name);

        return $name === '' ? $empty : mb_strtoupper(mb_substr($name, 0, 1));
    }
}

if (! function_exists('enum_label')) {
    /** A stored value such as "active" or "trial", shown in the language of the user. */
    function enum_label(?string $value, ?string $empty = null): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return $empty ?? '—';
        }

        return __(ucfirst(str_replace(['_', '-'], ' ', $value)));
    }
}

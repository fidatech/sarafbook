<?php

namespace App\Support;

/**
 * Files other pages need (libraries and their styles), downloaded once in the background so that
 * the first visit to those pages does not wait for them. The list is written by
 * tools/build-assets.php into public/warm.json. Those files are kept by the browser for a year.
 */
class WarmAssets
{
    public static function urls(): array
    {
        static $urls = null;
        if ($urls !== null) {
            return $urls;
        }
        $file = public_path('warm.json');
        $list = is_file($file) ? (array) json_decode((string) file_get_contents($file), true) : [];

        // the same address the pages ask for, or the browser would keep a second copy: the shell
        // loads its files with ?v=<time changed>, the pages and the icon font without it
        $versioned = ['js/vendor/jquery.min.js', 'js/vendor/sweetalert2.min.js', 'css/vendor/sweetalert2.min.css', 'css/vendor/bootstrap-icons.css', 'assets/js/numeric-inputs.js'];

        return $urls = array_values(array_map(
            fn ($path) => in_array($path, $versioned, true) ? asset_v($path) : asset($path),
            array_filter($list, fn ($path) => is_string($path) && is_file(public_path($path)))
        ));
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * How long pages take in the users' own browsers: a page sends a few numbers after it has
 * loaded (resources/assets/js/app.js, "timing"), and they are kept, one line each, in
 * storage/logs/timing.log for the speed checks (scripts/deploy/cpanel-diagnose.sh).
 * Numbers and the path only: no query string, no form data, no user.
 */
class TimingController
{
    private const MAX_BYTES = 2_000_000;

    public function __invoke(Request $request): Response
    {
        $file = storage_path('logs/timing.log');
        if (! is_file($file) || filesize($file) < self::MAX_BYTES) {
            $num = fn (string $key) => max(0, min(120000, (int) $request->query($key, 0)));
            $word = fn (string $key) => substr(preg_replace('/[^a-z0-9_\-]/i', '', (string) $request->query($key, '')), 0, 24);
            $path = '/'.ltrim(substr(preg_replace('#[^a-z0-9_\-/]#i', '', (string) $request->query('p', '')), 0, 80), '/');
            $line = sprintf(
                "%s %-14s %-28s %-7s %-22s ttfb %5d  shown %5d  ready %5d  done %5d  rtt %4d %s\n",
                now()->format('m-d H:i:s'),
                substr($request->getHost(), 0, 14),
                $path,
                $word('k'),
                $word('d') ?: '-',
                $num('ttfb'), $num('fcp'), $num('dcl'), $num('load'), $num('rtt'), $word('net')
            );
            @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
        }

        return response('', 204)->header('Cache-Control', 'no-store');
    }
}

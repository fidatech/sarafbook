<?php

/*
 * Pages in the fida-sarafi style (docs/DESIGN.md): drawn on the server, with lists that change
 * in place. A list's filter form asks for its parts with the X-Fragment header; page() then
 * sends only those parts (Blade @fragment) instead of the whole page.
 */

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

if (! function_exists('page')) {
    /** The whole page, or only the parts named in X-Fragment (comma separated) of $parts. */
    function page(string $view, array $data = [], array $parts = ['list']): Response
    {
        $view = view($view, $data);
        $asked = array_values(array_intersect($parts, array_map('trim', explode(',', (string) request()->header('X-Fragment')))));
        if ($asked) {
            return response($view->fragments($asked))->header('Vary', 'X-Fragment')->header('Cache-Control', 'no-store, private');
        }

        return response($view)->header('Vary', 'X-Fragment');
    }
}

if (! function_exists('done')) {
    /**
     * The answer to a save: JSON for the older pages that send with JavaScript, otherwise back
     * to the page (or $to) with the message shown as a notice.
     */
    function done(string $message, ?string $to = null, bool $ok = true, int $status = 200): JsonResponse|RedirectResponse
    {
        if (request()->expectsJson()) {
            return response()->json(['success' => $ok, 'message' => $message], $ok ? 200 : ($status === 200 ? 422 : $status));
        }
        $redirect = $to ? redirect($to) : back();

        return $redirect->with($ok ? 'success' : 'error', $message);
    }
}

if (! function_exists('ico')) {
    /** An icon of the sprite as plain markup, for long lists where a component per icon costs time. */
    function ico(string $name, string $class = ''): string
    {
        static $sprite = null;
        $sprite ??= asset_v('icons.svg');

        return '<i class="fi'.($class !== '' ? ' '.$class : '').'"><svg aria-hidden="true" focusable="false"><use href="'.$sprite.'#s-'.$name.'"/></svg></i>';
    }
}

if (! function_exists('upload_extension')) {
    /**
     * The extension an uploaded attachment is saved with: taken from what the file really is,
     * never from the sender's file name, and only images and PDF are accepted. Attachments live
     * in the public folder, so a .php name there could run on the server.
     */
    function upload_extension(\Illuminate\Http\UploadedFile $file): string
    {
        $ext = strtolower((string) $file->guessExtension());
        $ext = $ext === 'jpeg' ? 'jpg' : $ext;
        if (! in_array($ext, ['jpg', 'png', 'webp', 'gif', 'pdf'], true)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'attachment' => __('Only pictures (JPG, PNG, WebP, GIF) and PDF files can be attached.'),
            ]);
        }

        return $ext;
    }
}

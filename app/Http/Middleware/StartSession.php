<?php

namespace App\Http\Middleware;

use Illuminate\Session\Middleware\StartSession as BaseStartSession;

/**
 * Laravel's session middleware, except that a page the browser loads ahead of time
 * ("Sec-Purpose: prefetch", docs/SPEED.md) only reads the session and never writes it.
 *
 * Without this, an early request that ends after a real save could write back the session as it
 * was before the save, and the "saved" message or a form's errors would be lost. It would also
 * use up a message meant for the page the user actually opens.
 */
class StartSession extends BaseStartSession
{
    protected function saveSession($request)
    {
        if ($request->prefetch()) {
            return;
        }

        parent::saveSession($request);
    }
}

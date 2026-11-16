<?php

use Illuminate\Support\Facades\Auth;

if (!function_exists('logActivity')) {

    function logActivity($model, $description, $properties = [], $event = null)
    {
        $activity = activity()
            ->causedBy(Auth::user())
            ->performedOn($model)
            ->withProperties(array_merge($properties, [
                'ip' => request()->ip(),
            ]));

        if ($event) {
            $activity->event($event);
        }

        return $activity->log($description);
    }

}

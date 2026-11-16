<?php

namespace App\Support;

class AccessLabels
{
    /** Display labels only: permission keys and role names remain unchanged. */
    public static function permission(string $name): string
    {
        if (app()->getLocale() === 'en') {
            return ucfirst($name);
        }

        [$action, $module] = array_pad(explode(' ', $name, 2), 2, null);
        $actions = __('access.permission_actions');
        $modules = __('access.permission_modules');

        if (!is_array($actions) || !is_array($modules) || !isset($actions[$action], $modules[$module])) {
            return ucfirst($name);
        }

        return str_replace(':module', $modules[$module], $actions[$action]);
    }

    public static function group(string $name): string
    {
        $labels = __('access.permission_modules');

        return is_array($labels) ? ($labels[$name] ?? ucfirst(str_replace('_', ' ', $name))) : $name;
    }

    public static function role(string $name): string
    {
        $labels = __('access.role_names');

        return is_array($labels) ? ($labels[strtolower($name)] ?? $name) : $name;
    }
}

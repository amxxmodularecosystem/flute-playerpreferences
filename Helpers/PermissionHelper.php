<?php

namespace Flute\Modules\PlayerPreferences\Helpers;

class PermissionHelper
{
    /**
     * Returns true if the current token has master access or the given
     * specific permission (api.player-preferences.<action>).
     */
    public static function canApi(string $action): bool
    {
        return user()->can('api.player-preferences')
            || user()->can("api.player-preferences.{$action}");
    }
}

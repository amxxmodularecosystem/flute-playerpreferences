<?php

declare(strict_types=1);

namespace Flute\Modules\PlayerPreferences;

use Flute\Core\Database\Entities\Permission;
use Flute\Core\ModulesManager\ModuleInformation;

class Installer extends \Flute\Core\Support\AbstractModuleInstaller
{
    private const PERMISSIONS = [
        'admin.player-preferences'       => 'player-preferences.permissions.admin',
        'api.player-preferences'         => 'player-preferences.permissions.api',
        'api.player-preferences.read'    => 'player-preferences.permissions.api_read',
        'api.player-preferences.write'   => 'player-preferences.permissions.api_write',
    ];

    public function install(ModuleInformation &$module): bool
    {
        foreach (self::PERMISSIONS as $name => $desc) {
            if (!Permission::findOne(['name' => $name])) {
                $permission = new Permission();
                $permission->name = $name;
                $permission->desc = $desc;
                $permission->save();
            }
        }

        return true;
    }

    public function uninstall(ModuleInformation &$module): bool
    {
        foreach (array_keys(self::PERMISSIONS) as $name) {
            $permission = Permission::findOne(['name' => $name]);
            if ($permission) {
                $permission->delete();
            }
        }

        return true;
    }

    public function getKey(): ?string
    {
        return 'PlayerPreferences';
    }
}

<?php

namespace Flute\Modules\PlayerPreferences\Packages;

use Flute\Core\Modules\Admin\Support\AbstractAdminPackage;
use Flute\Modules\PlayerPreferences\Packages\Screens\PlayerPreferencesListScreen;
use Flute\Modules\PlayerPreferences\Packages\Screens\PlayerPreferencesUserScreen;

class PlayerPreferencesPackage extends AbstractAdminPackage
{
    public function initialize(): void
    {
        $this->loadRoutesFromFile('routes.php');
    }

    public function getPermissions(): array
    {
        return ['admin', 'admin.player-preferences'];
    }

    public function getMenuItems(): array
    {
        return [
            [
                'title'      => __('player-preferences.admin.menu_title'),
                'icon'       => 'ph.bold.sliders-bold',
                'url'        => url('/admin/player-preferences'),
                'permission' => 'admin.player-preferences',
            ],
        ];
    }

    public function getPriority(): int
    {
        return 50;
    }
}

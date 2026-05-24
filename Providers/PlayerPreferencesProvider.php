<?php

namespace Flute\Modules\PlayerPreferences\Providers;

use Flute\Core\Support\ModuleServiceProvider;
use Flute\Modules\PlayerPreferences\Packages\PlayerPreferencesPackage;
use Flute\Modules\PlayerPreferences\Services\PlayerPreferencesService;

class PlayerPreferencesProvider extends ModuleServiceProvider
{
    protected ?string $moduleName = 'PlayerPreferences';

    public function register(\DI\Container $container): void
    {
        $container->set(PlayerPreferencesService::class, \DI\autowire());
    }

    public function boot(\DI\Container $container): void
    {
        $this->bootstrapModule();
        $this->loadEntities();
        $this->loadRouterAttributes();

        if (is_admin_path()) {
            $this->loadPackage(new PlayerPreferencesPackage());
        }
    }
}

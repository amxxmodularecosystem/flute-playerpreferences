<?php

use Flute\Core\Router\Router;
use Flute\Modules\PlayerPreferences\Packages\Screens\PlayerPreferencesListScreen;
use Flute\Modules\PlayerPreferences\Packages\Screens\PlayerPreferencesUserScreen;

Router::screen('/admin/player-preferences', PlayerPreferencesListScreen::class);
Router::screen('/admin/player-preferences/{userId}', PlayerPreferencesUserScreen::class);

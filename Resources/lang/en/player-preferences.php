<?php

return [
    'permissions' => [
        'admin'     => 'Access to Player Preferences admin panel',
        'api'       => 'Full API access to Player Preferences (read + write)',
        'api_read'  => 'API: read player preferences',
        'api_write' => 'API: write player preferences',
    ],

    'admin' => [
        'menu_title'       => 'Player Preferences',
        'list_title'       => 'Player Preferences',
        'list_description' => 'Settings saved by game servers for each player',
        'user_not_found'   => 'User not found',
        'user_description' => 'Settings saved by game servers',
        'col_player'       => 'Player',
        'col_server'       => 'Server',
        'col_count'        => 'Settings',
        'col_key'          => 'Key',
        'col_value'        => 'Value',
        'col_updated'               => 'Last updated',
        'filter_player'             => 'Player name',
        'filter_player_placeholder' => 'Search by name...',
        'filter_server'             => 'Server',
    ],
];

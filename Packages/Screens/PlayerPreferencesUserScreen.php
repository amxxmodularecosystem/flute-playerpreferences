<?php

namespace Flute\Modules\PlayerPreferences\Packages\Screens;

use Flute\Admin\Platform\Fields\TD;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Screen;
use Flute\Core\Database\Entities\Server;
use Flute\Core\Database\Entities\User;
use Flute\Modules\PlayerPreferences\database\Entities\PlayerPreference;

class PlayerPreferencesUserScreen extends Screen
{
    public ?string $permission = 'admin.player-preferences';

    public ?User   $user        = null;
    public array   $items       = [];
    public array   $serverNames = [];

    public function mount(): void
    {
        $userId   = (int) request()->input('userId');
        $serverId = (int) request()->get('server_id', 0);

        $this->user = rep(User::class)->findByPK($userId);

        $servers = rep(Server::class)->findAll([]);
        foreach ($servers as $server) {
            $this->serverNames[$server->id] = $server->name;
        }

        if ($this->user === null) {
            $this->name = __('player-preferences.admin.user_not_found');
            return;
        }

        $this->name        = $this->user->name;
        $this->description = __('player-preferences.admin.user_description');

        $filter = ['userId' => $userId];
        if ($serverId > 0) {
            $filter['serverId'] = $serverId;
        }

        $this->items = rep(PlayerPreference::class)->findAll($filter);
    }

    public function layout(): array
    {
        if ($this->user === null) {
            return [];
        }

        return [
            LayoutFactory::table('items', [
                TD::make('serverId', __('player-preferences.admin.col_server'))
                    ->sort()
                    ->render(fn (PlayerPreference $row) => $this->serverNames[$row->serverId] ?? ('Server ' . $row->serverId)),

                TD::make('key', __('player-preferences.admin.col_key'))
                    ->sort()
                    ->cantHide(),

                TD::make('value', __('player-preferences.admin.col_value'))
                    ->render(fn (PlayerPreference $row) => sprintf(
                        '<code style="word-break:break-all">%s</code>',
                        e($row->value)
                    )),

                TD::make('updatedAt', __('player-preferences.admin.col_updated'))
                    ->sort()
                    ->render(fn (PlayerPreference $row) => $row->updatedAt
                        ? $row->updatedAt->format('Y-m-d H:i')
                        : '—'
                    ),
            ])
                ->searchable(['key'])
                ->perPage(50),
        ];
    }
}

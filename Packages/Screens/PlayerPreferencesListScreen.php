<?php

namespace Flute\Modules\PlayerPreferences\Packages\Screens;

use Flute\Admin\Platform\Fields\TD;
use Flute\Admin\Platform\Layouts\Filters;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Screen;
use Flute\Core\Database\Entities\Server;
use Flute\Modules\PlayerPreferences\database\Entities\PlayerPreference;

class PlayerPreferencesListScreen extends Screen
{
    public ?string $name        = 'player-preferences.admin.list_title';
    public ?string $description = 'player-preferences.admin.list_description';
    public ?string $permission  = 'admin.player-preferences';

    public array $items       = [];
    public array $serverNames = [];
    public array $serverOptions = [];

    public function mount(): void
    {
        $servers = rep(Server::class)->findAll([]);
        foreach ($servers as $server) {
            $this->serverNames[$server->id] = $server->name;
        }

        $rows = rep(PlayerPreference::class)
            ->select()
            ->load('user')
            ->orderBy('updatedAt', 'DESC')
            ->fetchAll();

        // Collect distinct server IDs for the filter dropdown (before filtering)
        $serverIds = [];
        foreach ($rows as $row) {
            $serverIds[$row->serverId] = $this->serverNames[$row->serverId] ?? ('Server ' . $row->serverId);
        }
        ksort($serverIds);
        $this->serverOptions = $serverIds;

        // Read filter values
        $filterServerId = request()->get('server_id');
        $filterPlayer   = strtolower(trim((string) request()->get('player', '')));

        // Group by (userId, serverId), applying server filter while iterating
        $grouped = [];
        foreach ($rows as $row) {
            if ($row->user === null) {
                continue;
            }

            if ($filterServerId !== null && $filterServerId !== '' && (int) $filterServerId !== $row->serverId) {
                continue;
            }

            $key = $row->userId . '_' . $row->serverId;

            if (!isset($grouped[$key])) {
                $grouped[$key] = (object) [
                    'user'      => $row->user,
                    'userId'    => $row->userId,
                    'serverId'  => $row->serverId,
                    'count'     => 0,
                    'updatedAt' => $row->updatedAt,
                ];
            }

            $grouped[$key]->count++;

            if ($row->updatedAt > $grouped[$key]->updatedAt) {
                $grouped[$key]->updatedAt = $row->updatedAt;
            }
        }

        // Apply player name filter after grouping
        if ($filterPlayer !== '') {
            $grouped = array_filter(
                $grouped,
                fn ($item) => str_contains(strtolower($item->user->name), $filterPlayer)
            );
        }

        $this->items = array_values($grouped);
    }

    public function layout(): array
    {
        return [
            Filters::make()
                ->input(
                    'player',
                    __('player-preferences.admin.filter_player'),
                    'text',
                    null,
                    __('player-preferences.admin.filter_player_placeholder')
                )
                ->select(
                    'server_id',
                    __('player-preferences.admin.filter_server'),
                    $this->serverOptions
                )
                ->compact(),

            LayoutFactory::table('items', [
                TD::make('user', __('player-preferences.admin.col_player'))
                    ->cantHide()
                    ->render(fn ($item) => sprintf(
                        '<a href="%s">%s</a>',
                        url('/admin/player-preferences/' . $item->userId),
                        e($item->user->name)
                    )),

                TD::make('serverId', __('player-preferences.admin.col_server'))
                    ->render(fn ($item) => $this->serverNames[$item->serverId] ?? ('Server ' . $item->serverId)),

                TD::make('count', __('player-preferences.admin.col_count'))
                    ->render(fn ($item) => $item->count),

                TD::make('updatedAt', __('player-preferences.admin.col_updated'))
                    ->render(fn ($item) => $item->updatedAt
                        ? $item->updatedAt->format('Y-m-d H:i')
                        : '—'
                    ),
            ])->perPage(25),
        ];
    }
}

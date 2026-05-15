<?php

namespace Flute\Modules\PlayerPreferences\Services;

use Flute\Core\Database\Entities\SocialNetwork;
use Flute\Core\Database\Entities\User;
use Flute\Core\Database\Entities\UserSocialNetwork;
use Flute\Modules\PlayerPreferences\database\Entities\PlayerPreference;

class PlayerPreferencesService
{
    /**
     * Resolves a CMS User by their SteamID64 via the UserSocialNetwork table.
     * Returns null if no CMS account is linked to this Steam account.
     */
    public function findUserBySteamId(string $steamid64): ?User
    {
        $steamNetwork = rep(SocialNetwork::class)->findOne(['key' => 'steam']);

        if ($steamNetwork === null) {
            return null;
        }

        $link = rep(UserSocialNetwork::class)
            ->select()
            ->where('value', $steamid64)
            ->where('socialNetwork_id', $steamNetwork->id)
            ->load('user')
            ->fetchOne();

        return $link?->user;
    }

    /**
     * Returns all settings for a user on a given server.
     *
     * @return array<string, mixed>
     */
    public function getSettings(int $userId, int $serverId): array
    {
        $rows = rep(PlayerPreference::class)->findAll([
            'userId'   => $userId,
            'serverId' => $serverId,
        ]);

        $settings = [];
        foreach ($rows as $row) {
            $settings[$row->key] = json_decode($row->value, true);
        }

        return $settings;
    }

    /**
     * Shallow-merges $incoming into existing settings and persists all changed
     * keys in a single transaction. Returns the full settings after the update.
     *
     * @param  array<string, mixed> $incoming
     * @return array<string, mixed>
     */
    public function updateSettings(int $userId, int $serverId, array $incoming): array
    {
        $existing = rep(PlayerPreference::class)->findAll([
            'userId'   => $userId,
            'serverId' => $serverId,
        ]);

        $map = [];
        foreach ($existing as $row) {
            $map[$row->key] = $row;
        }

        foreach ($incoming as $key => $value) {
            if (isset($map[$key])) {
                $row = $map[$key];
            } else {
                $row           = new PlayerPreference();
                $row->userId   = $userId;
                $row->serverId = $serverId;
                $row->key      = (string) $key;
            }
            $row->value = json_encode($value);
            $row->save();
        }

        $settings = [];
        foreach ($map as $key => $row) {
            $settings[$key] = json_decode($row->value, true);
        }
        foreach ($incoming as $key => $value) {
            $settings[$key] = $value;
        }

        return $settings;
    }
}

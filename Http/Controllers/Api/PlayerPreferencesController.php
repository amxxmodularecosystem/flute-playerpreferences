<?php

namespace Flute\Modules\PlayerPreferences\Http\Controllers\Api;

use Flute\Core\Router\Annotations\Get;
use Flute\Core\Router\Annotations\Post;
use Flute\Core\Router\Annotations\Middleware;
use Flute\Core\Support\BaseController;
use Flute\Modules\PlayerPreferences\Helpers\PermissionHelper;
use Flute\Modules\PlayerPreferences\Services\PlayerPreferencesService;

#[Middleware(['api', 'token'])]
class PlayerPreferencesController extends BaseController
{
    public function __construct(private readonly PlayerPreferencesService $service) {}

    /**
     * GET /api/player-preferences/settings
     *
     * Query params or JSON body:
     *   steamid64  — 17-digit SteamID64, OR
     *   steamid    — STEAM_X:Y:Z format
     *   server_id  — integer server identifier
     *
     * Response: {"steamid64":"...","server_id":1,"settings":{...}}
     */
    #[Get('/api/player-preferences/settings', name: 'api.player-preferences.get')]
    public function getSettings(): mixed
    {
        if (!PermissionHelper::canApi('read')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $data = $this->parseInput();

        $steamid64 = $this->resolveSteamId64($data);
        if ($steamid64 === null) {
            return $this->json(['error' => 'Valid steamid or steamid64 is required'], 400);
        }

        $serverId = isset($data['server_id']) ? (int) $data['server_id'] : null;
        if ($serverId === null) {
            return $this->json(['error' => 'server_id is required'], 400);
        }

        $user = $this->service->findUserBySteamId($steamid64);
        if ($user === null) {
            return $this->json(['error' => 'No CMS user linked to this Steam account'], 404);
        }

        $settings = $this->service->getSettings($user->id, $serverId);

        return $this->json([
            'steamid64' => $steamid64,
            'server_id' => $serverId,
            'settings'  => (object) $settings,
        ]);
    }

    /**
     * POST /api/player-preferences/settings
     *
     * JSON body:
     *   steamid64  — 17-digit SteamID64, OR
     *   steamid    — STEAM_X:Y:Z format
     *   server_id  — integer server identifier
     *   settings   — object with arbitrary key-value pairs (shallow-merged into existing)
     *
     * Response: {"steamid64":"...","server_id":1,"settings":{...}}
     */
    #[Post('/api/player-preferences/settings', name: 'api.player-preferences.update')]
    public function updateSettings(): mixed
    {
        if (!PermissionHelper::canApi('write')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $data = $this->parseInput();

        $steamid64 = $this->resolveSteamId64($data);
        if ($steamid64 === null) {
            return $this->json(['error' => 'Valid steamid or steamid64 is required'], 400);
        }

        $serverId = isset($data['server_id']) ? (int) $data['server_id'] : null;
        if ($serverId === null) {
            return $this->json(['error' => 'server_id is required'], 400);
        }

        if (!isset($data['settings']) || !is_array($data['settings'])) {
            return $this->json(['error' => 'settings must be a JSON object'], 400);
        }

        $user = $this->service->findUserBySteamId($steamid64);
        if ($user === null) {
            return $this->json(['error' => 'No CMS user linked to this Steam account'], 404);
        }

        $settings = $this->service->updateSettings($user->id, $serverId, $data['settings']);

        return $this->json([
            'steamid64' => $steamid64,
            'server_id' => $serverId,
            'settings'  => (object) $settings,
        ]);
    }

    /**
     * Reads input from JSON body first, then overlays query-string params.
     */
    private function parseInput(): array
    {
        $data = [];

        $content = request()->getContent();
        if (!empty($content)) {
            $parsed = json_decode($content, true);
            if (is_array($parsed)) {
                $data = $parsed;
            }
        }

        foreach (request()->query->all() as $key => $value) {
            $data[$key] = $value;
        }

        return $data;
    }

    /**
     * Accepts either a ready steamid64 (17 digits) or STEAM_X:Y:Z and returns steamid64.
     * Returns null if the value is missing or unrecognised.
     */
    private function resolveSteamId64(array $data): ?string
    {
        $raw = $data['steamid64'] ?? $data['steamid'] ?? null;
        if ($raw === null) {
            return null;
        }

        $raw = trim((string) $raw);

        if (preg_match('/^\d{17}$/', $raw)) {
            return $raw;
        }

        // STEAM_X:Y:Z  →  steamid64 = 76561197960265728 + Z*2 + Y
        if (preg_match('/^STEAM_\d:([01]):(\d+)$/i', $raw, $m)) {
            return (string) (76561197960265728 + (int) $m[2] * 2 + (int) $m[1]);
        }

        return null;
    }
}

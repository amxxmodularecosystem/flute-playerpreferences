<?php

namespace Flute\Modules\PlayerPreferences\Http\Controllers\Api;

use Flute\Core\Database\Entities\ApiKey;
use Flute\Core\Router\Annotations\Route;
use Flute\Core\Support\BaseController;
use Flute\Core\Support\FluteRequest;
use Flute\Modules\PlayerPreferences\Http\Middleware\PlayerPreferencesApiMiddleware;
use Flute\Modules\PlayerPreferences\Services\PlayerPreferencesService;
use Symfony\Component\HttpFoundation\Response;

#[Route('/api/player-preferences', name: 'api.player-preferences.', middleware: PlayerPreferencesApiMiddleware::class)]
class PlayerPreferencesController extends BaseController
{
    public function __construct(private readonly PlayerPreferencesService $service) {}

    #[Route('/settings', methods: ['GET'], name: 'get')]
    public function getSettings(FluteRequest $request): Response
    {
        /** @var ApiKey $apiKey */
        $apiKey = $request->attributes->get('api_key');

        if (!$this->canApi($apiKey, ['api.player-preferences', 'api.player-preferences.read'])) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $data = $this->parseInput($request);

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

        return $this->json([
            'steamid64' => $steamid64,
            'server_id' => $serverId,
            'settings'  => (object) $this->service->getSettings($user->id, $serverId),
        ]);
    }

    #[Route('/settings', methods: ['POST'], name: 'update')]
    public function updateSettings(FluteRequest $request): Response
    {
        /** @var ApiKey $apiKey */
        $apiKey = $request->attributes->get('api_key');

        if (!$this->canApi($apiKey, ['api.player-preferences', 'api.player-preferences.write'])) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $data = $this->parseInput($request);

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

        return $this->json([
            'steamid64' => $steamid64,
            'server_id' => $serverId,
            'settings'  => (object) $this->service->updateSettings($user->id, $serverId, $data['settings']),
        ]);
    }

    private function canApi(?ApiKey $apiKey, array $permissions): bool
    {
        if (!$apiKey) {
            return false;
        }

        foreach ($permissions as $permission) {
            if ($apiKey->hasPermissionByName($permission)) {
                return true;
            }
        }

        return false;
    }

    private function parseInput(FluteRequest $request): array
    {
        $data = [];

        $content = $request->getContent();
        if (!empty($content)) {
            $parsed = json_decode($content, true);
            if (is_array($parsed)) {
                $data = $parsed;
            }
        }

        foreach ($request->query->all() as $key => $value) {
            $data[$key] = $value;
        }

        return $data;
    }

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

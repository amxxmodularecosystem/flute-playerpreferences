<?php

namespace Flute\Modules\PlayerPreferences\Http\Middleware;

use Closure;
use Flute\Core\Database\Entities\ApiKey;
use Flute\Core\Support\BaseMiddleware;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\Response;

class PlayerPreferencesApiMiddleware extends BaseMiddleware
{
    public function handle(FluteRequest $request, Closure $next, ...$args): Response
    {
        $plainKey = $request->headers->get('X-API-Key')
            ?? $request->headers->get('X_API_KEY')
            ?? $request->getAuthorizationBearerToken();

        if (!$plainKey) {
            return response()->json(['error' => 'API key is required'], 401);
        }

        $apiKey = ApiKey::findByPlainKey($plainKey, true);
        if (!$apiKey) {
            return response()->json(['error' => 'Invalid API key'], 401);
        }

        $apiKey->updateLastUsed();
        $request->attributes->set('api_key', $apiKey);

        return $next($request);
    }
}

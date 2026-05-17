# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a **Flute CMS module** that provides an API and admin panel for storing and retrieving per-player game server preferences. It is designed to be dropped into a Flute framework installation — there is no standalone build, test, or lint toolchain in this repo.

The module namespace is `Flute\Modules\PlayerPreferences`. It is registered via `module.json` and bootstrapped through the Flute module system.

## Architecture

### Data Flow

1. A game server calls the REST API with a Steam ID + server ID
2. The API controller resolves the Steam ID to a CMS `User` via `UserSocialNetwork`
3. `PlayerPreferencesService` reads/writes `PlayerPreference` entities (one row per user+server+key)
4. Admins browse preferences through the Orchid-style admin screens

### Key Components

| Layer | File | Purpose |
|---|---|---|
| Entity | `database/Entities/PlayerPreference.php` | Cycle ORM entity; unique index on `(user_id, server_id, setting_key)` |
| Service | `Services/PlayerPreferencesService.php` | Looks up users by SteamID64, reads/writes settings with transaction safety |
| API | `Http/Controllers/Api/PlayerPreferencesController.php` | Two endpoints (GET/POST); converts `STEAM_X:Y:Z` ↔ SteamID64 |
| Bootstrap | `Providers/PlayerPreferencesProvider.php` | Registers service, entities, routes, and admin package |
| Admin | `Packages/PlayerPreferencesPackage.php` + `Screens/` | Admin menu, list screen, per-user screen |
| Routes | `Packages/routes.php` | Defines admin routes |
| i18n | `Resources/lang/{en,ru}/player-preferences.php` | English and Russian translations |

### API Endpoints

Both require `api` + `token` middleware:

- `GET /api/player-preferences/settings` — Fetch all settings for a player on a server. Params: `steamid64` or Steam2 format (`STEAM_X:Y:Z`), `server_id`.
- `POST /api/player-preferences/settings` — Shallow-merge new settings into existing ones. Same params plus `settings` (JSON object).

### Steam ID Conversion

The controller converts `STEAM_X:Y:Z` to SteamID64 inline: `76561197960265728 + Z*2 + Y`.

### Admin Screens

- **List screen** — groups rows by `(userId, serverId)`, shows player name, server, setting count, last updated; filterable by player name / server ID.
- **User screen** — shows all settings for one player across all servers; filterable by key.

## Flute Framework Conventions

- Entities use Cycle ORM attributes (`#[Entity]`, `#[Column]`, `#[BelongsTo]`, etc.)
- Services are registered in the DI container via the Provider's `register()` method
- Admin packages extend Flute's package/screen system (similar to Laravel Orchid)
- Language keys follow the pattern `player-preferences.key_name`
- Admin package is only loaded on admin paths (checked in `PlayerPreferencesProvider::boot()`)

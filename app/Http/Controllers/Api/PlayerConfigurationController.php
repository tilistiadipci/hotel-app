<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PlayerContentManager;
use App\Services\PlayerTokenAuthenticator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlayerConfigurationController extends Controller
{
    public function __construct(
        private readonly PlayerTokenAuthenticator $authenticator,
        private readonly PlayerContentManager $content,
    ) {
    }

    public function show(Request $request): JsonResponse
    {
        $hotel = $request->attributes->get('hotel');
        $token = trim((string) $request->header('X-Player-Token'));

        if ($token === '') {
            return response()->json(['status' => false, 'message' => 'Header X-Player-Token wajib dikirim.'], 401);
        }

        $player = $this->authenticator->find($hotel, $token);

        if (! $player) {
            return response()->json(['status' => false, 'message' => 'Token player tidak valid atau sudah kedaluwarsa.'], 401);
        }

        $player->loadMissing(['theme.details', 'theme.imageMedia']);
        $theme = $player->theme;

        return response()->json([
            'status' => true,
            'hotel' => ['code' => $hotel->code, 'name' => $hotel->name],
            'player' => [
                'id' => $player->uuid,
                'name' => $player->name,
                'alias' => $player->alias,
                'serial' => $player->serial,
            ],
            'theme' => $theme ? [
                'id' => $theme->uuid,
                'name' => $theme->name,
                'description' => $theme->description,
                'image_url' => $theme->imageMedia
                    ? getMediaImageUrl($theme->imageMedia->storage_path, 1280, 720)
                    : getMediaImageUrl('default/theme-'.$theme->id.'.png', 1280, 720),
                'details' => $theme->details->pluck('value', 'key'),
            ] : null,
            'content' => [
                'uses_custom' => (bool) $player->use_custom_content,
                'html' => $player->html_content,
                'menus' => $this->content->effective($player)->values(),
            ],
        ]);
    }
}

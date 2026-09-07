<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Title;
use App\Traits\MediaProcessors;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    use MediaProcessors;

    public function show(int $id, Request $request): JsonResponse
    {
        $theme = $request['theme'] == 'dark' ? 1 : 0;

        $title = Title::with(['Asset' => function ($query) use ($theme) {
            $query->where('is_dark_mode', $theme);
        }])
            ->findOrFail($id);

        foreach ($title->Asset as $asset) {
            $asset->logo = $this->verifyPhoto($asset->logo, '_assets/mobile');
            $asset->chart_icon = $this->verifyPhoto($asset->chart_icon, '_assets/mobile');
            $asset->article_icon = $this->verifyPhoto($asset->article_icon, '_assets/mobile');
            $asset->podcast_icon = $this->verifyPhoto($asset->podcast_icon, '_assets/mobile');
            $asset->article_page_icon = $this->verifyPhoto($asset->article_page_icon, '_assets/mobile');
            $asset->youtube_page_icon = $this->verifyPhoto($asset->youtube_page_icon, '_assets/mobile');
        }

        return response()->json([
            'title' => $title,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Show;
use App\Traits\MediaProcessors;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShowController extends Controller
{
    use MediaProcessors;

    public function index(): JsonResponse
    {
        $shows = Show::query()
            ->has('Jock')
            ->with('Jock')
            ->where('location', $this->getStationCode())
            ->where('is_active', '=', 1)
            ->get();

        foreach ($shows as $show) {
            $show->background_image = $this->verifyPhoto($show->background_image, 'shows');
            $show->icon = $this->verifyPhoto($show->icon, 'shows');
            $show->header_image = $this->verifyPhoto($show->header_image, 'shows');
        }

        return response()->json([
            'shows' => $shows,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $show = Show::query()
            ->with(['Podcast', 'Jock'])
            ->where('location', $this->getStationCode())->findOrFail($id);

        $show->background_image = $this->verifyPhoto($show->background_image, 'shows');
        $show->icon = $this->verifyPhoto($show->icon, 'shows');
        $show->header_image = $this->verifyPhoto($show->header_image, 'shows');

        foreach ($show->Podcast as $podcast) {
            $podcast->image = $this->verifyPhoto($podcast->image, 'podcasts');
        }

        return response()->json([
            'show' => $show,
        ]);
    }

    public function podcasts(int $id, Request $request): JsonResponse
    {
        $request->merge(['show_id' => $id]);

        return app(PodcastController::class)->index($request);
    }
}

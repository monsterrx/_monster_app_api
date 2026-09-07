<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Podcast;
use App\Models\Show;
use App\Traits\MediaProcessors;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PodcastController extends Controller
{
    use MediaProcessors;

    public function index(Request $request): JsonResponse
    {
        $request->validate(['show_id' => ['nullable', 'integer', 'min:1'], 'keyword' => ['nullable', 'string', 'max:200'], 'page' => ['nullable', 'integer', 'min:1']]);
        $shows = Show::has('Podcast')
            ->withCount(['Podcast' => function ($query) {
                $query->whereNull('deleted_at')
                    ->where('location', $this->getStationCode());
            }])
            ->with('Jock')
            ->whereNull('deleted_at')
            ->where('location', $this->getStationCode())
            ->orderBy('podcast_count', 'desc')
            ->get();

        $podcasts = Podcast::with('Show.Jock')
            ->whereNull('deleted_at')
            ->where('location', $this->getStationCode())
            ->orderBy('date', 'desc')
            ->paginate(8);

        // for podcast filtering
        $show_id = $request->get('show_id');
        $keyword = $request->get('keyword');

        if ($show_id) {
            $podcasts = Podcast::query()
                ->with('Show.Jock')
                ->whereNull('deleted_at')
                ->where('show_id', $show_id)
                ->where('location', $this->getStationCode())
                ->orderBy('date', 'desc')
                ->paginate(8)
                ->appends('show_id', $show_id);

            if ($keyword) {
                $podcasts = Podcast::query()
                    ->with('Show.Jock')
                    ->whereNull('deleted_at')
                    ->where('show_id', $show_id)
                    ->where('episode', 'like', '%'.$keyword.'%')
                    ->where('location', $this->getStationCode())
                    ->orderBy('date', 'desc')
                    ->paginate(8)
                    ->appends([
                        'show_id' => $show_id,
                        'keyword' => $keyword,
                    ]);
            }

            $show = Show::query()
                ->with('Jock')
                ->where('location', $this->getStationCode())->findOrFail($show_id);

            $show->icon = $this->verifyPhoto($show->icon, 'shows');
            $show->background_image = $this->verifyPhoto($show->background_image, 'shows');
            $show->header_image = $this->verifyPhoto($show->header_image, 'shows');

            foreach ($podcasts as $podcast) {
                $podcast['date'] = date('M d, Y', strtotime($podcast['date']));
                $podcast['time_elapsed'] = $this->timePassedSincePublished($podcast['date']);
                $podcast['short_episode'] = Str::limit($podcast['episode'], 16);
                $podcast['image'] = $this->verifyPhoto($podcast['image'], 'podcasts');
                $podcast['stream'] = $podcast->link;
            }

            $podcasts->appends('show_id', $show_id);

            return response()->json([
                'show' => $show,
                'podcasts' => $podcasts,
            ]);
        }

        foreach ($podcasts as $podcast) {
            $podcast['date'] = date('M d, Y', strtotime($podcast['date']));
            $podcast['time_elapsed'] = $this->timePassedSincePublished($podcast['date']);
            $podcast['short_episode'] = Str::limit($podcast['episode'], 16);
            $podcast['image'] = $this->verifyPhoto($podcast['image'], 'podcasts');
            $podcast['stream'] = $podcast->link;
        }

        foreach ($shows as $show) {
            $show->background_image = $this->verifyPhoto($show->background_image, 'shows');
            $show->icon = $this->verifyPhoto($show->icon, 'shows');
            $show->header_image = $this->verifyPhoto($show->header_image, 'shows');
        }

        return response()->json([
            'shows' => $shows,
            'podcasts' => $podcasts,
            'next' => $podcasts->nextPageUrl(), // to be removed when the mobile app is released
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $podcast = Podcast::with('Show')->where('location', $this->getStationCode())->findOrFail($id);

        $podcast['image'] = $this->verifyPhoto($podcast['image'], 'podcasts');
        $podcast['show']['background_image'] = $this->verifyPhoto($podcast['show']['background_image'], 'shows');
        $podcast['stream'] = $podcast->link;

        return response()->json([
            'podcast' => $podcast,
        ]);
    }
}

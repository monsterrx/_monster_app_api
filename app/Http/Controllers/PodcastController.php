<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\MediaProcessors;
use App\Traits\SystemFunctions;
use Illuminate\Http\Request;
use Str;
use App\Models\Podcast;
use App\Models\Show;

class PodcastController extends Controller {
    use MediaProcessors, SystemFunctions;

    public function index(Request $request) {
        $podcasts = Podcast::with('Show.Jock')
            ->whereNull('deleted_at')
            ->where('location', $this->getStationCode())
            ->orderBy('date', 'desc')
            ->paginate(8);

        // for podcast filtering
        $show_id = $request->get('show_id');
        $keyword = $request->get('keyword');

        if($show_id) {
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
                        'keyword' => $keyword
                    ]);
            }

            $show = Show::query()
                ->with('Jock')
                ->findOrFail($show_id);

            $show->background_image = $this->verifyPhoto($show->background_image, 'shows');

            foreach ($podcasts as $podcast) {
                $podcast['date'] = date('M d, Y', strtotime($podcast['date']));
                $podcast['time_elapsed'] = $this->timePassedSincePublished($podcast['date']);
                $podcast['short_episode'] = mb_strimwidth($podcast['episode'], 0, 16, '...');
                $podcast['image'] = $this->verifyPhoto($podcast['image'], 'podcasts');
                $podcast['stream'] = $podcast->link;
            }

            $podcasts->appends('show_id', $show_id);

            return response()->json([
                'show' => $show,
                'podcasts' => $podcasts
            ]);
        }

        foreach ($podcasts as $podcast) {
            $podcast['date'] = date('M d, Y', strtotime($podcast['date']));
            $podcast['time_elapsed'] = $this->timePassedSincePublished($podcast['date']);
            $podcast['short_episode'] = mb_strimwidth($podcast['episode'], 0, 16, '...');
            $podcast['image'] = $this->verifyPhoto($podcast['image'], 'podcasts');
            $podcast['stream'] = $podcast->link;
        }

        return response()->json([
            'podcasts' => $podcasts,
            'next' => $podcasts->nextPageUrl() // to be removed when the mobile app is released
        ]);
    }

    public function show($id) {
        $podcast = Podcast::with('Show')->findOrFail($id);

        $podcast['image'] = $this->verifyPhoto($podcast['image'], 'podcasts');
        $podcast['show']['background_image'] = $this->verifyPhoto($podcast['show']['background_image'], 'shows');
        $podcast['stream'] = $podcast->link;

        return response()->json([
            'podcast' => $podcast
        ]);
    }
}
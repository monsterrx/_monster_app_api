<?php

namespace App\Http\Controllers;

use App\Models\Podcast;
use App\Models\Show;
use App\Services\ContentSearch;
use App\Traits\MediaProcessors;
use App\Traits\SystemFunctions;
use Illuminate\Http\Request;
use Str;

class PodcastController extends Controller
{
    use MediaProcessors, SystemFunctions;

    public function index(Request $request)
    {
        $request->validate(['keyword' => 'nullable|string|max:160', 'show_id' => 'nullable|integer|min:1']);
        $show_id = $request->get('show_id');
        $keyword = trim((string) $request->get('keyword', ''));
        $show = null;
        if ($show_id) {
            $show = Show::with('Jock')
                ->whereNull('deleted_at')
                ->where('location', $this->getStationCode())
                ->findOrFail($show_id);
        }

        $query = Podcast::with('Show.Jock')
            ->whereNull('deleted_at')
            ->where('location', $this->getStationCode());
        if ($show_id) {
            $query->where('show_id', $show_id);
        }
        if ($keyword !== '') {
            ContentSearch::podcasts($query, $keyword);
        }
        $podcasts = $query->orderBy('date', 'desc')->orderBy('id', 'desc')->paginate(8)
            ->appends(array_filter([
                'show_id' => $show_id,
                'keyword' => $keyword,
            ], function ($value) {
                return $value !== null && $value !== '';
            }));

        $showImages = [];
        foreach ($podcasts as $podcast) {
            $podcast->time_elapsed = $this->timePassedSincePublished($podcast->date);
            $podcast->date = date('M d, Y', strtotime($podcast->date));
            $podcast->short_episode = Str::limit($podcast->episode, 16);
            $podcast->image = $this->verifyPhoto($podcast->image, 'podcasts');
            $podcast->stream = $podcast->link;
            if ($podcast->Show) {
                $relatedShow = $podcast->Show;
                if (! isset($showImages[$relatedShow->id])) {
                    $showImages[$relatedShow->id] = $this->verifyPhoto($relatedShow->background_image, 'shows');
                }
                $relatedShow->background_image = $showImages[$relatedShow->id];
            }
        }

        $payload = [
            'podcasts' => $podcasts,
            'next' => $podcasts->nextPageUrl(),
            'search' => ['keyword' => $keyword, 'show_id' => $show_id, 'fields' => ['title', 'show_name']],
        ];
        if ($show) {
            $show->icon = $this->verifyPhoto($show->icon, 'shows');
            $show->background_image = $this->verifyPhoto($show->background_image, 'shows');
            $show->header_image = $this->verifyPhoto($show->header_image, 'shows');
            $payload['show'] = $show;
        }

        return response()->json($payload);
    }

    public function show($id)
    {
        $podcast = Podcast::with('Show')->findOrFail($id);

        $podcast['image'] = $this->verifyPhoto($podcast['image'], 'podcasts');
        $podcast['show']['background_image'] = $this->verifyPhoto($podcast['show']['background_image'], 'shows');
        $podcast['stream'] = $podcast->link;

        return response()->json([
            'podcast' => $podcast,
        ]);
    }
}

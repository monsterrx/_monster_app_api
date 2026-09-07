<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Models\Chart;
use App\Models\Contest;
use App\Models\Podcast;
use App\Models\Show;
use App\Traits\ChartFunctions;
use App\Traits\JockFunctions;
use App\Traits\MediaProcessors;
use App\Traits\SystemFunctions;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    use ChartFunctions, JockFunctions, MediaProcessors, SystemFunctions;

    public function index(Request $request): JsonResponse
    {
        $day = Carbon::now()->format('l');
        $time = date('H:i');

        $session_id = $this->IdGenerator(10);

        $categories = Category::query()
            ->has('Article', '>', 0)
            ->whereHas('Article', function (Builder $query) {
                $query->whereNull('deleted_at')
                    ->whereNotNull('published_at')
                    ->where('location', $this->getStationCode());
            })
            ->orderBy('name')
            ->get();

        $giveaway = Contest::whereNull('deleted_at')
            ->orderBy('type')
            ->where('is_active', 1)
            ->where('location', $this->getStationCode())
            ->get();

        $charts = Chart::with('Song.Album.Artist')
            ->whereNull('deleted_at')
            ->where('dated', $this->getLatestChartDate())
            ->where('daily', 0)
            ->where('is_posted', 1)
            ->where('location', $this->getStationCode())
            ->orderBy('position')
            ->limit(5)->get();

        $articles = Article::with('Employee', 'Category')
            ->latest()
            ->whereNull('deleted_at')
            ->whereNotNull('published_at')
            ->where('location', $this->getStationCode())
            ->orderBy('published_at', 'desc')
            ->limit(5)->get();

        $podcasts = Podcast::with('Show')
            ->latest()
            ->whereNull('deleted_at')
            ->where('show_id', 1) // The Morning Rush
            ->where('location', $this->getStationCode())
            ->limit(5)->get();

        $currentShow = Show::with('Timeslot')
            ->whereHas('Timeslot', function ($query) {
                $day = Carbon::now()->format('l');
                $time = date('H:i');

                $query->whereNull('deleted_at')
                    ->where('end', '>', $time)
                    ->where('start', '<=', $time)
                    ->where('day', '=', $day)
                    ->where('location', $this->getStationCode())
                    ->orderBy('start');
            })->whereNull('deleted_at')
            ->first();

        $shows = Show::has('Podcast')
            ->where('location', $this->getStationCode())
            ->get();

        $chart_date = $charts->isEmpty() ? null : date('F d, Y', strtotime($charts->first()->dated));

        $jocks = $this->jocksQuery($time, $day);

        foreach ($charts as $chart) {
            $chart['song_artist'] = $chart->Song->Album->Artist->name;
            $chart['song_name'] = $chart->Song->name;
            $chart['album_image'] = $this->verifyPhoto($chart->Song->Album->image, 'albums');
        }

        foreach ($articles as $article) {
            $article->image = $this->verifyPhoto($article['image'], 'articles');
        }

        foreach ($podcasts as $podcast) {
            $podcast->image = $this->verifyPhoto($podcast['image'], 'podcasts');
        }

        foreach ($jocks as $jock) {
            $jock->profile_image = $this->verifyPhoto($jock->profile_image, 'jocks');
        }

        foreach ($shows as $show) {
            $show->background_image = $this->verifyPhoto($show->background_image, 'shows');

            $show->icon = $this->verifyPhoto($show->icon, 'shows');

            $show->header_image = $this->verifyPhoto($show->header_image, 'shows');
        }

        if ($currentShow) {
            $currentShow->background_image = $this->verifyPhoto($currentShow->background_image, 'shows');

            return response()->json([
                'giveaways' => $giveaway,
                'charts' => $charts,
                'articles' => $articles,
                'podcasts' => $podcasts,
                'shows' => $shows,
                'chart_date' => $chart_date,
                'jocks' => $jocks,
                'session_id' => $session_id,
                'categories' => $categories,
            ]);
        }

        return response()->json([
            'giveaways' => $giveaway,
            'charts' => $charts,
            'articles' => $articles,
            'podcasts' => $podcasts,
            'shows' => $shows,
            'chart_date' => $chart_date,
            'show' => $currentShow,
            'jocks' => $jocks,
            'session_id' => $session_id,
            'categories' => $categories,
        ]);
    }
}

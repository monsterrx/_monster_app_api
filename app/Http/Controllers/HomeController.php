<?php

namespace App\Http\Controllers;

use App\Models\Contest;
use App\Models\Chart;
use App\Models\Article;
use App\Models\Podcast;
use App\Models\Show;
use App\Models\Title;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HomeController extends Controller {
    public function index() {
        $session_id = $this->IdGenerator(10);

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
            ->get()
            ->take(5);

        $chart_date = date('F d, Y', strtotime($charts->first()->dated ?? date('F d, Y')));

        $articles = Article::with('Employee', 'Category')
            ->latest()
            ->whereNull('deleted_at')
            ->whereNotNull('published_at')
            ->where('location', $this->getStationCode())
            ->orderBy('published_at', 'desc')
            ->get()
            ->take(5);

        foreach ($charts as $chart) {
            $chart['song_artist'] = $chart->Song->Album->Artist->name;
            $chart['song_name'] = $chart->Song->name;
            $chart['album_image'] = $this->verifyPhoto($chart->Song->Album->image, 'albums');
        }

        foreach ($articles as $article) {
            $article->image = $this->verifyPhoto($article['image'], 'articles');
        }

        return response()->json([
            'giveaways' => $giveaway,
            'charts' => $charts,
            'chart_date' => $chart_date,
            'articles' => $articles,
            'session_id' => $session_id
        ]);
    }

    public function assets($id, Request $request) {
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
            'title' => $title
        ]);
    }
}
<?php

namespace App\Http\Controllers\API;

use App\Models\Title;
use App\Services\ContentSearch;
use App\Traits\AssetProcessors;
use App\Traits\ChartFunctions;
use App\Traits\JockFunctions;
use App\Traits\MediaProcessors;
use App\Traits\SystemFunctions;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Exception;
use Str;
use Carbon\Carbon;
use App\Models\Show;
use App\Models\Chart;
use App\Models\Article;
use App\Models\Contest;
use App\Models\Podcast;
use App\Models\Timeslot;
use App\Models\Category;
use App\Models\Asset;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class MobileAppController extends Controller
{
    use SystemFunctions, JockFunctions, AssetProcessors, MediaProcessors, ChartFunctions;

    public function home(Request $request) {
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
            ->get()
            ->take(5);

        $articles = Article::with('Employee', 'Category')
            ->latest()
            ->whereNull('deleted_at')
            ->whereNotNull('published_at')
            ->where('location', $this->getStationCode())
            ->orderBy('published_at', 'desc')
            ->get()
            ->take(5);

        $podcasts = Podcast::with('Show')
            ->latest()
            ->whereNull('deleted_at')
            ->where('show_id', 1) // The Morning Rush
            ->where('location', $this->getStationCode())
            ->get()
            ->take(5);

        $currentShow = Show::with('Timeslot')
            ->whereHas('Timeslot', function($query) {
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

        $chart_date = date('F d, Y', strtotime($charts->first()->dated));

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

        if($currentShow) {
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
                'categories' => $categories
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
            'categories' => $categories
        ]);
    }

    public function live() {
        // Variables on getting the day and time
        $day = Carbon::now()->format('l');
        $time = date('H:i');

        // 20240924 Update
        $stream = $this->getStream();

        $currentShow = Show::query()
            ->whereHas('Timeslot', function($query) use ($day, $time) {
                $query->whereNull('deleted_at')
                    ->where('end', '>', $time)
                    ->where('start', '<=', $time)
                    ->where('day', '=', $day)
                    ->where('location', $this->getStationCode())
                    ->orderBy('start');
            })->whereNull('deleted_at')
            ->first();

        if ($currentShow == null) {
            return response()->json(['show' => $currentShow, 'live' => $stream]);
        }

        $currentShow['background_image'] = $this->verifyPhoto($currentShow['background_image'], 'shows');
        $currentShow['icon'] = $this->verifyPhoto($currentShow['icon'], 'shows');
        $currentShow['header_image'] = $this->verifyPhoto($currentShow['header_image'], 'shows');

        $showList = Timeslot::with('Show')
            ->whereNull('deleted_at')
            ->where('day', $day)
            ->where('location', $this->getStationCode())
            ->orderBy('start')
            ->get();

        foreach ($showList as $timeslot) {
            $timeslot['start'] = date('h:i A', strtotime($timeslot['start']));
            $timeslot['end'] = date('h:i A', strtotime($timeslot['end']));
        }

        $currentJocks = $this->jocksQuery($time, $day);

        foreach ($currentJocks as $jock) {
            $jock->profile_image = $this->verifyPhoto($jock->profile_image, 'jocks');
            $jock->background_image = $this->verifyPhoto($jock->background_image, 'jocks');
            $jock->main_image = $this->verifyPhoto($jock->main_image, 'jocks');
        }

        return response()->json([
            'show' => $currentShow,
            'jocks' => $currentJocks,
            'live' => $stream
        ]);
    }

    public function charts() {
        $charts = Chart::with('Song.Album.Artist')
            ->where('dated', $this->getLatestChartDate())
            ->where('daily', 0)
            ->where('is_posted', 1)
            ->where('location', $this->getStationCode())
            ->orderBy('position')
            ->get();

        foreach ($charts as $chart) {
            $chart->album_image = $this->verifyPhoto($chart->Song->Album->image, 'albums');
        }

        return response()->json([
            'charts' => $charts
        ]);
    }

    public function articles(Request $request) {
        $request->validate(['keyword' => 'nullable|string|max:160', 'category_id' => 'nullable|integer|min:1']);
        $categories = Category::query()
            ->has('Article', '>', 0)
            ->whereHas('Article', function (Builder $query) {
                $query->whereNull('deleted_at')
                    ->whereNotNull('published_at')
                    ->where('location', $this->getStationCode());
            })
            ->orderBy('name')
            ->get();

        $category_id = $request->get('category_id');
        $keyword = trim((string) $request->get('keyword', ''));
        $selectedCategory = null;
        if ($category_id) {
            try {
                $selectedCategory = Category::query()->findOrFail($category_id);
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                return response()->json(['error' => 'Category not found'], 400);
            }
        }

        $query = Article::with('Employee', 'Category')
            ->whereNull('deleted_at')
            ->whereNotNull('published_at')
            ->where('location', $this->getStationCode());

        if ($category_id) {
            $query->where('category_id', $category_id);
        }
        if ($keyword !== '') {
            ContentSearch::articles($query, $keyword);
        }

        $articles = $query->orderBy('created_at', 'desc')->paginate(8)
            ->appends(array_filter([
                'category_id' => $category_id,
                'keyword' => $keyword,
            ], function ($value) { return $value !== null && $value !== ''; }));

        foreach ($categories as $category) {
            $category->icon = $this->verifyPhoto($category->icon, '_assets/categories');
            $category->dark_mode_icon = $this->verifyPhoto($category->dark_mode_icon, '_assets/categories');
        }
        foreach ($articles as $article) {
            $article->image = $this->verifyPhoto($article->image, 'articles');
            $article->author = $article->Employee ? trim($article->Employee->first_name . ' ' . $article->Employee->last_name) : '';
            $article->time_passed = $this->timePassedSincePublished($article->published_at);
        }

        $payload = [
            'articles' => $articles,
            'next' => $articles->nextPageUrl(),
            'search' => ['keyword' => $keyword, 'category_id' => $category_id, 'fields' => ['title', 'author']],
        ];
        if ($selectedCategory) {
            $payload['category'] = $selectedCategory;
        } else {
            $payload['categories'] = $categories;
        }
        return response()->json($payload);
    }

    public function viewArticle($id) {
        try {
            $article = Article::query()
                ->with('Employee', 'Category' ,'Content', 'Relevant')
                ->findOrFail($id);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Article not found'
            ], 400);
        }

        $article->image = $this->verifyPhoto($article->image, 'articles');

        $article->update_date = date('F d, Y', strtotime($article->updated_at));
        $article->publish_date = date('F d, Y', strtotime($article->published_at));
        $article->time_passed = $this->timePassedSincePublished($article->published_at);
        $article->author = $article->Employee->first_name . ' ' . $article->Employee->last_name;

        foreach ($article->Content as $articleContent) {
            $articleContent->content = $this->replaceSourceText($articleContent->content);
            $articleContent->content = $this->replaceArticleSourceText($articleContent->content);
        }

        return response()->json([
            'article' => $article
        ]);
    }

    public function podcasts(Request $request) {
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
            ], function ($value) { return $value !== null && $value !== ''; }));

        $showImages = [];
        foreach ($podcasts as $podcast) {
            $podcast->time_elapsed = $this->timePassedSincePublished($podcast->date);
            $podcast->date = date('M d, Y', strtotime($podcast->date));
            $podcast->short_episode = Str::limit($podcast->episode, 16);
            $podcast->image = $this->verifyPhoto($podcast->image, 'podcasts');
            $podcast->stream = $podcast->link;
            if ($podcast->Show) {
                $relatedShow = $podcast->Show;
                if (!isset($showImages[$relatedShow->id])) {
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
        } else {
            $shows = Show::has('Podcast')->withCount(['Podcast' => function ($query) {
                    $query->whereNull('deleted_at')->where('location', $this->getStationCode());
                }])->with('Jock')->whereNull('deleted_at')
                ->where('location', $this->getStationCode())->orderBy('podcast_count', 'desc')->get();
            foreach ($shows as $availableShow) {
                $availableShow->background_image = $this->verifyPhoto($availableShow->background_image, 'shows');
                $availableShow->icon = $this->verifyPhoto($availableShow->icon, 'shows');
                $availableShow->header_image = $this->verifyPhoto($availableShow->header_image, 'shows');
            }
            $payload['shows'] = $shows;
        }
        return response()->json($payload);
    }

    public function viewPodcast($id) {
        $podcast = Podcast::with('Show')->findOrFail($id);

        $podcast['image'] = $this->verifyPhoto($podcast['image'], 'podcasts');
        $podcast['show']['background_image'] = $this->verifyPhoto($podcast['show']['background_image'], 'shows');
        $podcast['stream'] = $podcast->link;

        return response()->json([
            'podcast' => $podcast
        ]);
    }

    public function youTube($max) {
        $channel_id = 'UCMgKa-bzBoj40sQUqvX7kag';
        $channel_key = 'AIzaSyDv1JDmiKR1QiLeKaUIWtWTA45Ay-cUmyk'; // AIzaSyAr-GrOSqC0o3-d-DPbkUM4E7kOZ76KRNA
        $channel_max = $max;

        $data = array('id' => $channel_id, 'key' => $channel_key, 'max' => $channel_max);

        return response()->json($data);
    }

    public function search(Request $request) {
        return view('components.search');
    }

    public function browse(Request $request) {
        $search = $request->keyword;

        $podcasts = Podcast::with('Show')
            ->whereNull('deleted_at')
            ->where('location', $this->getStationCode())
            ->orderBy('date', 'desc')
            ->get()
            ->take(5);

        $articles = Article::with('Category', 'Employee')
            ->whereNull('deleted_at')
            ->whereNotNull('published_at')
            ->orderBy('created_at', 'desc')
            ->get()
            ->take(5);

        if($search) {
            $podcasts = Podcast::with('Show')
                ->whereNull('deleted_at')
                ->where('episode', 'like', '%'.$search.'%')
                ->orderByDesc('created_at')
                ->paginate(5)
                ->appends('keyword', $search);

            $articles = Article::whereNull('deleted_at')
                ->whereNotNull('published_at')
                ->where('title', 'like', '%'.$search.'%')
                ->orWhere('heading', 'like', '%'.$search.'%')
                ->orderByDesc('created_at')
                ->paginate(5)
                ->appends('keyword', $search);
        }

        foreach ($podcasts as $podcast) {
            $podcast['image'] = $this->verifyPhoto($podcast['image'], 'podcasts');
        }

        foreach ($articles as $article) {
            $article['image'] = $this->verifyPhoto($article['image'], 'articles');
        }

        return response()->json([
            'podcasts' => $podcasts,
            'articles' => $articles
        ]);
    }

    public function help() {
        return view('help');
    }

    public function about() {
        return view('about');
    }

    public function vote(Request $request) {
        $chart_id = $request->chart_id;

        $chart = Chart::with('Song.Album.Artist')->findOrFail($chart_id);

        $chart->voted_at = date('Y-m-d');
        ++$chart->phone_votes;
        $chart->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Vote Sent!'
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

    public function shows() {
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
            'shows' => $shows
        ]);
    }

    public function viewShow($id) {
        $show = Show::query()
            ->with(['Podcast', 'Jock'])
            ->findOrFail($id);

        $show->background_image = $this->verifyPhoto($show->background_image, 'shows');
        $show->icon = $this->verifyPhoto($show->icon, 'shows');
        $show->header_image = $this->verifyPhoto($show->header_image, 'shows');

        foreach ($show->Podcast as $podcast) {
            $podcast->image = $this->verifyPhoto($podcast->image, 'podcasts');
        }

        return response()->json([
            'show' => $show
        ]);
    }
}

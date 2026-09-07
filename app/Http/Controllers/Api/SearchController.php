<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Podcast;
use App\Traits\MediaProcessors;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    use MediaProcessors;

    public function index(): View
    {
        return view('components.search');
    }

    public function browse(Request $request): JsonResponse
    {
        $data = $request->validate(['keyword' => ['nullable', 'string', 'max:200'], 'page' => ['nullable', 'integer', 'min:1']]);
        $podcasts = Podcast::with('Show')->where('location', $this->getStationCode());
        $articles = Article::with('Category', 'Employee')->where('location', $this->getStationCode())->whereNotNull('published_at');
        if (! empty($data['keyword'])) {
            $keyword = '%'.$data['keyword'].'%';
            $podcasts->where('episode', 'like', $keyword);
            $articles->where(fn (Builder $query) => $query->where('title', 'like', $keyword)->orWhere('heading', 'like', $keyword));
            $podcasts = $podcasts->orderByDesc('created_at')->paginate(5)->withQueryString();
            $articles = $articles->orderByDesc('created_at')->paginate(5)->withQueryString();
        } else {
            $podcasts = $podcasts->orderByDesc('date')->limit(5)->get();
            $articles = $articles->orderByDesc('created_at')->limit(5)->get();
        }
        foreach ($podcasts as $podcast) {
            $podcast->image = $this->verifyPhoto($podcast->image, 'podcasts');
        }
        foreach ($articles as $article) {
            $article->image = $this->verifyPhoto($article->image, 'articles');
        }

        return response()->json(['podcasts' => $podcasts, 'articles' => $articles]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Traits\MediaProcessors;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    use MediaProcessors;

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category_id' => ['nullable', 'integer', 'min:1'],
            'keyword' => ['nullable', 'string', 'max:200'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $categories = Category::whereHas('Article', function (Builder $query): void {
            $query->whereNotNull('published_at')->where('location', $this->getStationCode());
        })->orderBy('name')->get();
        $query = Article::with('Employee', 'Category')->whereNotNull('published_at')->where('location', $this->getStationCode());
        $category = null;
        if (! empty($data['category_id'])) {
            $category = Category::findOrFail($data['category_id']);
            $query->where('category_id', $category->id);
        }
        if (! empty($data['keyword'])) {
            $keyword = '%'.$data['keyword'].'%';
            $query->where(function (Builder $query) use ($keyword): void {
                $query->where('title', 'like', $keyword)->orWhere('heading', 'like', $keyword)->orWhereHas('Employee', function (Builder $author) use ($keyword): void {
                    $author->where('first_name', 'like', $keyword)->orWhere('last_name', 'like', $keyword);
                });
            });
        }
        $articles = $query->orderByDesc('created_at')->paginate(8)->withQueryString();
        foreach ($articles as $article) {
            $this->present($article);
        }
        foreach ($categories as $item) {
            $item->icon = $this->verifyPhoto($item->icon, '_assets/categories');
            $item->dark_mode_icon = $this->verifyPhoto($item->dark_mode_icon, '_assets/categories');
        }

        return response()->json([
            $category ? 'category' : 'categories' => $category ?? $categories,
            'articles' => $articles,
            'next' => $articles->nextPageUrl(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $article = Article::with('Employee', 'Category', 'Content', 'Relevant')
            ->where('location', $this->getStationCode())->whereNotNull('published_at')->findOrFail($id);
        $this->present($article);
        $article->update_date = $article->updated_at?->format('F d, Y');
        $article->publish_date = date('F d, Y', strtotime($article->published_at));
        foreach ($article->Content as $content) {
            $content->content = $this->replaceArticleSourceText($this->replaceSourceText($content->content));
        }

        return response()->json(['article' => $article]);
    }

    private function present(Article $article): void
    {
        $article->image = $this->verifyPhoto($article->image, 'articles');
        $article->author = trim(($article->Employee?->first_name ?? '').' '.($article->Employee?->last_name ?? ''));
        $article->time_passed = $this->timePassedSincePublished($article->published_at);
    }
}

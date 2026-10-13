<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Services\ContentSearch;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['keyword' => 'nullable|string|max:160', 'category_id' => 'nullable|integer|min:1']);
        $category_id = $request->get('category_id');
        $keyword = trim((string) $request->get('keyword', ''));
        $selectedCategory = null;
        if ($category_id) {
            try {
                $selectedCategory = Category::query()->findOrFail($category_id);
            } catch (ModelNotFoundException $e) {
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

        $articles = $query->orderBy('created_at', 'desc')->orderBy('id', 'desc')->paginate(8)
            ->appends(array_filter([
                'category_id' => $category_id,
                'keyword' => $keyword,
            ], function ($value) {
                return $value !== null && $value !== '';
            }));

        foreach ($articles as $article) {
            $article->image = $this->verifyPhoto($article->image, 'articles');
            $article->author = $article->Employee ? trim($article->Employee->first_name.' '.$article->Employee->last_name) : '';
            $article->time_passed = $this->timePassedSincePublished($article->published_at);
        }

        $payload = [
            'articles' => $articles,
            'next' => $articles->nextPageUrl(),
            'search' => ['keyword' => $keyword, 'category_id' => $category_id, 'fields' => ['title', 'author']],
        ];
        if ($selectedCategory) {
            $payload['category'] = $selectedCategory;
        } elseif ($keyword === '') {
            $categories = Category::query()
                ->has('Article', '>', 0)
                ->whereHas('Article', function (Builder $query) {
                    $query->whereNull('deleted_at')
                        ->whereNotNull('published_at')
                        ->where('location', $this->getStationCode());
                })
                ->orderBy('name')
                ->get();
            foreach ($categories as $category) {
                $category->icon = $this->verifyPhoto($category->icon, '_assets/categories');
                $category->dark_mode_icon = $this->verifyPhoto($category->dark_mode_icon, '_assets/categories');
            }
            $payload['categories'] = $categories;
        }

        return response()->json($payload);
    }

    public function show($id)
    {
        try {
            $article = Article::query()
                ->with('Employee', 'Category', 'Content', 'Relevant')
                ->findOrFail($id);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Article not found',
            ], 400);
        }

        $article->image = $this->verifyPhoto($article->image, 'articles');

        $article->update_date = date('F d, Y', strtotime($article->updated_at));
        $article->publish_date = date('F d, Y', strtotime($article->published_at));
        $article->time_passed = $this->timePassedSincePublished($article->published_at);
        $article->author = $article->Employee->first_name.' '.$article->Employee->last_name;

        foreach ($article->Content as $articleContent) {
            $articleContent->content = $this->replaceSourceText($articleContent->content);
            $articleContent->content = $this->replaceArticleSourceText($articleContent->content);
        }

        return response()->json([
            'article' => $article,
        ]);
    }
}

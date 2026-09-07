<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Article;
use App\Models\Category;
use Exception;
use Illuminate\Database\Eloquent\Builder;

class ArticleController extends Controller {
    public function index(Request $request) {
        $articles = Article::with('Employee', 'Category')
            ->whereNull('deleted_at')
            ->whereNotNull('published_at')
            ->where('location', $this->getStationCode())
            ->orderBy('created_at', 'desc')
            ->paginate(4);

        // for article filtering
        $category_id = $request->get('category_id');
        $keyword = $request->get('keyword');

        if($category_id) {
            try {
                $category = Category::query()
                    ->findOrFail($category_id);
            } catch (Exception $e) {
                return response()->json([
                    'error' => 'Category not found'
                ], 400);
            }

            $articles = Article::with('Employee')
                ->where('category_id', $category_id)
                ->latest()
                ->where('location', $this->getStationCode())
                ->whereNotNull('published_at')
                ->paginate(4)
                ->appends('category_id', $category_id);

            if ($keyword) {
                $articles = Article::with('Employee')
                    ->where('category_id', $category_id)
                    ->where(function($query) use ($keyword) {
                        $query->where('title', 'like', '%'.$keyword.'%')
                            ->orWhere('heading', 'like', '%'.$keyword.'%')
                            ->orWhereHas('Employee', function(Builder $query) use ($keyword) {
                                $query->where('first_name', 'like', '%'.$keyword.'%')
                                    ->orWhere('last_name', 'like', '%'.$keyword.'%');
                            });
                    })
                    ->whereNotNull('published_at')
                    ->where('location', $this->getStationCode())
                    ->orderBy('created_at', 'desc')
                    ->paginate(4)
                    ->appends([
                        'category_id' => $category_id,
                        'keyword' => $keyword
                    ]);
            }

            foreach ($articles as $article) {
                $article->image = $this->verifyPhoto($article->image, 'articles');
                $article->author = $article->Employee->first_name . ' ' . $article->Employee->last_name;
                $article->time_passed = $this->timePassedSincePublished($article->published_at);
            }

            return response()->json([
                'category' => $category,
                'articles' => $articles,
                'next' => $articles->nextPageUrl()
            ]);
        }

        foreach ($articles as $article) {
            $article->image = $this->verifyPhoto($article->image, 'articles');
            $article->author = $article->Employee->first_name . ' ' . $article->Employee->last_name;
            $article->time_passed = $this->timePassedSincePublished($article->published_at);
        }

        return response()->json([
            'articles' => $articles,
            'next' => $articles->nextPageUrl()
        ]);
    }

    public function show($id) {
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
}
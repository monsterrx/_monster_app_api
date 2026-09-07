<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Request;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;

class CategoryController extends Controller {
    public function index(Request $request) {
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

        return response()->json([
            'categories' => $categories
        ]);
    }
}
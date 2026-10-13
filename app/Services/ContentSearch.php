<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

class ContentSearch
{
    public static function pattern(string $keyword): string
    {
        // Treat SQL wildcard characters as part of the user's title or name.
        return '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim($keyword)).'%';
    }

    public static function articles(Builder $query, string $keyword): Builder
    {
        $pattern = self::pattern($keyword);

        return $query->where(function (Builder $matches) use ($pattern) {
            $matches->whereRaw("title LIKE ? ESCAPE '!'", [$pattern])
                ->orWhereHas('Employee', function (Builder $author) use ($pattern) {
                    $author->whereRaw("TRIM(CONCAT_WS(' ', first_name, last_name)) LIKE ? ESCAPE '!'", [$pattern]);
                });
        });
    }

    public static function podcasts(Builder $query, string $keyword): Builder
    {
        $pattern = self::pattern($keyword);

        return $query->where(function (Builder $matches) use ($pattern) {
            $matches->whereRaw("episode LIKE ? ESCAPE '!'", [$pattern])
                ->orWhereHas('Show', function (Builder $show) use ($pattern) {
                    $show->whereNull('deleted_at')->whereRaw("title LIKE ? ESCAPE '!'", [$pattern]);
                });
        });
    }
}

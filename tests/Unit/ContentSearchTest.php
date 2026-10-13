<?php

namespace Tests\Unit;

use App\Models\Article;
use App\Models\Podcast;
use App\Services\ContentSearch;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Pagination\Paginator;
use PHPUnit\Framework\TestCase;

class ContentSearchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $db = new Capsule();
        $db->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $db->setAsGlobal();
        $db->bootEloquent();
        $db->getConnection()->getPdo()->sqliteCreateFunction('CONCAT_WS', function ($separator, ...$parts) {
            return implode($separator, array_filter($parts, function ($part) { return $part !== null; }));
        });
        Capsule::schema()->create('employees', function (Blueprint $table) {
            $table->id(); $table->string('first_name'); $table->string('last_name'); $table->softDeletes();
        });
        Capsule::schema()->create('articles', function (Blueprint $table) {
            $table->id(); $table->unsignedInteger('employee_id'); $table->unsignedInteger('category_id');
            $table->string('title'); $table->string('heading'); $table->string('location');
            $table->timestamp('published_at')->nullable(); $table->softDeletes();
        });
        Capsule::schema()->create('shows', function (Blueprint $table) {
            $table->id(); $table->string('title'); $table->softDeletes();
        });
        Capsule::schema()->create('podcasts', function (Blueprint $table) {
            $table->id(); $table->unsignedInteger('show_id'); $table->string('episode');
            $table->string('location'); $table->softDeletes();
        });
        Capsule::table('employees')->insert(['id' => 1, 'first_name' => 'Roanne', 'last_name' => 'Marquez']);
        Capsule::table('shows')->insert([['id' => 1, 'title' => 'The Morning Rush'], ['id' => 2, 'title' => 'All Out']]);
        Paginator::currentPathResolver(function () { return 'https://example.test/podcasts'; });
        Paginator::currentPageResolver(function () { return 1; });
    }

    private function article(array $changes = []): void
    {
        Capsule::table('articles')->insert(array_merge([
            'employee_id' => 1, 'category_id' => 8, 'title' => 'Music Festival', 'heading' => 'Body-only phrase',
            'location' => 'mnl', 'published_at' => '2026-10-13 12:00:00', 'deleted_at' => null,
        ], $changes));
    }

    private function articles(string $keyword, $category = null): array
    {
        $query = Article::whereNull('deleted_at')->whereNotNull('published_at')->where('location', 'mnl');
        if ($category !== null) $query->where('category_id', $category);
        return ContentSearch::articles($query, $keyword)->pluck('id')->all();
    }

    public function test_partial_titles_and_full_author_names_do_not_search_article_body(): void
    {
        $this->article();
        $this->assertCount(1, $this->articles('Festival'));
        $this->assertCount(1, $this->articles('anne Marq'));
        $this->assertCount(1, $this->articles('Roanne Marquez'));
        $this->assertCount(0, $this->articles('Body-only phrase'));
    }

    public function test_author_matches_cannot_bypass_station_publication_deletion_or_category_filters(): void
    {
        $this->article();
        $this->article(['location' => 'ceb']);
        $this->article(['published_at' => null]);
        $this->article(['deleted_at' => '2026-10-13']);
        $this->article(['category_id' => 9]);
        $this->assertCount(2, $this->articles('Marquez'));
        $this->assertCount(1, $this->articles('Marquez', 8));
    }

    public function test_wildcards_and_quotes_are_literal_search_text(): void
    {
        $this->article(['title' => "100%_sure! O'Brien"]);
        $this->article(['title' => '100xxsure']);
        $this->assertCount(1, $this->articles('%_sure!'));
        $this->assertCount(1, $this->articles("O'Brien"));
        $this->assertCount(0, $this->articles("' OR 1=1 --"));
    }

    public function test_episode_titles_and_show_names_work_globally_and_inside_a_show(): void
    {
        Capsule::table('podcasts')->insert([
            ['id' => 1, 'show_id' => 1, 'episode' => 'Forever Hold My Peace', 'location' => 'mnl'],
            ['id' => 2, 'show_id' => 1, 'episode' => 'Delusional Confidence', 'location' => 'mnl'],
            ['id' => 3, 'show_id' => 2, 'episode' => 'Morning Rush memories', 'location' => 'mnl'],
            ['id' => 4, 'show_id' => 1, 'episode' => 'Other station', 'location' => 'ceb'],
        ]);
        $query = function () { return Podcast::whereNull('deleted_at')->where('location', 'mnl'); };
        $this->assertSame([1], ContentSearch::podcasts($query(), 'Hold My')->pluck('id')->all());
        $this->assertSame([1, 2, 3], ContentSearch::podcasts($query(), 'Morning Rush')->pluck('id')->all());
        $this->assertSame([1, 2], ContentSearch::podcasts($query()->where('show_id', 1), 'Morning Rush')->pluck('id')->all());
        $this->assertSame([], ContentSearch::podcasts($query()->where('show_id', 1), 'All Out')->pluck('id')->all());
    }

    public function test_deleted_shows_and_episodes_do_not_match_by_show_name_and_pagination_retains_scope(): void
    {
        Capsule::table('shows')->insert(['id' => 3, 'title' => 'Morning Rush deleted', 'deleted_at' => '2026-10-13']);
        Capsule::table('podcasts')->insert(['show_id' => 3, 'episode' => 'Unavailable show', 'location' => 'mnl']);
        Capsule::table('podcasts')->insert(['show_id' => 1, 'episode' => 'Deleted episode', 'location' => 'mnl', 'deleted_at' => '2026-10-13']);
        for ($i = 0; $i < 9; $i++) Capsule::table('podcasts')->insert(['show_id' => 1, 'episode' => 'Episode ' . $i, 'location' => 'mnl']);
        $query = Podcast::whereNull('deleted_at')->where('location', 'mnl');
        $page = ContentSearch::podcasts($query, 'Morning Rush')->orderBy('id')->paginate(8)
            ->appends(['show_id' => 1, 'keyword' => 'Morning Rush']);
        $this->assertSame(9, $page->total());
        $this->assertCount(8, $page->items());
        parse_str(parse_url($page->nextPageUrl(), PHP_URL_QUERY), $params);
        $this->assertSame(['show_id' => '1', 'keyword' => 'Morning Rush', 'page' => '2'], $params);
    }
}

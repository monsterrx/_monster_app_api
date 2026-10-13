<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ContentSearchApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'station.code' => 'mnl', 'station.verify_media' => false]);
        DB::purge('sqlite');
        $pdo = DB::connection()->getPdo();
        $createFunction = method_exists($pdo, 'createFunction') ? 'createFunction' : 'sqliteCreateFunction';
        $pdo->$createFunction('CONCAT_WS', function ($separator, ...$parts) {
            return implode($separator, array_filter($parts, fn ($part) => $part !== null));
        });
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->string('dark_mode_icon')->nullable();
            $table->softDeletes();
        });
        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('address')->nullable();
            $table->softDeletes();
        });
        Schema::create('articles', function (Blueprint $table): void {
            $table->id();
            $table->integer('employee_id');
            $table->integer('category_id');
            $table->string('title');
            $table->string('heading');
            $table->string('image')->nullable();
            $table->string('location');
            $table->timestamp('published_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('shows', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('location');
            $table->string('background_image')->nullable();
            $table->string('icon')->nullable();
            $table->string('header_image')->nullable();
            $table->softDeletes();
        });
        Schema::create('jocks', function (Blueprint $table): void {
            $table->id();
            $table->softDeletes();
        });
        Schema::create('jock_show', function (Blueprint $table): void {
            $table->integer('jock_id');
            $table->integer('show_id');
        });
        Schema::create('podcasts', function (Blueprint $table): void {
            $table->id();
            $table->integer('show_id');
            $table->string('episode');
            $table->string('location');
            $table->string('image')->nullable();
            $table->string('link');
            $table->date('date');
            $table->softDeletes();
        });
        DB::table('categories')->insert([['id' => 1, 'name' => 'Music'], ['id' => 2, 'name' => 'Events']]);
        DB::table('employees')->insert(['id' => 1, 'first_name' => 'Roanne', 'last_name' => 'Marquez', 'address' => 'Private address']);
        foreach ([[], ['location' => 'cbu'], ['published_at' => null], ['deleted_at' => now()], ['category_id' => 2]] as $i => $changes) {
            DB::table('articles')->insert(array_merge(['id' => $i + 1, 'employee_id' => 1, 'category_id' => 1, 'title' => 'Music Festival', 'heading' => 'Body-only phrase', 'location' => 'mnl', 'published_at' => now(), 'created_at' => now(), 'updated_at' => now()], $changes));
        }
        DB::table('shows')->insert([['id' => 1, 'title' => 'The Morning Rush', 'location' => 'mnl'], ['id' => 2, 'title' => 'All Out', 'location' => 'mnl'], ['id' => 3, 'title' => 'Other Station', 'location' => 'cbu']]);
        for ($i = 1; $i <= 10; $i++) {
            DB::table('podcasts')->insert(['id' => $i, 'show_id' => $i === 10 ? 2 : 1, 'episode' => $i === 10 ? 'Morning Rush memories' : 'Episode title '.$i, 'location' => 'mnl', 'link' => 'audio.mp3', 'date' => '2026-10-13']);
        }
        DB::table('podcasts')->insert(['id' => 11, 'show_id' => 1, 'episode' => 'Other station', 'location' => 'cbu', 'link' => 'audio.mp3', 'date' => '2026-10-13']);
    }

    public function test_article_titles_and_full_author_names_search_globally_and_keep_category_scope(): void
    {
        $this->getJson('/api/mobile_app_v2/articles?keyword=Roanne%20Marquez')->assertOk()->assertJsonPath('articles.total', 2)->assertJsonPath('search.fields', ['title', 'author'])->assertJsonMissing(['address' => 'Private address']);
        $this->getJson('/api/mobile_app_v2/articles?keyword=Festival&category_id=1')->assertOk()->assertJsonCount(1, 'articles.data')->assertJsonPath('category.id', 1)->assertJsonPath('articles.data.0.id', 1);
        $this->getJson('/api/mobile_app_v2/articles?keyword=Body-only')->assertOk()->assertJsonPath('articles.total', 0);
        $this->getJson('/api/mobile_app_v2/articles')->assertOk()->assertJsonCount(2, 'categories');
    }

    public function test_show_name_and_episode_titles_search_globally_with_complete_pagination(): void
    {
        $first = $this->getJson('/api/mobile_app_v2/podcasts?keyword=Morning%20Rush')->assertOk()->assertJsonPath('podcasts.total', 10)->assertJsonCount(8, 'podcasts.data')->assertJsonPath('search.fields', ['title', 'show_name']);
        $this->getJson($first->json('podcasts.next_page_url'))->assertOk()->assertJsonCount(2, 'podcasts.data')->assertJsonPath('search.keyword', 'Morning Rush');
        $this->getJson('/api/mobile_app_v2/podcasts?keyword=title%209')->assertOk()->assertJsonCount(1, 'podcasts.data')->assertJsonPath('podcasts.data.0.id', 9)->assertJsonPath('podcasts.data.0.show.title', 'The Morning Rush');
    }

    public function test_scoped_podcast_search_cannot_escape_the_show_or_station(): void
    {
        $first = $this->getJson('/api/mobile_app_v2/podcasts?keyword=Morning%20Rush&show_id=1')->assertOk()->assertJsonPath('podcasts.total', 9)->assertJsonPath('show.id', 1);
        $this->getJson($first->json('podcasts.next_page_url'))->assertOk()->assertJsonCount(1, 'podcasts.data')->assertJsonPath('search.show_id', '1');
        $this->getJson('/api/mobile_app_v2/podcasts?keyword=All%20Out&show_id=1')->assertOk()->assertJsonPath('podcasts.total', 0);
        $this->getJson('/api/mobile_app_v2/podcasts?show_id=3')->assertNotFound();
    }

    public function test_both_search_endpoints_validate_malformed_keywords(): void
    {
        foreach (['articles', 'podcasts'] as $resource) {
            $this->getJson('/api/mobile_app_v2/'.$resource.'?keyword[]=bad')->assertUnprocessable()->assertJsonValidationErrors('keyword');
        }
    }
}

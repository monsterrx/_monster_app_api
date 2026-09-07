<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ApiMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'station.code' => 'mnl', 'station.verify_media' => false, 'station.youtube_api_key' => null]);
        DB::purge('sqlite');
        Schema::create('charts', function (Blueprint $table): void {
            $table->id();
            $table->string('location');
            $table->boolean('is_posted');
            $table->integer('phone_votes')->default(0);
            $table->date('voted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
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
            $table->integer('category_id');
            $table->integer('employee_id');
            $table->string('title');
            $table->string('heading');
            $table->string('image')->nullable();
            $table->string('location');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        DB::table('categories')->insert(['id' => 1, 'name' => 'Music']);
        DB::table('employees')->insert(['id' => 1, 'first_name' => 'Test', 'last_name' => 'Author', 'address' => 'Private address']);
        foreach (['mnl', 'cbu', 'mnl', 'mnl'] as $index => $station) {
            DB::table('articles')->insert(['id' => $index + 1, 'category_id' => 1, 'employee_id' => 1, 'title' => 'Music news', 'heading' => 'Search phrase', 'location' => $station, 'published_at' => $index === 2 ? null : now(), 'created_at' => now(), 'updated_at' => now(), 'deleted_at' => $index === 3 ? now() : null]);
        }
    }

    public function test_articles_only_return_published_station_records_and_hide_private_author_fields(): void
    {
        $this->getJson('/api/articles?keyword=Search&category_id=1')->assertOk()->assertJsonCount(1, 'articles.data')->assertJsonPath('articles.data.0.id', 1)->assertJsonPath('category.id', 1)->assertJsonMissing(['address' => 'Private address']);
    }

    public function test_invalid_inputs_return_validation_errors(): void
    {
        $this->getJson('/api/articles?keyword[]=bad')->assertUnprocessable()->assertJsonValidationErrors('keyword');
        $this->getJson('/api/podcasts?show_id[]=1')->assertUnprocessable();
        $this->postJson('/api/charts/votes')->assertUnprocessable()->assertJsonValidationErrors('chart_id');
        $this->getJson('/api/youtube/51')->assertUnprocessable();
    }

    public function test_missing_articles_return_json_without_an_accept_header(): void
    {
        $this->get('/api/articles/999')->assertNotFound()->assertHeader('content-type', 'application/json');
    }

    public function test_both_vote_routes_increment_the_imported_counter(): void
    {
        DB::table('charts')->insert(['id' => 1, 'location' => 'mnl', 'is_posted' => 1, 'phone_votes' => 7]);
        $this->postJson('/api/charts/votes', ['chart_id' => 1])->assertOk()->assertJsonPath('status', 'success');
        $this->getJson('/api/charts/vote?chart_id=1')->assertOk();
        $this->assertDatabaseHas('charts', ['id' => 1, 'phone_votes' => 9, 'voted_at' => now()->toDateString()]);
    }

    public function test_votes_cannot_modify_another_station(): void
    {
        DB::table('charts')->insert(['id' => 1, 'location' => 'cbu', 'is_posted' => 1, 'phone_votes' => 7]);
        $this->postJson('/api/charts/votes', ['chart_id' => 1])->assertNotFound();
        $this->assertDatabaseHas('charts', ['id' => 1, 'phone_votes' => 7]);
    }

    public function test_legacy_html_pages_and_youtube_configuration(): void
    {
        foreach (['search', 'help', 'about'] as $page) {
            $this->get('/api/'.$page)->assertOk()->assertHeader('content-type', 'text/html; charset=utf-8');
        }
        $this->getJson('/api/youtube/5')->assertStatus(503)->assertJsonPath('max', 5)->assertJsonMissingPath('key');
    }

    public function test_youtube_proxies_video_data_without_exposing_the_credential(): void
    {
        config(['station.youtube_api_key' => 'test-only-placeholder']);
        Http::fake(['www.googleapis.com/*' => Http::response(['items' => [['id' => ['videoId' => 'example']]]])]);
        $this->getJson('/api/youtube/5')->assertOk()->assertJsonPath('items.0.id.videoId', 'example')->assertJsonMissingPath('key');
    }

    public function test_all_api_routes_resolve_to_existing_controller_actions(): void
    {
        foreach (Route::getRoutes() as $route) {
            if (str_starts_with($route->uri(), 'api')) {
                $this->assertTrue(str_starts_with($route->getName(), 'api.'));
                $this->assertTrue(is_callable([$route->getController(), $route->getActionMethod()]));
            }
        }
    }
}

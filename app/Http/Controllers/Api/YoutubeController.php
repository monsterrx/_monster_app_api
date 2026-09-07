<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class YoutubeController extends Controller
{
    public function index(int $max): JsonResponse
    {
        validator(['max' => $max], ['max' => ['required', 'integer', 'between:1,50']])->validate();
        $channel = config('station.youtube_channel_id');
        $key = config('station.youtube_api_key');
        if (! $key) {
            return response()->json(['message' => 'YouTube is not configured.', 'id' => $channel, 'max' => $max], 503);
        }
        try {
            $data = Cache::remember('youtube:'.hash('sha256', $channel.':'.$max), 300, function () use ($channel, $key, $max): array {
                $response = Http::connectTimeout(3)->timeout(10)->get('https://www.googleapis.com/youtube/v3/search', [
                    'key' => $key, 'channelId' => $channel, 'maxResults' => $max, 'part' => 'snippet', 'type' => 'video', 'order' => 'date',
                ]);
                if (! $response->successful()) {
                    abort(502, 'YouTube is temporarily unavailable.');
                }

                return ['items' => $response->json('items', []), 'nextPageToken' => $response->json('nextPageToken')];
            });
        } catch (ConnectionException) {
            return response()->json(['message' => 'YouTube is temporarily unavailable.'], 502);
        }

        return response()->json(['id' => $channel, 'max' => $max, ...$data]);
    }
}

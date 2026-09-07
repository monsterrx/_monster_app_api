<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chart;
use App\Traits\ChartFunctions;
use App\Traits\MediaProcessors;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChartController extends Controller
{
    use ChartFunctions, MediaProcessors;

    public function index(): JsonResponse
    {
        $charts = Chart::with('Song.Album.Artist')
            ->where('dated', $this->getLatestChartDate())
            ->where('daily', 0)
            ->where('is_posted', 1)
            ->where('location', $this->getStationCode())
            ->orderBy('position')
            ->get();

        foreach ($charts as $chart) {
            $chart->album_image = $this->verifyPhoto($chart->Song->Album->image, 'albums');
        }

        return response()->json([
            'charts' => $charts,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['chart_id' => ['required', 'integer', 'min:1']]);
        $chart_id = $data['chart_id'];

        $chart = Chart::where('location', $this->getStationCode())->where('is_posted', 1)->whereNull('deleted_at')->findOrFail($chart_id);

        $chart->increment('phone_votes', 1, ['voted_at' => now()->toDateString()]);

        return response()->json([
            'status' => 'success',
            'message' => 'Vote Sent!',
        ]);
    }
}

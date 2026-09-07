<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Chart;

class ChartController extends Controller {
    public function index(Request $request) {
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
            'charts' => $charts
        ]);
    }

    public function vote(Request $request) {
        $chart_id = $request['chart_id'];

        $chart = Chart::with('Song.Album.Artist')->findOrFail($chart_id);

        $chart->voted_at = date('Y-m-d');
        ++$chart->phone_votes;
        $chart->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Vote Sent!'
        ]);
    }
}
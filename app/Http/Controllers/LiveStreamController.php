<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\SystemFunctions;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Carbon;
use App\Models\Show;

class LiveStreamController extends Controller {
    public function index() {
        $stream = $this->getStream();

        return response()->json([
            'live' => $stream
        ]);
    }

    public function getLiveShow() {
        $day = Carbon::now()->format('l');
        $time = date('H:i');

        $currentShow = Show::query()
            ->whereHas('Timeslot', function($query) use ($day, $time) {
                $query->whereNull('deleted_at')
                    ->where('end', '>', $time)
                    ->where('start', '<=', $time)
                    ->where('day', '=', $day)
                    ->where('location', $this->getStationCode())
                    ->orderBy('start');
            })->whereNull('deleted_at')
            ->first();

        if ($currentShow == null) {
            return response()->json([
                'show' => $currentShow,
            ]);
        }

        $currentShow['background_image'] = $this->verifyPhoto($currentShow['background_image'], 'shows');

        $currentJocks = $this->jocksQuery($time, $day);

        return response()->json([
            'show' => $currentShow,
            'jocks' => $currentJocks
        ]);
    }
}
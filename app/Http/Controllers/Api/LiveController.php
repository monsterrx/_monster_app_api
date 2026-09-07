<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Show;
use App\Models\Timeslot;
use App\Traits\JockFunctions;
use App\Traits\MediaProcessors;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class LiveController extends Controller
{
    use JockFunctions, MediaProcessors;

    public function index(): JsonResponse
    {
        // Variables on getting the day and time
        $day = Carbon::now()->format('l');
        $time = date('H:i');

        // 20240924 Update
        $stream = $this->getStream();

        $currentShow = Show::query()
            ->whereHas('Timeslot', function ($query) use ($day, $time) {
                $query->whereNull('deleted_at')
                    ->where('end', '>', $time)
                    ->where('start', '<=', $time)
                    ->where('day', '=', $day)
                    ->where('location', $this->getStationCode())
                    ->orderBy('start');
            })->whereNull('deleted_at')
            ->first();

        if ($currentShow == null) {
            return response()->json(['show' => $currentShow, 'live' => $stream]);
        }

        $currentShow['background_image'] = $this->verifyPhoto($currentShow['background_image'], 'shows');
        $currentShow['icon'] = $this->verifyPhoto($currentShow['icon'], 'shows');
        $currentShow['header_image'] = $this->verifyPhoto($currentShow['header_image'], 'shows');

        $showList = Timeslot::with('Show')
            ->whereNull('deleted_at')
            ->where('day', $day)
            ->where('location', $this->getStationCode())
            ->orderBy('start')
            ->get();

        foreach ($showList as $timeslot) {
            $timeslot['start'] = date('h:i A', strtotime($timeslot['start']));
            $timeslot['end'] = date('h:i A', strtotime($timeslot['end']));
        }

        $currentJocks = $this->jocksQuery($time, $day);

        foreach ($currentJocks as $jock) {
            $jock->profile_image = $this->verifyPhoto($jock->profile_image, 'jocks');
            $jock->background_image = $this->verifyPhoto($jock->background_image, 'jocks');
            $jock->main_image = $this->verifyPhoto($jock->main_image, 'jocks');
        }

        return response()->json([
            'show' => $currentShow,
            'jocks' => $currentJocks,
            'live' => $stream,
        ]);
    }
}

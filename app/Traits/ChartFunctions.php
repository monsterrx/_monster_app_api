<?php

namespace App\Traits;

use App\Models\Chart;
use App\Models\Outbreak;

trait ChartFunctions
{
    use SystemFunctions;

    public function getLatestChartDate(): ?string
    {
        return Chart::with('Song.Album.Artist')
            ->whereNull('deleted_at')
            ->where('daily', '=', 0)
            ->where('is_posted', '=', 1)
            ->where('location', $this->getStationCode())
            ->select('dated')
            ->max('dated');
    }

    public function getLatestDailyChartDate(): ?string
    {
        return Chart::with('Song.Album.Artist')
            ->whereNull('deleted_at')
            ->where('daily', '=', 1)
            ->where('is_posted', '=', 1)
            ->where('location', $this->getStationCode())
            ->select('dated')
            ->max('dated');
    }

    public function getLatestSouthsidesDate(): ?string
    {
        return Chart::whereNull('deleted_at')
            ->where('daily', '=', 0)
            ->where('local', '=', 1)
            ->where('location', '=', $this->getStationCode())
            ->select('dated')
            ->max('dated');
    }

    public function getLatestOutbreakDate(): ?string
    {
        return Outbreak::whereNull('deleted_at')
            ->where('location', '=', $this->getStationCode())
            ->select('dated')
            ->max('dated');
    }
}

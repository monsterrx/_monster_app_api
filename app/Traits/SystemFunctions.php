<?php

namespace App\Traits;

use App\Models\StreamLink;
use Illuminate\Support\Carbon;

trait SystemFunctions
{
    public function timePassedSincePublished(?string $publishedDate): string
    {
        return Carbon::parse($publishedDate)->diffForHumans();
    }

    public function getStream(): ?string
    {
        return StreamLink::where('location', $this->getStationCode())->value('url');
    }

    public function getAppUrl(): string
    {
        return rtrim(config('station.media_url'), '/');
    }

    public function getStationCode(): string
    {
        return config('station.code', 'mnl');
    }

    public function IdGenerator(int $length): string
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $value = 'RX';
        for ($i = 0; $i < $length; $i++) {
            $value .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $value.'931';
    }
}

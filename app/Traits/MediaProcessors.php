<?php

namespace App\Traits;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

trait MediaProcessors
{
    use SystemFunctions;

    public function verifyAudio(?string $fileName): string
    {
        return $this->getAppUrl().'/audios/'.$fileName;
    }

    public function verifyMobileAsset(?string $fileName, bool $longPhoto = false, bool $banner = false, bool $banner500 = false, bool $mobileWallpaper = false, bool $desktopWallpaper = false): string
    {
        return $this->verifyPhoto($fileName, '_assets/mobile', $longPhoto, $banner, $banner500, $mobileWallpaper, $desktopWallpaper);
    }

    public function verifyPhoto(?string $fileName, string $directory, bool $longPhoto = false, bool $banner = false, bool $banner500 = false, bool $mobileWallpaper = false, bool $desktopWallpaper = false): string
    {
        $fallback = $this->getFileName($longPhoto, $banner, $banner500, $mobileWallpaper, $desktopWallpaper);
        if (! $fileName || ! preg_match('/^[\w&.\-]+\.(jpe?g|png|webp|jfif|gif|svg)$/i', $fileName)) {
            return $fallback;
        }
        $url = $this->getAppUrl().'/images/'.$directory.'/'.$fileName;

        return ! config('station.verify_media') || $this->doesFileExistsInServer($url) ? $url : $fallback;
    }

    public function getFileName(bool $longPhoto = false, bool $banner = false, bool $banner500 = false, bool $mobileWallpaper = false, bool $desktopWallpaper = false): string
    {
        $fileName = match (true) {
            $longPhoto => 'default-long.png',
            $banner => 'default-banner.png',
            $banner500 => 'default-banner-sm.png',
            $mobileWallpaper => 'mobile-wallpaper-missing.png',
            $desktopWallpaper => 'desktop-wallpaper-missing.png',
            default => 'default.png',
        };

        return $this->getAppUrl().'/images/_assets/'.$fileName;
    }

    public function doesFileExistsInServer(string $fileUrl): bool
    {
        return Cache::remember('media:'.hash('sha256', $fileUrl), 3600, function () use ($fileUrl): bool {
            try {
                return Http::connectTimeout(1)->timeout(2)->head($fileUrl)->successful();
            } catch (ConnectionException) {
                return false;
            }
        });
    }

    public function replaceSourceText(?string $source): string
    {
        return str_replace('../../../../source/', $this->getAppUrl().'/source/', $source ?? '');
    }

    public function replaceArticleSourceText(?string $source): string
    {
        return str_replace('../../../../Article/', $this->getAppUrl().'/Article/', $source ?? '');
    }
}

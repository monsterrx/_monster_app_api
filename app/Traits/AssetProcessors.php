<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait AssetProcessors {
    use SystemFunctions;

    public function storeFile(Request $request, string $path, string $directory, $universal = false): string {
        $song = $request->file('track_link');

        $audio_name = Str::slug($request['name'], '') . '-sample-' . date('Ymd') . '.' . $song->getClientOriginalExtension();

        $song->move($path, $audio_name);

        $file = 'songs/' . $audio_name;

        $this->storeAsset($directory, $audio_name, $file);

        return $audio_name;
    }

    public function storePhoto(Request $request, string $path, string $directory, $universal = false, $profile_pic = false, $header_pic = false, $main_pic = false): string
    {
        $image = $request->file('image');

        $image_name = date('Ymd') . '-' . mt_rand() . '.' . $image->getClientOriginalExtension();

        $image->move($path, $image_name);

        $file = 'images/'.$directory.'/' . $image_name;

        if($universal) {
            $this->storeAsset($directory, $image_name, $file);
        } else {
            Storage::disk($directory)->put($image_name, file_get_contents($file));
        }

        return $image_name;
    }

    public function storeAsset($directory, $asset_name, $source) {
        Storage::disk($directory)->put($asset_name, file_get_contents($source));

        if($this->getStationCode() === 'mnl') {
            Storage::disk('cbu_'.$directory.'')->put($asset_name, file_get_contents($source));
            Storage::disk('cbu_'.$directory.'_cms')->put($asset_name, file_get_contents($source));
            Storage::disk('dav_'.$directory.'')->put($asset_name, file_get_contents($source));
            Storage::disk('dav_'.$directory.'_cms')->put($asset_name, file_get_contents($source));
        }

        if($this->getStationCode() === 'cbu') {
            Storage::disk('mnl_'.$directory.'')->put($asset_name, file_get_contents($source));
            Storage::disk('mnl_'.$directory.'_cms')->put($asset_name, file_get_contents($source));
            Storage::disk('dav_'.$directory.'')->put($asset_name, file_get_contents($source));
            Storage::disk('dav_'.$directory.'_cms')->put($asset_name, file_get_contents($source));
        }

        if($this->getStationCode() === 'dav') {
            Storage::disk('mnl_'.$directory.'')->put($asset_name, file_get_contents($source));
            Storage::disk('mnl_'.$directory.'_cms')->put($asset_name, file_get_contents($source));
            Storage::disk('cbu_'.$directory.'')->put($asset_name, file_get_contents($source));
            Storage::disk('cbu_'.$directory.'_cms')->put($asset_name, file_get_contents($source));
        }
    }
}

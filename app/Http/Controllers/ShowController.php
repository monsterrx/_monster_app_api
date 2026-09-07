<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Show;

class ShowController extends Controller {
    public function index() {
        $shows = Show::query()
            ->has('Jock')
            ->with('Jock')
            ->where('location', $this->getStationCode())
            ->where('is_active', '=', 1)
            ->get();

        foreach ($shows as $show) {
            $show->background_image = $this->verifyPhoto($show->background_image, 'shows');
        }

        return response()->json([
            'shows' => $shows
        ]);
    }

    public function show($id) {
        $show = Show::query()
            ->with(['Podcast', 'Jock'])
            ->findOrFail($id);

        $show->background_image = $this->verifyPhoto($show->background_image, 'shows');

        foreach ($show->Podcast as $podcast) {
            $podcast->image = $this->verifyPhoto($podcast->image, 'podcasts');
        }

        return response()->json([
            'show' => $show
        ]);
    }
}
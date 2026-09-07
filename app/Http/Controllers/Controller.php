<?php

namespace App\Http\Controllers;
use App\Traits\AssetProcessors;
use App\Traits\ChartFunctions;
use App\Traits\JockFunctions;
use App\Traits\MediaProcessors;
use App\Traits\SystemFunctions;

abstract class Controller
{
    use SystemFunctions,
        AssetProcessors,
        MediaProcessors,
        ChartFunctions,
        JockFunctions;
}

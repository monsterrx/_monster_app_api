<?php

namespace App\Traits;

use App\Models\Jock;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

trait JockFunctions
{
    use SystemFunctions;

    public function jocksQuery(string $time, string $day): Collection
    {
        return Jock::query()
            ->whereHas('Timeslot', function ($builder) use ($time, $day) {
                $builder->where('timeslots.deleted_at', '=', null)
                    ->where('end', '>', $time)
                    ->where('start', '<=', $time)
                    ->where('day', '=', $day)
                    ->where('location', $this->getStationCode());
            })->whereHas('Employee', function ($builder) {
                $builder->where('employees.deleted_at', '=', null);
            })->whereHas('Show', function ($builder) {
                $builder->where('shows.deleted_at', '=', null);
            })->whereNull('deleted_at')
            ->get();
    }

    public function removeTMRJock(int $id, string $time, string $day): Collection
    {
        return DB::table('jock_show')
            ->join('jocks', 'jock_show.jock_id', '=', 'jocks.id')
            ->join('timeslots', 'jock_show.show_id', '=', 'timeslots.show_id')
            ->join('employees', 'jocks.employee_id', '=', 'employees.id')
            ->join('shows', 'jock_show.show_id', '=', 'shows.id')
            ->whereNull('timeslots.deleted_at')
            ->where('jock_show.jock_id', '!=', $id)
            ->where('timeslots.end', '>', $time)
            ->where('timeslots.start', '<=', $time)
            ->where('timeslots.day', $day)
            ->where('timeslots.location', $this->getStationCode())
            ->orderBy('timeslots.start')
            ->select('jock_show.jock_id', 'jocks.name', 'employees.first_name', 'employees.last_name', 'shows.title', 'jocks.profile_image')
            ->get();
    }
}

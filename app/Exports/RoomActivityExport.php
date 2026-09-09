<?php

namespace App\Exports;

use App\Models\Booking;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Carbon\Carbon;

class RoomActivityExport implements FromView, ShouldAutoSize
{
    protected $startDate;
    protected $endDate;
    protected $startTime;
    protected $endTime;
    protected $activityType;
    protected $search;

    public function __construct($startDate, $endDate, $startTime, $endTime, $activityType, $search = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->activityType = $activityType;
        $this->search = $search;
    }

    public function view(): View
    {
        $startDateTime = Carbon::parse("{$this->startDate} {$this->startTime}");
        $endDateTime = Carbon::parse("{$this->endDate} {$this->endTime}")->endOfMinute();

        $query = Booking::with(['guest', 'room', 'user'])
            ->where('hotel_id', active_hotel_id())
            ->where(function($q) use ($startDateTime, $endDateTime) {
                if ($this->activityType === 'checkin' || $this->activityType === 'all') {
                    $q->orWhereBetween('actual_check_in', [$startDateTime, $endDateTime]);
                }
                if ($this->activityType === 'checkout' || $this->activityType === 'all') {
                    $q->orWhereBetween('actual_check_out', [$startDateTime, $endDateTime]);
                }
            })
            ->when($this->search, function($q) {
                $q->where(function($subQ) {
                    $subQ->whereHas('guest', function($guestQ) {
                        $guestQ->where('name', 'like', "%{$this->search}%");
                    })
                    ->orWhereHas('room', function($roomQ) {
                        $roomQ->where('name', 'like', "%{$this->search}%")
                              ->orWhere('room_number', 'like', "%{$this->search}%");
                    })
                    ->orWhere('custom_room_name', 'like', "%{$this->search}%");
                });
            });

        $activities = $query->orderBy('updated_at', 'desc')->get();

        return view('reports.partials.room_activity_table', [
            'activities' => $activities,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'startTime' => $this->startTime,
            'endTime' => $this->endTime,
            'activityType' => $this->activityType,
            'search' => $this->search,
        ]);
    }
}

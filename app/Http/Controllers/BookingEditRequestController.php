<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingEditRequest;
use App\Traits\AjaxResponse;
use Illuminate\Http\Request;

class BookingEditRequestController extends Controller
{
    use \App\Traits\AjaxResponse;

    /**
     * Store a new edit request.
     */
    public function store(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'change_type' => 'required|array',
            'change_type.*' => 'string|in:date,guest,room,price,other',
            'reason' => 'required|string|max:1000',
            'proposed_changes' => 'nullable|array',
        ]);

        try {
            $editRequest = BookingEditRequest::create([
                'hotel_id' => active_hotel_id(),
                'booking_id' => $booking->id,
                'requested_by' => auth()->id(),
                'change_type' => implode(',', $validated['change_type']),
                'reason' => $validated['reason'],
                'proposed_changes' => $validated['proposed_changes'] ?? null,
                'status' => 'pending',
            ]);

            return $this->ajaxOrRedirect('Edit request submitted. Waiting for approval.', url()->previous(), $editRequest, 201);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Approve an edit request.
     */
    public function approve(Request $request, BookingEditRequest $editRequest)
    {
        try {
            $editRequest->update([
                'status' => 'approved',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'reviewer_notes' => $request->input('notes'),
                'expires_at' => now()->addHour(),
            ]);

            return $this->ajaxOrRedirect('Edit request approved. User has 1 hour to make changes.', back()->getTargetUrl());
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reject an edit request.
     */
    public function reject(Request $request, BookingEditRequest $editRequest)
    {
        try {
            $editRequest->update([
                'status' => 'rejected',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'reviewer_notes' => $request->input('notes'),
            ]);

            return $this->ajaxOrRedirect('Edit request rejected.', back()->getTargetUrl());
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\EmployeeSchedule;
use App\Models\ShiftHandover;
use App\Services\HandoverService;
use App\Traits\AjaxResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShiftHandoverController extends Controller
{
    use \App\Traits\AjaxResponse;

    protected HandoverService $handoverService;

    public function __construct(HandoverService $handoverService)
    {
        $this->handoverService = $handoverService;
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $hotelId = active_hotel_id();
        $today = get_hotel_date();

        $mySchedule = EmployeeSchedule::with('shift')
            ->where('hotel_id', $hotelId)
            ->where('employee_id', $user->id)
            ->where('schedule_date', $today)
            ->first();

        $existingDraft = null;
        $canCreate = false;
        if ($mySchedule && $mySchedule->shift) {
            $existingDraft = $this->handoverService->getExistingDraft($hotelId, $user->id, $today);
            if (!$existingDraft) {
                $hasHandover = ShiftHandover::where('hotel_id', $hotelId)
                    ->where('outgoing_employee_id', $user->id)
                    ->where('handover_date', $today)
                    ->exists();
                $canCreate = !$hasHandover;
            }
        }

        $pendingHandovers = collect();
        if ($mySchedule && $mySchedule->shift) {
            $pendingHandovers = $this->handoverService->getPendingForShift(
                $hotelId, $mySchedule->shift_id, $today
            );
        }

        $lastHandover = null;
        if ($mySchedule && $mySchedule->shift) {
            $lastHandover = ShiftHandover::with(['outgoingEmployee', 'incomingEmployee', 'items'])
                ->where('hotel_id', $hotelId)
                ->where('outgoing_shift_id', $mySchedule->shift_id)
                ->where('status', 'confirmed')
                ->orderBy('handover_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->first();
        }

        return view('shift-handover.index', compact(
            'mySchedule', 'existingDraft', 'canCreate', 'pendingHandovers', 'lastHandover'
        ));
    }

    public function create(Request $request)
    {
        $user = auth()->user();
        $hotelId = active_hotel_id();
        $today = get_hotel_date();

        try {
            $handover = $this->handoverService->createDraft($hotelId, $user->id, $today);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return redirect()->route('shift-handovers.index')
                ->with('error', $e->getMessage());
        }

        return $this->ajaxOrRedirect('Draft created successfully.', route('shift-handovers.show', $handover), $handover);
    }

    public function show(ShiftHandover $shiftHandover)
    {
        $shiftHandover->load(['outgoingShift', 'incomingShift', 'outgoingEmployee', 'incomingEmployee', 'items.template']);

        $user = auth()->user();
        $hotelId = active_hotel_id();

        if ($shiftHandover->hotel_id !== $hotelId) {
            abort(404);
        }

        $canEdit = $shiftHandover->status === 'draft'
            && $shiftHandover->outgoing_employee_id === $user->id;

        $canConfirm = $shiftHandover->status === 'submitted'
            && $user->can('handover.confirm');

        $isIncoming = false;
        if ($canConfirm) {
            $today = get_hotel_date();
            $mySchedule = EmployeeSchedule::where('hotel_id', $hotelId)
                ->where('employee_id', $user->id)
                ->where('schedule_date', $today)
                ->first();
            $isIncoming = $mySchedule && $mySchedule->shift_id === $shiftHandover->incoming_shift_id;
        }

        if ($shiftHandover->status === 'draft' && $canEdit) {
            return view('shift-handover.form', [
                'handover' => $shiftHandover->load(['outgoingShift', 'incomingShift', 'outgoingEmployee', 'incomingEmployee', 'items']),
            ]);
        }

        return view('shift-handover.show', compact(
            'shiftHandover', 'canEdit', 'canConfirm', 'isIncoming'
        ));
    }

    public function update(Request $request, ShiftHandover $shiftHandover)
    {
        $user = auth()->user();
        $hotelId = active_hotel_id();

        if ($shiftHandover->hotel_id !== $hotelId) {
            abort(404);
        }

        if ($shiftHandover->status !== 'draft' || $shiftHandover->outgoing_employee_id !== $user->id) {
            abort(403);
        }

        $data = $request->validate([
            'cash_amount' => 'nullable|numeric|min:0|max:9999999999999.99',
            'notes' => 'nullable|string|max:2000',
            'items' => 'nullable|array',
            'items.*.is_checked' => 'nullable|boolean',
            'items.*.value' => 'nullable|string|max:100',
            'items.*.notes' => 'nullable|string|max:500',
        ]);

        $updateData = [];
        if (array_key_exists('cash_amount', $data)) {
            $updateData['cash_amount'] = $data['cash_amount'];
        }
        if (array_key_exists('notes', $data)) {
            $updateData['notes'] = $data['notes'];
        }

        if (!empty($updateData)) {
            $shiftHandover->update($updateData);
        }

        if ($request->has('items')) {
            foreach ($data['items'] as $itemId => $itemData) {
                $item = $shiftHandover->items()->find($itemId);
                if ($item) {
                    $item->update([
                        'is_checked' => isset($itemData['is_checked']) && $itemData['is_checked'],
                        'value' => $itemData['value'] ?? null,
                        'notes' => $itemData['notes'] ?? null,
                    ]);
                }
            }
        }

        return $this->ajaxOrRedirect('Draft serah terima berhasil disimpan', route('shift-handovers.show', $shiftHandover));
    }

    public function submit(Request $request, ShiftHandover $shiftHandover)
    {
        $user = auth()->user();
        $hotelId = active_hotel_id();

        if ($shiftHandover->hotel_id !== $hotelId) {
            abort(404);
        }

        if ($shiftHandover->status !== 'draft' || $shiftHandover->outgoing_employee_id !== $user->id) {
            abort(403);
        }

        try {
            $this->handoverService->submit($shiftHandover, $request->all());
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError('Validation failed', $e->errors());
            }
            return redirect()->route('shift-handovers.show', $shiftHandover)
                ->withErrors($e->errors())
                ->withInput();
        }

        return $this->ajaxOrRedirect('Serah terima berhasil disubmit', route('shift-handovers.index'));
    }

    public function confirm(Request $request, ShiftHandover $shiftHandover)
    {
        $user = auth()->user();
        $hotelId = active_hotel_id();

        if ($shiftHandover->hotel_id !== $hotelId) {
            abort(404);
        }

        if (!$user->can('handover.confirm')) {
            abort(403);
        }

        try {
            $this->handoverService->confirm($shiftHandover, $user->id);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return redirect()->route('shift-handovers.show', $shiftHandover)
                ->with('error', $e->getMessage());
        }

        return $this->ajaxOrRedirect('Serah terima berhasil dikonfirmasi', route('shift-handovers.index'));
    }

    public function dispute(Request $request, ShiftHandover $shiftHandover)
    {
        $user = auth()->user();
        $hotelId = active_hotel_id();

        if ($shiftHandover->hotel_id !== $hotelId) {
            abort(404);
        }

        if (!$user->can('handover.confirm')) {
            abort(403);
        }

        $request->validate([
            'incoming_notes' => 'required|string|min:10',
        ]);

        try {
            $this->handoverService->dispute($shiftHandover, $user->id, $request->incoming_notes);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError('Validation failed', $e->errors());
            }
            return redirect()->route('shift-handovers.show', $shiftHandover)
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return redirect()->route('shift-handovers.show', $shiftHandover)
                ->with('error', $e->getMessage());
        }

        return $this->ajaxOrRedirect('Serah terima telah di-dispute', route('shift-handovers.index'));
    }

    public function history(Request $request)
    {
        $user = auth()->user();
        $hotelId = active_hotel_id();

        $query = ShiftHandover::with([
            'outgoingShift:id,name', 'incomingShift:id,name', 'outgoingEmployee:id,name', 'incomingEmployee:id,name'
        ])->where('hotel_id', $hotelId);

        $dateFrom = $request->input('date_from', Carbon::today()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->input('date_to', Carbon::today()->format('Y-m-d'));

        $dateFromCarbon = Carbon::parse($dateFrom);
        $dateToCarbon = Carbon::parse($dateTo);

        if ($dateFromCarbon->diffInDays($dateToCarbon) > 90) {
            $dateToCarbon = $dateFromCarbon->copy()->addDays(90);
            $dateTo = $dateToCarbon->format('Y-m-d');
        }

        $query->whereBetween('handover_date', [$dateFrom, $dateTo]);

        if ($request->filled('shift_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('outgoing_shift_id', $request->shift_id)
                  ->orWhere('incoming_shift_id', $request->shift_id);
            });
        }

        if ($request->filled('employee_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('outgoing_employee_id', $request->employee_id)
                  ->orWhere('incoming_employee_id', $request->employee_id);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if (!$user->can('handover.view-all')) {
            $query->where(function ($q) use ($user) {
                $q->where('outgoing_employee_id', $user->id)
                  ->orWhere('incoming_employee_id', $user->id);
            });
        }

        $handovers = $query->orderBy('handover_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $shifts = \App\Models\Shift::where('hotel_id', $hotelId)->where('is_active', true)->orderBy('sort_order')->get();
        $employees = \App\Models\User::whereHas('schedules')->get();

        return view('shift-handover.history', compact(
            'handovers', 'shifts', 'employees', 'dateFrom', 'dateTo'
        ));
    }

    public function exportPdf(ShiftHandover $shiftHandover)
    {
        $hotelId = active_hotel_id();

        if ($shiftHandover->hotel_id !== $hotelId) {
            abort(404);
        }

        $shiftHandover->load([
            'outgoingShift', 'incomingShift', 'outgoingEmployee', 'incomingEmployee', 'items'
        ]);

        $hotel = current_hotel();

        $pdf = Pdf::loadView('shift-handover.pdf', [
            'handover' => $shiftHandover,
            'hotel' => $hotel,
        ]);

        $pdf->setPaper('A4', 'portrait');

        $filename = 'serah_terima_shift_'
            . ($shiftHandover->outgoingShift->code ?? $shiftHandover->outgoingShift->name ?? 'shift')
            . '_' . $shiftHandover->handover_date->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }
}

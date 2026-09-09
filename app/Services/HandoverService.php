<?php

namespace App\Services;

use App\Http\Controllers\ShiftReportController;
use App\Models\EmployeeSchedule;
use App\Models\HandoverChecklistTemplate;
use App\Models\Shift;
use App\Models\ShiftHandover;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HandoverService
{
    public function createDraft(int $hotelId, int $employeeId, string $date): ShiftHandover
    {
        $schedule = EmployeeSchedule::with('shift')
            ->where('hotel_id', $hotelId)
            ->where('employee_id', $employeeId)
            ->where('schedule_date', $date)
            ->first();

        if (!$schedule || !$schedule->shift) {
            throw new \Exception('Anda tidak memiliki jadwal shift hari ini');
        }

        $existing = $this->getExistingDraft($hotelId, $employeeId, $date);
        if ($existing) {
            return $existing;
        }

        $outgoingShift = $schedule->shift;
        $incomingShift = $this->getNextShift($hotelId, $outgoingShift->id);

        $financialSummary = $this->getFinancialSummary($hotelId, $date, $outgoingShift);

        try {
            $handover = ShiftHandover::create([
                'hotel_id' => $hotelId,
                'handover_date' => $date,
                'outgoing_shift_id' => $outgoingShift->id,
                'incoming_shift_id' => $incomingShift ? $incomingShift->id : $outgoingShift->id,
                'outgoing_employee_id' => $employeeId,
                'financial_summary' => $financialSummary,
                'transaction_details' => $financialSummary['transactions'] ?? [],
                'status' => 'draft',
            ]);

            $templates = HandoverChecklistTemplate::where('hotel_id', $hotelId)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();

            foreach ($templates as $template) {
                $handover->items()->create([
                    'checklist_template_id' => $template->id,
                    'item_name' => $template->name,
                    'is_required' => $template->is_required,
                    'is_checked' => false,
                ]);
            }

            return $handover;
        } catch (QueryException $e) {
            if ($e->getCode() === '23000' && str_contains($e->getMessage(), 'shift_handover_unique')) {
                $existing = $this->getExistingDraft($hotelId, $employeeId, $date);
                if ($existing) {
                    return $existing;
                }
            }
            throw $e;
        }
    }

    public function getExistingDraft(int $hotelId, int $employeeId, string $date): ?ShiftHandover
    {
        return ShiftHandover::where('hotel_id', $hotelId)
            ->where('outgoing_employee_id', $employeeId)
            ->where('handover_date', $date)
            ->where('status', 'draft')
            ->first();
    }

    public function getNextShift(int $hotelId, int $currentShiftId): ?Shift
    {
        $currentShift = Shift::where('hotel_id', $hotelId)->find($currentShiftId);
        if (!$currentShift) {
            return null;
        }

        $nextShift = Shift::where('hotel_id', $hotelId)
            ->where('is_active', true)
            ->where('is_off', false)
            ->where('sort_order', '>', $currentShift->sort_order)
            ->orderBy('sort_order')
            ->first();

        if (!$nextShift) {
            $nextShift = Shift::where('hotel_id', $hotelId)
                ->where('is_active', true)
                ->where('is_off', false)
                ->orderBy('sort_order')
                ->first();
        }

        return $nextShift;
    }

    public function getFinancialSummary(int $hotelId, string $date, Shift $shift): array
    {
        $controller = app(ShiftReportController::class);
        $carbonDate = Carbon::parse($date);
        $rows = $controller->buildShiftReport($hotelId, $carbonDate, $shift);

        $pemasukanRoom = collect($rows)->sum('pemasukan_room');
        $pemasukanLain = collect($rows)->sum('pemasukan_lain');
        $pengeluaran = collect($rows)->sum('pengeluaran');
        $totalIncome = $pemasukanRoom + $pemasukanLain;
        $netBalance = $totalIncome - $pengeluaran;

        return [
            'pemasukan_room' => $pemasukanRoom,
            'pemasukan_lain' => $pemasukanLain,
            'pengeluaran' => $pengeluaran,
            'total_income' => $totalIncome,
            'total_expense' => $pengeluaran,
            'net_balance' => $netBalance,
            'transactions' => $rows,
        ];
    }

    public function submit(ShiftHandover $handover, array $data): ShiftHandover
    {
        $cashAmount = $data['cash_amount'] ?? null;
        if (is_null($cashAmount) || $cashAmount < 0) {
            throw ValidationException::withMessages([
                'cash_amount' => 'Jumlah kas fisik wajib diisi dan harus bernilai minimal 0',
            ]);
        }

        $items = $data['items'] ?? [];
        foreach ($items as $itemId => $itemData) {
            $item = $handover->items()->find($itemId);
            if ($item && $item->is_required && (!isset($itemData['is_checked']) || !$itemData['is_checked'])) {
                throw ValidationException::withMessages([
                    "items.{$itemId}.is_checked" => "Item \"{$item->item_name}\" wajib dicentang",
                ]);
            }
        }

        $financialSummary = $handover->financial_summary;
        $netBalance = $financialSummary['net_balance'] ?? 0;
        $discrepancy = abs((float) $cashAmount - (float) $netBalance) > 0.01;
        $discrepancyNotes = $data['discrepancy_notes'] ?? '';

        if ($discrepancy && strlen(trim($discrepancyNotes)) < 10) {
            throw ValidationException::withMessages([
                'discrepancy_notes' => 'Catatan penjelasan selisih wajib diisi (min 10 karakter)',
            ]);
        }

        $notes = $data['notes'] ?? '';
        if (strlen($notes) > 2000) {
            $notes = substr($notes, 0, 2000);
        }

        DB::transaction(function () use ($handover, $data, $cashAmount, $discrepancy, $discrepancyNotes, $notes) {
            $handover->update([
                'cash_amount' => $cashAmount,
                'cash_discrepancy' => $discrepancy,
                'discrepancy_notes' => $discrepancy ? $discrepancyNotes : null,
                'notes' => $notes,
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            $items = $data['items'] ?? [];
            foreach ($items as $itemId => $itemData) {
                $item = $handover->items()->find($itemId);
                if ($item) {
                    $item->update([
                        'is_checked' => isset($itemData['is_checked']) && $itemData['is_checked'],
                        'value' => $itemData['value'] ?? null,
                        'notes' => $itemData['notes'] ?? null,
                    ]);
                }
            }
        });

        return $handover->fresh()->load('items');
    }

    public function confirm(ShiftHandover $handover, int $incomingEmployeeId): ShiftHandover
    {
        if ($handover->status !== 'submitted') {
            throw new \Exception('Handover telah diproses oleh pihak lain');
        }

        $handover->update([
            'incoming_employee_id' => $incomingEmployeeId,
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        return $handover->fresh();
    }

    public function dispute(ShiftHandover $handover, int $incomingEmployeeId, string $notes): ShiftHandover
    {
        if ($handover->status !== 'submitted') {
            throw new \Exception('Handover telah diproses oleh pihak lain');
        }

        if (strlen(trim($notes)) < 10) {
            throw ValidationException::withMessages([
                'incoming_notes' => 'Alasan dispute wajib diisi (min 10 karakter)',
            ]);
        }

        $handover->update([
            'incoming_employee_id' => $incomingEmployeeId,
            'incoming_notes' => $notes,
            'status' => 'disputed',
        ]);

        return $handover->fresh();
    }

    public function seedDefaultTemplates(int $hotelId): void
    {
        $defaults = [
            ['name' => 'Kas Fisik', 'description' => 'Pengecekan jumlah kas fisik', 'is_required' => true, 'sort_order' => 1],
            ['name' => 'Kunci Kamar', 'description' => 'Pengecekan kelengkapan kunci kamar', 'is_required' => true, 'sort_order' => 2],
            ['name' => 'Buku Tamu', 'description' => 'Pengecekan buku tamu', 'is_required' => true, 'sort_order' => 3],
            ['name' => 'Catatan Penting', 'description' => 'Catatan penting operasional', 'is_required' => true, 'sort_order' => 4],
        ];

        foreach ($defaults as $tmpl) {
            HandoverChecklistTemplate::firstOrCreate(
                ['hotel_id' => $hotelId, 'name' => $tmpl['name']],
                $tmpl + ['hotel_id' => $hotelId]
            );
        }
    }

    public function getPendingForShift(int $hotelId, int $shiftId, string $date)
    {
        return ShiftHandover::with(['outgoingEmployee', 'items'])
            ->where('hotel_id', $hotelId)
            ->where('incoming_shift_id', $shiftId)
            ->where('handover_date', $date)
            ->where('status', 'submitted')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}

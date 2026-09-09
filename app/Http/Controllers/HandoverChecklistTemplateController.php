<?php

namespace App\Http\Controllers;

use App\Models\HandoverChecklistTemplate;
use App\Traits\AjaxResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class HandoverChecklistTemplateController extends Controller
{
    use \App\Traits\AjaxResponse;

    public function index()
    {
        $hotelId = active_hotel_id();
        $templates = HandoverChecklistTemplate::where('hotel_id', $hotelId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('handover-templates.index', compact('templates'));
    }

    public function store(Request $request)
    {
        $hotelId = active_hotel_id();

        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'is_required' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0|max:999',
        ]);

        try {
            $exists = HandoverChecklistTemplate::where('hotel_id', $hotelId)
                ->whereRaw('LOWER(name) = ?', [strtolower($request->name)])
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'name' => 'Nama item sudah digunakan',
                ]);
            }

            $template = HandoverChecklistTemplate::create([
                'hotel_id' => $hotelId,
                'name' => $request->name,
                'description' => $request->description,
                'is_required' => $request->boolean('is_required', true),
                'sort_order' => $request->integer('sort_order', 0),
            ]);

            return $this->ajaxOrRedirect('Template item berhasil ditambahkan', route('handover-templates.index'), $template);
        } catch (ValidationException $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage(), $e->errors());
            }
            throw $e;
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function update(Request $request, HandoverChecklistTemplate $template)
    {
        $hotelId = active_hotel_id();

        if ($template->hotel_id !== $hotelId) {
            abort(404);
        }

        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'is_required' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0|max:999',
        ]);

        try {
            $exists = HandoverChecklistTemplate::where('hotel_id', $hotelId)
                ->whereRaw('LOWER(name) = ?', [strtolower($request->name)])
                ->where('id', '!=', $template->id)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'name' => 'Nama item sudah digunakan',
                ]);
            }

            $template->update([
                'name' => $request->name,
                'description' => $request->description,
                'is_required' => $request->boolean('is_required', true),
                'sort_order' => $request->integer('sort_order', 0),
            ]);

            return $this->ajaxOrRedirect('Template item berhasil diperbarui', route('handover-templates.index'), $template);
        } catch (ValidationException $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage(), $e->errors());
            }
            throw $e;
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function destroy(HandoverChecklistTemplate $template)
    {
        $hotelId = active_hotel_id();

        if ($template->hotel_id !== $hotelId) {
            abort(404);
        }

        try {
            $activeRequired = HandoverChecklistTemplate::where('hotel_id', $hotelId)
                ->where('is_active', true)
                ->where('is_required', true)
                ->where('id', '!=', $template->id)
                ->count();

            if ($activeRequired < 1 && $template->is_active && $template->is_required) {
                throw ValidationException::withMessages([
                    'is_active' => 'Minimal satu item wajib harus tetap aktif',
                ]);
            }

            $template->update(['is_active' => false]);

            return $this->ajaxOrRedirect('Template item berhasil dinonaktifkan', route('handover-templates.index'));
        } catch (ValidationException $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage(), $e->errors());
            }
            throw $e;
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:handover_checklist_templates,id',
            'items.*.sort_order' => 'required|integer|min:0|max:999',
        ]);

        try {
            foreach ($request->items as $item) {
                HandoverChecklistTemplate::where('id', $item['id'])
                    ->update(['sort_order' => $item['sort_order']]);
            }

            return $this->ajaxSuccess('Reorder successful');
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }
}

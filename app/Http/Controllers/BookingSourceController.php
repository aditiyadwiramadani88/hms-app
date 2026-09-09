<?php

namespace App\Http\Controllers;

use App\Models\BookingSource;
use App\Traits\AjaxResponse;
use Illuminate\Http\Request;

class BookingSourceController extends Controller
{
    use \App\Traits\AjaxResponse;

    public function index()
    {
        $sources = BookingSource::where('hotel_id', active_hotel_id())->orderBy('name')->get();
        return view('booking-sources.index', compact('sources'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:100',
            'color'     => 'required|string|max:20',
            'is_active' => 'boolean',
        ]);

        try {
            $source = BookingSource::create([
                'hotel_id'  => active_hotel_id(),
                'name'      => $validated['name'],
                'color'     => $validated['color'],
                'is_active' => $request->boolean('is_active', true),
            ]);

            return $this->ajaxOrRedirect('Sumber booking berhasil ditambahkan.', route('booking-sources.index'), $source, 201);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function update(Request $request, BookingSource $bookingSource)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:100',
            'color'     => 'required|string|max:20',
            'is_active' => 'boolean',
        ]);

        try {
            $bookingSource->update([
                'name'      => $validated['name'],
                'color'     => $validated['color'],
                'is_active' => $request->boolean('is_active', true),
            ]);

            return $this->ajaxOrRedirect('Sumber booking berhasil diupdate.', route('booking-sources.index'), $bookingSource);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function destroy(BookingSource $bookingSource)
    {
        try {
            $bookingSource->delete();
            return $this->ajaxOrRedirect('Sumber booking berhasil dihapus.', route('booking-sources.index'));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function apiList()
    {
        $sources = BookingSource::where('hotel_id', active_hotel_id())
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'color']);

        return response()->json($sources);
    }
}

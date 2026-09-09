<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use App\Traits\AjaxResponse;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    use \App\Traits\AjaxResponse;

    public function index()
    {
        $paymentMethods = PaymentMethod::where('hotel_id', active_hotel_id())
            ->orderBy('sort_order')
            ->get();
        return view('admin.payment-methods.index', compact('paymentMethods'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:payment_methods,code',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        try {
            PaymentMethod::create([
                'hotel_id' => active_hotel_id(),
                'name' => $validated['name'],
                'code' => $validated['code'],
                'sort_order' => $validated['sort_order'] ?? 0,
                'is_active' => $request->boolean('is_active', true),
            ]);

            return $this->ajaxOrRedirect('Metode pembayaran berhasil ditambahkan.', route('admin.payment-methods.index'));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:payment_methods,code,' . $paymentMethod->id,
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        try {
            $paymentMethod->update([
                'name' => $validated['name'],
                'code' => $validated['code'],
                'sort_order' => $validated['sort_order'] ?? 0,
                'is_active' => $request->boolean('is_active', true),
            ]);

            return $this->ajaxOrRedirect('Metode pembayaran berhasil diupdate.', route('admin.payment-methods.index'));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function destroy(PaymentMethod $paymentMethod)
    {
        try {
            $paymentMethod->delete();
            return $this->ajaxOrRedirect('Metode pembayaran berhasil dihapus.', route('admin.payment-methods.index'));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}

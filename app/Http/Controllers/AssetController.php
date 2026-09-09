<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetHistory;
use App\Models\Room;
use App\Models\User;
use App\Traits\AjaxResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class AssetController extends Controller
{
    use \App\Traits\AjaxResponse;

    private $statuses = ['Baik', 'Rusak Ringan', 'Rusak Berat', 'Maintenance', 'Menunggu Diganti', 'Sudah Dibuang'];

    public function index(Request $request)
    {
        $query = Asset::with(['category', 'room', 'picUser']);

        if ($request->filled('category_id')) {
            $query->where('asset_category_id', $request->category_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('location_type')) {
            $query->where('location_type', $request->location_type);
        }
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('serial_number', 'like', "%{$request->search}%");
            });
        }

        $assets = $query->latest()->paginate($request->input('per_page', 15));
        
        $categories = AssetCategory::all();
        $statuses = $this->statuses;

        return view('assets.index', compact('assets', 'categories', 'statuses'));
    }

    public function create()
    {
        $categories = AssetCategory::all();
        $rooms = Room::all();
        $users = User::whereHas('hotels', function($q) {
            $q->where('hotel_id', active_hotel_id());
        })->get();
        $roles = Role::all();
        $statuses = $this->statuses;

        return view('assets.create', compact('categories', 'rooms', 'users', 'roles', 'statuses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_category_id' => 'required|exists:asset_categories,id',
            'name' => 'required|string|max:255',
            'serial_number' => 'nullable|string|max:100',
            'purchase_year' => 'required|integer|min:1900|max:' . date('Y'),
            'purchase_price' => 'nullable|numeric|min:0',
            'supplier' => 'nullable|string|max:255',
            'location_type' => 'required|in:room,area',
            'room_id' => 'required_if:location_type,room|nullable|exists:rooms,id',
            'area_name' => 'required_if:location_type,area|nullable|string|max:255',
            'pic_type' => 'required|in:user,role',
            'pic_user_id' => 'required_if:pic_type,user|nullable|exists:users,id',
            'pic_role' => 'required_if:pic_type,role|nullable|string|max:100',
            'status' => 'required|in:' . implode(',', $this->statuses),
            'condition_notes' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'quantity' => 'required|integer|min:1',
            'acquisition_type' => 'required|in:purchase,donation,transfer,existing',
            'acquisition_notes' => 'nullable|string',
        ]);

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('assets', 'public');
            $validated['photo'] = $path;
        }

        $validated['hotel_id'] = active_hotel_id();
        
        if ($validated['location_type'] === 'room') {
            $validated['area_name'] = null;
        } else {
            $validated['room_id'] = null;
        }

        if ($validated['pic_type'] === 'user') {
            $validated['pic_role'] = null;
        } else {
            $validated['pic_user_id'] = null;
        }

        DB::transaction(function () use ($validated) {
            $asset = Asset::create($validated);
            
            AssetHistory::create([
                'asset_id' => $asset->id,
                'from_status' => null,
                'to_status' => $asset->status,
                'changed_by' => auth()->id(),
                'notes' => 'Initial asset registration',
            ]);

            // Record initial stock mutation
            \App\Models\AssetStockMutation::create([
                'asset_id' => $asset->id,
                'hotel_id' => active_hotel_id(),
                'type' => 'in',
                'quantity' => $asset->quantity,
                'reason' => $validated['acquisition_type'] === 'transfer' ? 'transfer_in' : $validated['acquisition_type'],
                'notes' => $validated['acquisition_notes'] ?? 'Stok awal saat registrasi asset',
                'unit_cost' => $validated['purchase_price'] ?? null,
                'user_id' => auth()->id(),
            ]);
        });

        return redirect()->route('assets.index')->with('success', 'Asset created successfully.');
    }

    public function show(Asset $asset)
    {
        $asset->load(['category', 'room', 'picUser', 'histories.user', 'repairs.reporter', 'repairs.approver', 'stockMutations.user']);
        $statuses = $this->statuses;
        $repairTypes = \App\Models\AssetRepair::repairTypes();
        $mutationReasons = \App\Models\AssetStockMutation::reasons();
        return view('assets.show', compact('asset', 'statuses', 'repairTypes', 'mutationReasons'));
    }

    public function edit(Asset $asset)
    {
        $categories = AssetCategory::all();
        $rooms = Room::all();
        $users = User::whereHas('hotels', function($q) {
            $q->where('hotel_id', active_hotel_id());
        })->get();
        $roles = Role::all();
        $statuses = $this->statuses;

        return view('assets.edit', compact('asset', 'categories', 'rooms', 'users', 'roles', 'statuses'));
    }

    public function update(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'asset_category_id' => 'required|exists:asset_categories,id',
            'name' => 'required|string|max:255',
            'serial_number' => 'nullable|string|max:100',
            'purchase_year' => 'required|integer|min:1900|max:' . date('Y'),
            'purchase_price' => 'nullable|numeric|min:0',
            'supplier' => 'nullable|string|max:255',
            'location_type' => 'required|in:room,area',
            'room_id' => 'required_if:location_type,room|nullable|exists:rooms,id',
            'area_name' => 'required_if:location_type,area|nullable|string|max:255',
            'pic_type' => 'required|in:user,role',
            'pic_user_id' => 'required_if:pic_type,user|nullable|exists:users,id',
            'pic_role' => 'required_if:pic_type,role|nullable|string|max:100',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($request->hasFile('photo')) {
            if ($asset->photo) {
                Storage::disk('public')->delete($asset->photo);
            }
            $path = $request->file('photo')->store('assets', 'public');
            $validated['photo'] = $path;
        }

        if ($validated['location_type'] === 'room') {
            $validated['area_name'] = null;
        } else {
            $validated['room_id'] = null;
        }

        if ($validated['pic_type'] === 'user') {
            $validated['pic_role'] = null;
        } else {
            $validated['pic_user_id'] = null;
        }

        $asset->update($validated);

        return redirect()->route('assets.index')->with('success', 'Asset updated successfully.');
    }

    public function updateStatus(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', $this->statuses),
            'notes' => 'required|string',
        ]);

        if ($asset->status === $validated['status']) {
            return back()->with('info', 'Status is already set to ' . $validated['status']);
        }

        DB::transaction(function () use ($asset, $validated) {
            $oldStatus = $asset->status;
            
            $asset->update([
                'status' => $validated['status'],
                'condition_notes' => $validated['notes'],
            ]);
            
            AssetHistory::create([
                'asset_id' => $asset->id,
                'from_status' => $oldStatus,
                'to_status' => $validated['status'],
                'changed_by' => auth()->id(),
                'notes' => $validated['notes'],
            ]);
        });

        return back()->with('success', 'Asset status updated successfully.');
    }

    public function destroy(Asset $asset)
    {
        if ($asset->photo) {
            Storage::disk('public')->delete($asset->photo);
        }
        
        $asset->delete();
        
        return $this->ajaxOrRedirect('Asset deleted successfully.', route('assets.index'));
    }

    /**
     * Add a repair record to an asset.
     */
    public function addRepair(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'repair_date' => 'required|date',
            'repair_type' => 'required|string|max:100',
            'technician' => 'nullable|string|max:255',
            'problem_description' => 'required|string',
            'action_taken' => 'nullable|string',
            'result' => 'nullable|string',
            'cost' => 'required|numeric|min:0',
            'status' => 'required|in:in_progress,completed,failed',
            'warranty_info' => 'nullable|string|max:255',
        ]);

        $validated['asset_id'] = $asset->id;
        $validated['hotel_id'] = active_hotel_id();
        $validated['reported_by'] = auth()->id();

        $repair = \App\Models\AssetRepair::create($validated);

        // Also log in asset history
        AssetHistory::create([
            'asset_id' => $asset->id,
            'from_status' => $asset->status,
            'to_status' => $asset->status,
            'changed_by' => auth()->id(),
            'notes' => 'Perbaikan: ' . $validated['repair_type'] . ' - ' . $validated['problem_description'] . ' (Biaya: Rp ' . number_format($validated['cost'], 0, ',', '.') . ')',
        ]);

        return $this->ajaxOrRedirect('Riwayat perbaikan berhasil ditambahkan.', route('assets.show', $asset), $repair);
    }

    /**
     * Add stock mutation (in/out) to an asset.
     */
    public function addStockMutation(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'type' => 'required|in:in,out',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string',
            'notes' => 'nullable|string',
            'unit_cost' => 'nullable|numeric|min:0',
            'reference' => 'nullable|string|max:255',
        ]);

        if ($validated['type'] === 'out' && $validated['quantity'] > $asset->quantity) {
            $errorMessage = 'Jumlah keluar melebihi stok yang tersedia (' . $asset->quantity . ').';
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($errorMessage);
            }
            return back()->with('error', $errorMessage);
        }

        $mutation = DB::transaction(function () use ($asset, $validated) {
            $mutation = \App\Models\AssetStockMutation::create([
                'asset_id' => $asset->id,
                'hotel_id' => active_hotel_id(),
                'type' => $validated['type'],
                'quantity' => $validated['quantity'],
                'reason' => $validated['reason'],
                'notes' => $validated['notes'],
                'unit_cost' => $validated['unit_cost'],
                'reference' => $validated['reference'],
                'user_id' => auth()->id(),
            ]);

            if ($validated['type'] === 'in') {
                $asset->increment('quantity', $validated['quantity']);
            } else {
                $asset->decrement('quantity', $validated['quantity']);
            }

            return $mutation;
        });

        return $this->ajaxOrRedirect('Mutasi stok berhasil dicatat.', route('assets.show', $asset), $mutation);
    }
}

<?php

namespace App\Http\Controllers;

use App\Traits\AjaxResponse;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    use \App\Traits\AjaxResponse;
    public function index()
    {
        $user = auth()->user();
        $hotels = $user->hotels;

        $isAdminAnywhere = \DB::table('model_has_roles')
            ->where('model_id', $user->id)
            ->where('model_type', get_class($user))
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->whereIn('roles.name', ['Admin', 'Super Admin'])
            ->exists();

        if ($isAdminAnywhere) {
            $hotels = \App\Models\Hotel::all();
        }

        return view('branch.select', compact('hotels'));
    }

    public function switch(Request $request)
    {
        $request->validate([
            'hotel_id' => 'required|exists:hotels,id'
        ]);

        $user = auth()->user();
        $hotelId = $request->hotel_id;

        $isAdminAnywhere = \DB::table('model_has_roles')
            ->where('model_id', $user->id)
            ->where('model_type', get_class($user))
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->whereIn('roles.name', ['Admin', 'Super Admin'])
            ->exists();

        if (!$isAdminAnywhere && !$user->hotels()->where('hotel_id', $hotelId)->exists()) {
            if ($this->isAjaxRequest()) return $this->ajaxError('You do not have access to this branch.');
            return redirect()->back()->with('error', 'You do not have access to this branch.');
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function() use ($user, $hotelId) {
                $currentRoleIds = \DB::table('model_has_roles')
                    ->where('model_id', $user->id)
                    ->where('model_type', get_class($user))
                    ->pluck('role_id')
                    ->unique()
                    ->toArray();

                if (!empty($currentRoleIds)) {
                    foreach ($currentRoleIds as $roleId) {
                        \Illuminate\Support\Facades\DB::table('model_has_roles')->updateOrInsert([
                            'role_id' => $roleId,
                            'model_type' => get_class($user),
                            'model_id' => $user->id,
                            'hotel_id' => $hotelId
                        ]);
                    }
                }

                session(['active_hotel_id' => $hotelId]);
                setPermissionsTeamId($hotelId);
            });

            return $this->ajaxOrRedirect('Branch switched successfully.', route('dashboard'));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            return redirect()->back()->with('error', 'Failed to switch branch: ' . $e->getMessage());
        }
    }
}

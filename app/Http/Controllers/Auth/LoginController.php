<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * The user has been authenticated — redirect based on role.
     */
    protected function authenticated(\Illuminate\Http\Request $request, $user)
    {
        // Check if user is banned
        if ($user->is_ban) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors([
                'email' => 'Akun Anda telah diblokir. Hubungi admin untuk informasi lebih lanjut.',
            ]);
        }

        // Clear force logout flag on successful login
        if ($user->is_force_logout) {
            $user->update(['is_force_logout' => false]);
        }

        // Tenant users → tenant dashboard
        if ($user->tenant_id) {
            // Auto-set hotel from tenant
            if ($user->tenant && $user->tenant->hotel_id) {
                session(['active_hotel_id' => $user->tenant->hotel_id]);
            }
            return redirect()->route('tenant.dashboard');
        }

        // Auto-set hotel if user has only one
        if ($user->hotels()->count() === 1) {
            session(['active_hotel_id' => $user->hotels()->first()->id]);
        }

        // Auto clock-in for operational roles
        $this->autoClockIn($user);

        // OB/Housekeeping users → OB dashboard (check via DB, not Spatie)
        $userRoles = \Illuminate\Support\Facades\DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_id', $user->id)
            ->where('model_type', get_class($user))
            ->pluck('roles.name')
            ->toArray();
            
        if ((in_array('OB', $userRoles) || in_array('Housekeeping', $userRoles)) && !in_array('Admin', $userRoles)) {
            // Further distinction: OB goes to ob.dashboard, Housekeeping Manager to housekeeping.my-tasks
            if (in_array('OB', $userRoles) && !in_array('Housekeeping', $userRoles)) {
                return redirect()->route('ob.dashboard');
            } else {
                return redirect()->route('housekeeping.my-tasks');
            }
        }

        // Default
        return redirect($this->redirectTo);
    }

    /**
     * Auto clock-in attendance for operational roles.
     */
    protected function autoClockIn($user)
    {
        $operationalRoles = ['OB', 'Housekeeping', 'Security', 'Front Office'];
        $userRoles = \Illuminate\Support\Facades\DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_id', $user->id)
            ->where('model_type', get_class($user))
            ->pluck('roles.name')
            ->toArray();

        $isOperational = count(array_intersect($operationalRoles, $userRoles)) > 0
            && !in_array('Admin', $userRoles)
            && !in_array('Super Admin', $userRoles)
            && !in_array('Owner', $userRoles);

        if ($isOperational) {
            $today = now()->format('Y-m-d');
            $alreadyClockedIn = Attendance::where('employee_id', $user->id)
                ->whereDate('attendance_date', $today)
                ->exists();

            if (!$alreadyClockedIn) {
                try {
                    Attendance::create([
                        'hotel_id' => active_hotel_id(),
                        'employee_id' => $user->id,
                        'attendance_date' => $today,
                        'check_in_time' => now(),
                        'status' => 'present',
                    ]);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('Auto clock-in gagal untuk user #' . $user->id . ': ' . $e->getMessage());
                }
            }
        }
    }
}

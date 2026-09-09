<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use App\Traits\AjaxResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    use \App\Traits\AjaxResponse;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $employees = Employee::latest()->paginate(10);
        return view('employees.index', compact('employees'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $users = User::whereDoesntHave('employee')->get();
        $roles = \Spatie\Permission\Models\Role::all();
        return view('employees.create', compact('users', 'roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:employees,code|max:255',
            'position' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'create_user' => 'nullable|boolean',
            'email' => 'required_if:create_user,1|nullable|email|unique:users,email',
            'password' => 'required_if:create_user,1|nullable|string|min:6',
            'role' => 'required_if:create_user,1|nullable|string|exists:roles,name',
        ]);

        try {
            return \Illuminate\Support\Facades\DB::transaction(function () use ($request) {
                $userData = null;
                
                // 1. Handle User Creation if requested
                if ($request->create_user) {
                    $user = User::create([
                        'name' => $request->name,
                        'email' => $request->email,
                        'password' => \Illuminate\Support\Facades\Hash::make($request->password),
                        'avatar' => 'avatar-1.jpg',
                    ]);
                    $user->assignRole($request->role);
                    $userData = $user->id;
                }

                // 2. Create Employee
                $employee = Employee::create(array_merge($request->all(), [
                    'user_id' => $userData ?? $request->user_id
                ]));

                return $this->ajaxOrRedirect('Employee and login account created successfully.', route('employees.index'), $employee);
            });
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employee $employee)
    {
        $users = User::all();
        $roles = \Spatie\Permission\Models\Role::all();
        return view('employees.edit', compact('employee', 'users', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Employee $employee)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:employees,code,' . $employee->id,
            'position' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
        ]);

        try {
            $employee->update($request->all());

            return $this->ajaxOrRedirect('Employee updated successfully.', route('employees.index'), $employee);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee)
    {
        try {
            $employee->delete();

            return $this->ajaxOrRedirect('Employee deleted successfully.', route('employees.index'));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }
}

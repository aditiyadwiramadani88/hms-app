<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\AjaxResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    use \App\Traits\AjaxResponse;

    /**
     * Display the user's profile form.
     */
    public function edit()
    {
        $user = Auth::user();
        return view('profile.edit', compact('user'));
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request)
    {
        try {
            $user = Auth::user();

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:1024',
            ]);

            if ($request->hasFile('avatar')) {
                // Delete old avatar if exists
                if ($user->avatar && $user->avatar !== 'avatar-1.jpg') {
                    Storage::disk('public')->delete('avatars/' . $user->avatar);
                }

                $fileName = time() . '_' . $user->id . '.' . $request->avatar->extension();
                $request->avatar->storeAs('avatars', $fileName, 'public');
                $validated['avatar'] = $fileName;
            }

            $user->update($validated);

            $user->refresh();

            if ($this->isAjaxRequest()) {
                $avatarUrl = $user->avatar && $user->avatar !== 'avatar-1.jpg'
                    ? asset('storage/avatars/' . $user->avatar)
                    : asset('build/images/users/avatar-1.jpg');

                return $this->ajaxSuccess('Profile updated successfully.', [
                    'user' => $user,
                    'avatar_url' => $avatarUrl,
                ]);
            }

            return redirect()->back()->with('success', 'Profile updated successfully.');
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            throw $e;
        }
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request)
    {
        try {
            $request->validate([
                'current_password' => 'required|current_password',
                'password' => ['required', 'confirmed', Password::min(6)],
            ]);

            Auth::user()->update([
                'password' => Hash::make($request->password),
            ]);

            if ($this->isAjaxRequest()) {
                return $this->ajaxSuccess('Password changed successfully.');
            }

            return redirect()->back()->with('success', 'Password changed successfully.');
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            throw $e;
        }
    }
}

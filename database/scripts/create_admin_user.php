<?php

use App\Models\User;
use App\Models\Hotel;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

// Create or update user
$user = User::updateOrCreate(
    ['email' => 'admin@hotel.com'],
    ['name' => 'Super Admin', 'password' => Hash::make('password123'), 'avatar' => 'avatar-1.jpg']
);

echo "User: {$user->email} (ID: {$user->id})\n";

// Ensure Admin role exists
$role = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
echo "Role: {$role->name} (ID: {$role->id})\n";

// Get hotel
$hotel = Hotel::first();
if (!$hotel) {
    echo "ERROR: No hotel found. Create a hotel first.\n";
    return;
}
echo "Hotel: {$hotel->name} (ID: {$hotel->id})\n";

// Assign role with hotel_id (Spatie teams mode)
DB::table('model_has_roles')->updateOrInsert(
    ['model_id' => $user->id, 'model_type' => 'App\\Models\\User', 'role_id' => $role->id],
    ['hotel_id' => $hotel->id]
);
echo "Role assigned with hotel_id: {$hotel->id}\n";

// Attach user to hotel
if (!DB::table('hotel_user')->where('user_id', $user->id)->where('hotel_id', $hotel->id)->exists()) {
    DB::table('hotel_user')->insert(['user_id' => $user->id, 'hotel_id' => $hotel->id]);
    echo "User attached to hotel\n";
} else {
    echo "User already attached to hotel\n";
}

echo "\nDone! Login with: admin@hotel.com / password123\n";

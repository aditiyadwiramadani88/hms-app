<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\BelongsToHotel;

class AuditLog extends Model
{
    use BelongsToHotel;
    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'description',
        'ip_address',
        'user_agent',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getDescriptionAttribute($value)
    {
        if (!$value) {
            return $value;
        }

        static $cachedUsers = [];
        static $cachedGuests = [];
        static $cachedBookings = [];

        // 1. Resolve User #(\d+)
        if (str_contains($value, 'User #')) {
            $value = preg_replace_callback('/User #(\d+)/', function ($matches) use (&$cachedUsers) {
                $userId = $matches[1];
                if (!array_key_exists($userId, $cachedUsers)) {
                    $cachedUsers[$userId] = User::find($userId)?->name;
                }
                return $cachedUsers[$userId] ?: $matches[0];
            }, $value);
        }

        // 2. Resolve guest (\d+) or for guest (\d+) to Guest Name
        $value = preg_replace_callback('/(?:for\s+)?guest\s+#?(\d+)/i', function ($matches) use (&$cachedGuests) {
            $guestId = $matches[1];
            if (!array_key_exists($guestId, $cachedGuests)) {
                $cachedGuests[$guestId] = Guest::find($guestId)?->name;
            }
            $name = $cachedGuests[$guestId];
            return $name ? "for {$name}" : $matches[0];
        }, $value);

        // 3. If description is "Payment of Rp ... added to booking" without booking details
        if (str_ends_with(trim($value), 'added to booking') && $this->model_type === 'App\Models\Booking' && $this->model_id) {
            $bId = $this->model_id;
            if (!array_key_exists($bId, $cachedBookings)) {
                $cachedBookings[$bId] = Booking::with(['guest', 'room'])->find($bId);
            }
            $b = $cachedBookings[$bId];
            if ($b) {
                $extra = " #{$b->id}";
                if ($b->guest || $b->room) {
                    $parts = [];
                    if ($b->guest?->name) $parts[] = $b->guest->name;
                    if ($b->room?->room_number) $parts[] = "Room " . $b->room->room_number;
                    $extra .= " (" . implode(', ', $parts) . ")";
                }
                $value = preg_replace('/added to booking$/', 'added to booking' . $extra, $value);
            }
        }

        // 4. If description is "Guest checked in to room X (Booking #Y)" without guest name
        if (preg_match('/^Guest checked in to room ([^\(]+)\(Booking #(\d+)\)/i', $value, $m)) {
            $bId = $m[2];
            if (!array_key_exists($bId, $cachedBookings)) {
                $cachedBookings[$bId] = Booking::with(['guest', 'room'])->find($bId);
            }
            $b = $cachedBookings[$bId];
            if ($b && $b->guest?->name) {
                $value = preg_replace('/^Guest checked in/i', "Guest {$b->guest->name} checked in", $value);
            }
        }

        return $value;
    }

    public static function log($action, $description, $model = null)
    {
        return static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model ? $model->id : null,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}

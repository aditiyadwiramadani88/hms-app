<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;

class PublicRoomController extends Controller
{
    public function index(Request $request)
    {
        $query = Room::with('roomType')
            ->whereIn('status', ['Available', 'available', 'Clean', 'clean']);

        $typeId = $request->get('type');
        $typeName = null;

        if ($typeId) {
            $query->where('room_type_id', $typeId);
            $typeName = \App\Models\RoomType::find($typeId)?->name;
        }

        $rooms = $query->orderBy('room_number')->get();

        return view('public.rooms.index', compact('rooms', 'typeName'));
    }

    public function show($id)
    {
        $room = Room::with('roomType')->findOrFail($id);

        $relatedRooms = Room::with('roomType')
            ->where('id', '!=', $room->id)
            ->where('room_type_id', $room->room_type_id)
            ->whereIn('status', ['Available', 'available', 'Clean', 'clean'])
            ->limit(4)
            ->get();

        return view('public.rooms.show', compact('room', 'relatedRooms'));
    }
}

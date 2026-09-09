<?php

use App\Models\CleaningChecklistTemplate;
use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Sync existing rooms with all their active hotel templates
        // This ensures backward compatibility (rooms still have the same checklist items as before)
        $hotels = Hotel::all();
        foreach ($hotels as $hotel) {
            $templates = CleaningChecklistTemplate::where('hotel_id', $hotel->id)->active()->get();
            $rooms = Room::where('hotel_id', $hotel->id)->get();
            
            foreach ($rooms as $room) {
                // If the room already had type-specific templates, we could try to sync those,
                // but the PRD suggests setting all active templates to all rooms as a starting point.
                // "Migrate existing rooms: set semua room -> assign semua template items (backward compatible)"
                $room->checklistTemplates()->sync($templates->pluck('id'));
            }
        }

        // 2. Drop the old pivot table that is no longer used
        Schema::dropIfExists('checklist_template_room_type');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // To reverse, we would need to recreate the table. 
        // Data loss on checklist_template_room_type is acceptable as we are moving to per-room model.
        Schema::create('checklist_template_room_type', function ($table) {
            $table->id();
            $table->foreignId('checklist_template_id')->constrained('cleaning_checklist_templates')->onDelete('cascade');
            $table->foreignId('room_type_id')->constrained('room_types')->onDelete('cascade');
            $table->timestamps();
        });
    }
};

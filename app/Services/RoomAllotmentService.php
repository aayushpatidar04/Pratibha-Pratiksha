<?php

namespace App\Services;

use App\Models\Bed;
use App\Models\Building;
use App\Models\Resident;
use App\Models\ResidentStay;
use App\Models\Room;
use Illuminate\Support\Facades\DB;

class RoomAllotmentService
{
    /**
     * Allot a bed to a resident: creates a ResidentStay, marks the bed occupied,
     * and keeps room/building occupancy counters in sync.
     */
    public static function allot(Resident $resident, array $data): ResidentStay
    {
        return DB::transaction(function () use ($resident, $data) {
            $bed = Bed::lockForUpdate()->findOrFail($data['bed_id']);

            if ($bed->status === 'occupied') {
                throw new \RuntimeException('This bed is already occupied.');
            }

            $room = Room::findOrFail($data['room_id']);

            $stay = ResidentStay::create([
                'resident_id' => $resident->id,
                'building_id' => $data['building_id'],
                'floor_id' => $data['floor_id'],
                'room_id' => $data['room_id'],
                'bed_id' => $data['bed_id'],
                'check_in_date' => $data['check_in_date'] ?? now()->toDateString(),
                'expected_check_out_date' => $data['expected_check_out_date'] ?? null,
                'rent_amount' => $data['rent_amount'] ?? $room->monthly_rent_per_bed,
                'deposit_amount' => $data['deposit_amount'] ?? 0,
                'bill_type' => $data['bill_type'] ?? 'monthly',
                'status' => 'active',
                'notes' => $data['notes'] ?? null,
            ]);

            $bed->update(['status' => 'occupied', 'resident_id' => $resident->id]);

            $room->increment('occupied_beds');
            $room->refresh();
            $room->update([
                'status' => $room->occupied_beds >= $room->capacity ? 'occupied' : 'partially_occupied',
            ]);

            Building::where('id', $data['building_id'])->increment('occupied');

            if ($resident->status === 'upcoming') {
                $resident->update(['status' => 'active']);
            }

            return $stay;
        });
    }

    /**
     * Check a resident out: closes the active stay and frees up the bed.
     */
    public static function checkout(ResidentStay $stay, ?string $checkOutDate = null): ResidentStay
    {
        return DB::transaction(function () use ($stay, $checkOutDate) {
            $stay->update([
                'status' => 'ended',
                'actual_check_out_date' => $checkOutDate ?? now()->toDateString(),
            ]);

            $bed = Bed::find($stay->bed_id);
            if ($bed) {
                $bed->update(['status' => 'vacant', 'resident_id' => null]);
            }

            $room = Room::find($stay->room_id);
            if ($room) {
                $room->decrement('occupied_beds');
                $room->refresh();
                $room->update([
                    'status' => $room->occupied_beds <= 0 ? 'available' : 'partially_occupied',
                ]);
            }

            Building::where('id', $stay->building_id)->decrement('occupied');

            return $stay;
        });
    }
}

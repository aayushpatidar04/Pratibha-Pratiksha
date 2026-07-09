<?php

namespace Database\Seeders;

use App\Models\Bed;
use App\Models\Building;
use App\Models\Floor;
use App\Models\Resident;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@hostel.test',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
        ]);

        $building = Building::create([
            'name' => 'Block A', 'code' => 'BLK-A', 'type' => 'boys',
            'address' => '123 College Road, Jodhpur', 'total_floors' => 2, 'status' => 'active',
        ]);

        $floor1 = Floor::create(['building_id' => $building->id, 'floor_number' => 1, 'name' => 'Ground Floor']);
        $floor2 = Floor::create(['building_id' => $building->id, 'floor_number' => 2, 'name' => 'First Floor']);

        foreach ([$floor1, $floor2] as $floor) {
            for ($i = 1; $i <= 3; $i++) {
                $room = Room::create([
                    'building_id' => $building->id,
                    'floor_id' => $floor->id,
                    'room_number' => "{$floor->floor_number}0{$i}",
                    'room_type' => '2_seater',
                    'capacity' => 2,
                    'monthly_rent_per_bed' => 6000,
                    'has_wifi' => true,
                    'status' => 'available',
                ]);
                for ($b = 1; $b <= 2; $b++) {
                    Bed::create(['room_id' => $room->id, 'bed_number' => "B{$b}", 'status' => 'vacant']);
                }
            }
        }

        Resident::create([
            'resident_code' => 'PP-'.now()->year.'-0001',
            'first_name' => 'Rahul', 'last_name' => 'Sharma', 'phone' => '9876543210',
            'gender' => 'male', 'course' => 'B.Tech', 'institute' => 'ITS Engineering College',
            'status' => 'active', 'created_by' => $admin->id,
        ]);

        Building::where('id', $building->id)->update(['total_rooms' => Room::where('building_id', $building->id)->count()]);

        // Default KYC document checklist — Aadhar + Photo required out of the box,
        // rest available but off until a super admin turns them on.
        $kycDocs = [
            ['document_type' => 'aadhar_card', 'label' => 'Aadhar Card', 'is_required' => true, 'sort_order' => 1],
            ['document_type' => 'photo', 'label' => 'Passport Photo', 'is_required' => true, 'sort_order' => 2],
            ['document_type' => 'pan_card', 'label' => 'PAN Card', 'is_required' => false, 'sort_order' => 3],
            ['document_type' => 'marksheet', 'label' => 'Latest Marksheet', 'is_required' => false, 'sort_order' => 4],
            ['document_type' => 'caste_certificate', 'label' => 'Caste Certificate', 'is_required' => false, 'sort_order' => 5],
            ['document_type' => 'medical_certificate', 'label' => 'Medical Certificate', 'is_required' => false, 'sort_order' => 6],
            ['document_type' => 'parent_consent', 'label' => 'Parent Consent Form', 'is_required' => false, 'sort_order' => 7],
            ['document_type' => 'other', 'label' => 'Other Document', 'is_required' => false, 'sort_order' => 8],
        ];
        foreach ($kycDocs as $doc) {
            \App\Models\KycRequirement::updateOrCreate(['document_type' => $doc['document_type']], $doc);
        }
    }
}
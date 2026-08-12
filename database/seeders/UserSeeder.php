<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Default Super Admin — required by spec (poin 33).
        // The 'hashed' cast on User::password means plain text here is hashed automatically.
        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@dms.local'],
            [
                'name' => 'Super Administrator',
                'password' => 'password',
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $superAdmin->syncRoles([UserRole::SuperAdmin->value]);

        // Sample users per role, so the workflow can be exercised end to end
        // (login as each role, submit → review → approve → publish).
        $lpm = Unit::where('code', 'LPM')->first();
        $baak = Unit::where('code', 'BAAK')->first();

        $sampleUsers = [
            [
                'email' => 'admindok@dms.local',
                'name' => 'Admin Dokumen LPM',
                'unit_id' => $lpm?->id,
                'position' => 'Staf Dokumen Mutu',
                'role' => UserRole::AdminDokumen,
            ],
            [
                'email' => 'staff@dms.local',
                'name' => 'Staff BAAK',
                'unit_id' => $baak?->id,
                'position' => 'Staf Administrasi',
                'role' => UserRole::Staff,
            ],
            [
                'email' => 'reviewer@dms.local',
                'name' => 'Reviewer LPM',
                'unit_id' => $lpm?->id,
                'position' => 'Kepala LPM',
                'role' => UserRole::Reviewer,
            ],
            [
                'email' => 'viewer@dms.local',
                'name' => 'Viewer Umum',
                'unit_id' => null,
                'position' => null,
                'role' => UserRole::Viewer,
            ],
        ];

        foreach ($sampleUsers as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => 'password',
                    'email_verified_at' => now(),
                    'unit_id' => $data['unit_id'],
                    'position' => $data['position'],
                    'is_active' => true,
                ]
            );
            $user->syncRoles([$data['role']->value]);
        }
    }
}

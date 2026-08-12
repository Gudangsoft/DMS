<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $superAdmin = Role::firstOrCreate(['name' => UserRole::SuperAdmin->value, 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        $adminDokumen = Role::firstOrCreate(['name' => UserRole::AdminDokumen->value, 'guard_name' => 'web']);
        $adminDokumen->syncPermissions([
            'documents.view_any',
            'documents.view',
            'documents.create',
            'documents.update',
            'documents.delete',
            'documents.download',
            'documents.comment',
            'documents.submit',
            'documents.publish',
            'documents.archive',
            'master_data.view_any',
            'master_data.manage',
            'pages.manage',
        ]);

        $staff = Role::firstOrCreate(['name' => UserRole::Staff->value, 'guard_name' => 'web']);
        $staff->syncPermissions([
            'documents.view_own',
            'documents.create',
            'documents.update_own',
            'documents.download',
            'documents.submit',
            'documents.comment',
        ]);

        $reviewer = Role::firstOrCreate(['name' => UserRole::Reviewer->value, 'guard_name' => 'web']);
        $reviewer->syncPermissions([
            'documents.view_any',
            'documents.view',
            'documents.download',
            'documents.comment',
            'documents.review',
            'documents.approve',
            'documents.reject',
            'documents.request_revision',
        ]);

        $viewer = Role::firstOrCreate(['name' => UserRole::Viewer->value, 'guard_name' => 'web']);
        $viewer->syncPermissions([
            'documents.view_any',
            'documents.view',
        ]);
        // documents.download is intentionally NOT granted by default for Viewer/User —
        // per spec (poin 15, RESTRICTED level): download for restricted docs is granted
        // per-user via $user->givePermissionTo('documents.download'), not via the role.
    }
}

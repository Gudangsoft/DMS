<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    public const PERMISSIONS = [
        // Documents
        'documents.view_any',
        'documents.view',
        'documents.view_own',
        'documents.create',
        'documents.update',
        'documents.update_own',
        'documents.delete',
        'documents.delete_own',
        'documents.restore',
        'documents.force_delete',
        'documents.download',
        'documents.comment',
        'documents.submit',
        'documents.review',
        'documents.approve',
        'documents.reject',
        'documents.request_revision',
        'documents.publish',
        'documents.archive',
        // Granted directly to individual users (not via role) for RESTRICTED/CONFIDENTIAL
        // access — see poin 15 and app/Policies/DocumentPolicy.php.
        'documents.access_restricted',
        'documents.access_confidential',

        // Master data (units, categories, subcategories, document types)
        'master_data.view_any',
        'master_data.manage',

        // Institutional content pages (Sambutan Ketua, Tentang DMS, dst.)
        'pages.manage',

        // User management
        'users.view_any',
        'users.create',
        'users.update',
        'users.delete',

        // Role & permission management
        'roles.view_any',
        'roles.create',
        'roles.update',
        'roles.delete',
        'permissions.view_any',
        'permissions.assign',

        // Reports
        'reports.view',
        'reports.export',

        // System
        'audit_log.view',
        'settings.manage',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // firstOrCreate keeps this idempotent on re-run without needing to truncate
        // a table that role_has_permissions/model_has_permissions have FK constraints on.
        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }
}

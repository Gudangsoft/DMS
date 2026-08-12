<?php

namespace App\Enums;

/**
 * Canonical Spatie role names. Referenced by RoleSeeder, UserSeeder, User::canAccessPanel(),
 * and the Filament resource policies so the role string is never hand-typed more than once.
 */
enum UserRole: string
{
    case SuperAdmin = 'Super Admin';
    case AdminDokumen = 'Admin Dokumen';
    case Staff = 'Staff/Uploader';
    case Reviewer = 'Reviewer/Approver';
    case Viewer = 'Viewer/User';

    /**
     * Roles that are allowed to sign in to the Filament back-office panel.
     * Viewer/User is a frontend-only role (see routes/web.php).
     *
     * @return array<int, self>
     */
    public static function panelRoles(): array
    {
        return [self::SuperAdmin, self::AdminDokumen, self::Staff, self::Reviewer];
    }
}

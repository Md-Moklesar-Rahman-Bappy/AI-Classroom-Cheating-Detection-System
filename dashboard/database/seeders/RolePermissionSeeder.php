<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command->warn('Seeder only for local/testing');

            return;
        }
        $roles = [
            'system_admin' => 'System Administrator',
            'exam_admin' => 'Exam Administrator',
            'invigilator' => 'Invigilator',
            'reviewer' => 'Reviewer',
            'auditor' => 'Auditor',
        ];
        foreach ($roles as $name => $desc) {
            Role::firstOrCreate(['name' => $name], ['description' => $desc]);
        }

        $perms = [
            ['manage_rooms', 'rooms'], ['manage_sessions', 'sessions'], ['manage_cameras', 'cameras'],
            ['upload_recordings', 'recordings'], ['view_evidence', 'evidence'], ['review_events', 'events'],
            ['export_reports', 'reports'], ['view_audit_logs', 'audit'], ['manage_users', 'users'],
            ['manage_models', 'models'], ['view_metrics', 'metrics'],
        ];
        foreach ($perms as [$name,$group]) {
            Permission::firstOrCreate(['name' => $name], ['group' => $group]);
        }

        $rolePerms = [
            'system_admin' => ['manage_rooms', 'manage_sessions', 'manage_cameras', 'upload_recordings', 'view_evidence', 'review_events', 'export_reports', 'view_audit_logs', 'manage_users', 'manage_models', 'view_metrics'],
            'exam_admin' => ['manage_rooms', 'manage_sessions', 'manage_cameras', 'upload_recordings', 'view_evidence', 'export_reports', 'view_metrics'],
            'invigilator' => ['view_evidence', 'view_metrics'],
            'reviewer' => ['view_evidence', 'review_events'],
            'auditor' => ['view_audit_logs', 'view_evidence', 'view_metrics'],
        ];
        foreach ($rolePerms as $roleName => $permNames) {
            $role = Role::where('name', $roleName)->first();
            $ids = Permission::whereIn('name', $permNames)->pluck('id');
            $role->permissions()->sync($ids);
        }

        $demos = [
            ['System Admin', 'admin@example.com', 'system_admin'],
            ['Exam Admin', 'exam@example.com', 'exam_admin'],
            ['Invigilator', 'invigilator@example.com', 'invigilator'],
            ['Reviewer', 'reviewer@example.com', 'reviewer'],
            ['Auditor', 'auditor@example.com', 'auditor'],
        ];
        foreach ($demos as [$name,$email,$roleName]) {
            $user = User::firstOrCreate(['email' => $email], ['name' => $name, 'password' => Hash::make('Password123!'), 'email_verified_at' => now()]);
            $role = Role::where('name', $roleName)->first();
            $user->roles()->syncWithoutDetaching([$role->id]);
        }
    }
}

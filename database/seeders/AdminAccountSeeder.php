<?php

namespace Database\Seeders;

use App\Constants\RoleStatusConstant;
use App\Constants\UserStatusConstant;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminAccountSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::firstOrCreate(
            ['role_name' => RoleStatusConstant::ADMIN],
            ['description' => 'Employee administrator']
        );

        $user = User::firstOrNew(['email' => 'admin@mail.com']);
        $user->password = Hash::make('12345678');
        $user->status = UserStatusConstant::ACTIVE;
        $user->save();

        $user->roles()->syncWithoutDetaching([$role->id]);

        Employee::updateOrCreate(
            ['user_id' => $user->id],
            [
                'first_name' => 'Admin',
                'middle_name' => null,
                'last_name' => 'User',
                'contact_number' => '09170000001',
                'position' => 'Administrator',
            ]
        );

        $this->command?->info('Admin account is ready: admin@mail.com / 12345678');
    }
}

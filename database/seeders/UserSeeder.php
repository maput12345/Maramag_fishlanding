<?php

namespace Database\Seeders;

use App\Constants\RoleStatusConstant;
use App\Constants\UserStatusConstant;
use App\Models\Broker;
use App\Models\Employee;
use App\Models\Role;
use App\Models\Stall;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        $adminRole = Role::firstOrCreate(
            ['role_name' => RoleStatusConstant::ADMIN],
            ['description' => 'Employee administrator']
        );

        $staffRole = Role::firstOrCreate(
            ['role_name' => RoleStatusConstant::STAFF],
            ['description' => 'Employee staff']
        );

        $brokerRole = Role::firstOrCreate(
            ['role_name' => RoleStatusConstant::BROKER],
            ['description' => 'Fish broker']
        );

        Role::firstOrCreate(
            ['role_name' => RoleStatusConstant::APPLICANT],
            ['description' => 'Broker applicant']
        );

        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@mail.com',
                'password' => Hash::make('12345678'),
                'role' => RoleStatusConstant::ADMIN,
                'position' => 'Administrator',
                'contact_number' => '09170000001',
            ],
            [
                'name' => 'LEEO Staff',
                'email' => 'staff@mail.com',
                'password' => Hash::make('12345678'),
                'role' => RoleStatusConstant::STAFF,
                'position' => 'Staff',
                'contact_number' => '09170000002',
            ],
            [
                'name' => 'John Broker',
                'email' => 'john.broker@mail.com',
                'password' => Hash::make('12345678'),
                'role' => RoleStatusConstant::BROKER,
                'address' => '789 Sales Street, Broker City, BC 54321',
                'stall_name' => 'Broker Stall 1',
            ],
            [
                'name' => 'Jane Sales',
                'email' => 'jane.sales@mail.com',
                'password' => Hash::make('12345678'),
                'role' => RoleStatusConstant::BROKER,
                'address' => '321 Commerce Blvd, Broker City, BC 54322',
                'stall_name' => 'Broker Stall 2',
            ],
            [
                'name' => 'Mike Seller',
                'email' => 'mike.seller@mail.com',
                'password' => Hash::make('12345678'),
                'role' => RoleStatusConstant::BROKER,
                'address' => '654 Trade Lane, Broker City, BC 54323',
                'stall_name' => 'Broker Stall 3',
            ],
            [
                'name' => 'Lisa Agent',
                'email' => 'lisa.agent@mail.com',
                'password' => Hash::make('12345678'),
                'role' => RoleStatusConstant::BROKER,
                'address' => '987 Market Square, Broker City, BC 54324',
                'stall_name' => 'Broker Stall 4',
            ],
        ];

        foreach ($users as $userData) {
            $nameParts = User::splitName($userData['name']);

            $user = User::create([
                'email' => $userData['email'],
                'password' => $userData['password'],
                'status' => UserStatusConstant::ACTIVE,
            ]);

            if ($userData['role'] === RoleStatusConstant::ADMIN) {
                $user->roles()->syncWithoutDetaching([$adminRole->id]);
                Employee::createProfile($user->id, [
                    'first_name' => $nameParts['first_name'],
                    'middle_name' => $nameParts['middle_name'],
                    'last_name' => $nameParts['last_name'],
                    'contact_number' => $userData['contact_number'] ?? null,
                    'position' => $userData['position'] ?? 'Administrator',
                ]);
            }

            if ($userData['role'] === RoleStatusConstant::STAFF) {
                $user->roles()->syncWithoutDetaching([$staffRole->id]);
                Employee::createProfile($user->id, [
                    'first_name' => $nameParts['first_name'],
                    'middle_name' => $nameParts['middle_name'],
                    'last_name' => $nameParts['last_name'],
                    'contact_number' => $userData['contact_number'] ?? null,
                    'position' => $userData['position'] ?? 'Staff',
                ]);
            }

            if ($userData['role'] === RoleStatusConstant::BROKER) {
                $stallNumber = preg_replace('/[^0-9]/', '', (string) ($userData['stall_name'] ?? ''));
                $stall = $stallNumber !== '' ? Stall::where('stall_number', $stallNumber)->first() : null;

                $user->roles()->syncWithoutDetaching([$brokerRole->id]);
                Broker::createProfile($user->id, [
                    'first_name' => $nameParts['first_name'],
                    'middle_name' => $nameParts['middle_name'],
                    'last_name' => $nameParts['last_name'],
                    'address' => $userData['address'],
                    'stall_name' => $userData['stall_name'] ?? null,
                    'stall_id' => $stall?->id,
                    'broker_status' => 'Active',
                    'approval_date' => now()->toDateString(),
                ]);

                if ($stall) {
                    $stall->update(['stall_status' => 'Occupied']);
                }
            }
        }

        $this->command->info('UserSeeder completed successfully!');
        $this->command->info('Created ' . User::count() . ' users');
        $this->command->info('Created ' . User::admins()->count() . ' admins');
        $this->command->info('Created ' . User::staff()->count() . ' staff');
        $this->command->info('Created ' . Broker::count() . ' brokers');
    }
}

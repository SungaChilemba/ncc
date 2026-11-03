<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SiteContentSeeder::class,
            AccessControlSeeder::class,
        ]);

        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('password'),
                'is_active' => true,
            ],
        );

        $admin->roles()->sync(
            Role::where('key', 'system_admin')->pluck('id'),
        );

        if (! app()->isProduction()) {
            $this->call(CaseStaffSeeder::class);
        }
    }
}

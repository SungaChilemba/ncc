<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Support\AccessCatalog;
use App\Support\Totp;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Development-only accounts for the built-in case roles. Each account is
 * seeded with a confirmed TOTP secret so the challenge flow can be simulated
 * without scanning a QR code. Never run this seeder in production.
 */
class CaseStaffSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('CaseStaffSeeder is disabled in production.');

            return;
        }

        if (! Role::where('key', 'case_manager')->exists()) {
            $this->call(AccessControlSeeder::class);
        }

        $caseRoles = collect(AccessCatalog::roles())
            ->filter(fn (array $definition) => $definition['category'] === 'case');

        foreach ($caseRoles as $key => $definition) {
            $email = str_replace('case_', '', $key).'@example.com';
            $secret = Totp::generateSecret();
            $recoveryCodes = collect(range(1, 8))
                ->map(fn () => strtoupper(Str::random(8)))
                ->all();

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $definition['name'],
                    'password' => Hash::make('password'),
                    'is_active' => true,
                ],
            );

            $user->roles()->sync(
                Role::where('key', $key)->pluck('id'),
            );

            $user->forceFill([
                'two_factor_secret' => $secret,
                'two_factor_recovery_codes' => $recoveryCodes,
                'two_factor_confirmed_at' => now(),
            ])->save();

            $this->command?->info(sprintf(
                '%s: %s / password — TOTP secret: %s',
                $definition['name'],
                $email,
                $secret,
            ));
        }
    }
}

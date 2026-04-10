<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $user = User::factory()->create([
            'name' => 'ngocmy01',
            'email' => 'test@example.com',
            'password' => 'ngocmy01'
        ]);

        Tenant::create([
            'name' => 'FlowSaaS Demo',
            'slug' => 'flowsaas-demo',
            'is_active' => true,
            'trial_ends_at' => now()->addDays(14),
            'settings' => json_encode(['theme' => 'light', 'currency' => 'USD']),
        ]);

        Tenant::create([
            'name' => 'Acme Corporation',
            'slug' => 'acme-corp',
            'is_active' => true,
            'trial_ends_at' => now()->addDays(30),
            'settings' => json_encode(['theme' => 'dark', 'currency' => 'EUR']),
        ]);
    }
}

<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a specific admin user
        if (! User::where('email', 'admin@example.com')->exists()) {
            User::create([
                'name'     => 'Admin User',
                'email'    => 'admin@example.com',
                'password' => bcrypt('password'),
            ]);
        }

        // Create 10 random users
        User::factory()->count(10)->create();
    }
}

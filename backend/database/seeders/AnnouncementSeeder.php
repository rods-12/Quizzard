<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AnnouncementSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating announcements...');

        // Use existing admins/superadmins; fall back to factory if none exist yet.
        $adminIds = User::whereIn('role', ['admin', 'superadmin'])
            ->pluck('id')
            ->toArray();

        if (empty($adminIds)) {
            $adminIds = [User::factory()->admin()->create()->id];
        }

        $now  = now();
        $rows = [];

        // 30 announcements spread across available admins
        for ($i = 0; $i < 30; $i++) {
            $rows[] = [
                'admin_id'   => $adminIds[array_rand($adminIds)],
                'title'      => fake()->sentence(6),
                'body'       => fake()->paragraphs(rand(1, 3), true),
                'created_at' => fake()->dateTimeBetween('-6 months', 'now'),
                'updated_at' => $now,
            ];
        }

        DB::table('announcements')->insert($rows);

        $this->command->info('✅ Announcements seeded: ' . count($rows));
    }
}

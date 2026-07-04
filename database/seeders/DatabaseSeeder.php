<?php

namespace Database\Seeders;

use App\Models\House;
use App\Models\Setting;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@budiman.test'],
            [
                'name' => 'Admin Sukan Budiman',
                'role' => 'admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        collect([
            ['name' => 'Rumah Hijau', 'color' => '#15803d', 'description' => 'Rumah sukan hijau Kampung Budiman.'],
            ['name' => 'Rumah Merah', 'color' => '#b91c1c', 'description' => 'Rumah sukan merah Kampung Budiman.'],
            ['name' => 'Rumah Biru', 'color' => '#1d4ed8', 'description' => 'Rumah sukan biru Kampung Budiman.'],
            ['name' => 'Rumah Kuning', 'color' => '#ca8a04', 'description' => 'Rumah sukan kuning Kampung Budiman.'],
        ])->each(fn (array $house) => House::updateOrCreate(['name' => $house['name']], $house));

        collect([
            ['name' => 'Balloon Rush', 'category' => 'Dewasa', 'max_players_per_group' => 12, 'duration_minutes' => 15, 'group_code' => 'CDCS2406A'],
            ['name' => 'Pindah Cawan', 'category' => 'Kanak-Kanak', 'max_players_per_group' => 10, 'duration_minutes' => 20, 'group_code' => 'CDCS2406A'],
            ['name' => 'Catch the Scammer', 'category' => 'Terbuka', 'max_players_per_group' => 12, 'duration_minutes' => 20, 'group_code' => 'CDCS2596B'],
            ['name' => 'Radio Rosak', 'category' => 'Terbuka', 'max_players_per_group' => 14, 'duration_minutes' => 20, 'group_code' => 'CDCS2596B'],
            ['name' => 'Jalan Berpasangan', 'category' => 'Dewasa', 'max_players_per_group' => 12, 'duration_minutes' => 20, 'group_code' => 'CDCS2406C'],
            ['name' => 'Catch the Duck', 'category' => 'Kanak-Kanak', 'max_players_per_group' => 12, 'duration_minutes' => 20, 'group_code' => 'CDCS2406C'],
            ['name' => 'Tarik Tali Final', 'category' => 'Terbuka', 'max_players_per_group' => 10, 'duration_minutes' => 20, 'group_code' => 'CDCS2406C'],
        ])->each(fn (array $sport) => Sport::updateOrCreate(['name' => $sport['name']], $sport + ['is_active' => true]));

        collect([
            'registration_is_open' => '1',
            'registration_deadline' => null,
            'event_date' => null,
            'event_time' => null,
            'event_venue' => 'Kampung Budiman',
            'admin_contact' => null,
            'whatsapp_template' => "Assalamualaikum/Salam sejahtera [Nama],\n\nIni adalah peringatan bahawa anda telah berdaftar untuk Sukan Rakyat Kampung Budiman.\n\nRumah Sukan: [Rumah]\nTarikh: [Tarikh]\nMasa: [Masa]\nTempat: [Lokasi]\n\nSila hadir awal untuk urusan pendaftaran kehadiran.\nTerima kasih.",
        ])->each(fn ($value, string $key) => Setting::setValue($key, $value));
    }
}

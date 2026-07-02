<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Guardian;
use App\Models\House;
use App\Models\Participant;
use App\Models\Sport;
use App\Models\SportRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_settings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/admin/settings', [
            'registration_is_open' => '1',
            'registration_deadline' => now()->addDay()->format('Y-m-d H:i:s'),
            'event_date' => now()->addWeek()->format('Y-m-d'),
            'event_time' => '8.00 pagi',
            'event_venue' => 'Dewan Kampung Budiman',
            'admin_contact' => '0123456789',
            'whatsapp_template' => 'Salam [Nama] [Rumah] [Tarikh] [Masa] [Lokasi]',
        ])->assertRedirect('/admin/settings');

        $this->assertDatabaseHas('settings', ['key' => 'event_venue', 'value' => 'Dewan Kampung Budiman']);
    }

    public function test_admin_cannot_overfill_accepted_event_capacity(): void
    {
        $user = User::factory()->create();
        $house = House::create(['name' => 'Rumah Hijau']);
        $sport = Sport::create(['name' => 'Balloon Rush', 'category' => 'Dewasa', 'max_players_per_group' => 1, 'is_active' => true]);
        $accepted = Participant::create([
            'registration_code' => 'SRKB-260602-FIRST',
            'name' => 'Peserta Pertama',
            'age' => 30,
            'phone' => '0121111111',
            'category' => 'Dewasa',
            'house_id' => $house->id,
            'status' => 'Aktif',
        ]);
        SportRegistration::create(['participant_id' => $accepted->id, 'sport_id' => $sport->id, 'status' => 'Diterima']);

        $this->actingAs($user)->post('/admin/participants', [
            'name' => 'Peserta Kedua',
            'age' => 31,
            'phone' => '0122222222',
            'category' => 'Dewasa',
            'house_id' => $house->id,
            'status' => 'Aktif',
            'sport_ids' => [$sport->id],
            'sport_statuses' => [$sport->id => 'Diterima'],
        ])->assertSessionHasErrors("sport_statuses.{$sport->id}");
    }

    public function test_audit_log_page_filters_work(): void
    {
        $user = User::factory()->create();
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'update',
            'model_type' => Sport::class,
            'model_id' => 1,
            'ip_address' => '127.0.0.1',
        ]);

        $this->actingAs($user)
            ->get('/admin/audit-logs?action=update&model_type='.urlencode(Sport::class))
            ->assertOk()
            ->assertSee('Sport #1');
    }

    public function test_print_report_renders_participant_list(): void
    {
        $user = User::factory()->create();
        $house = House::create(['name' => 'Rumah Biru']);
        Participant::create([
            'registration_code' => 'SRKB-260602-PRINT',
            'name' => 'Peserta Cetak',
            'age' => 19,
            'phone' => '0123333333',
            'category' => 'Terbuka',
            'house_id' => $house->id,
            'status' => 'Aktif',
        ]);

        $this->actingAs($user)
            ->get('/admin/reports/print')
            ->assertOk()
            ->assertSee('Peserta Cetak');
    }

    public function test_admin_create_derives_category_from_age(): void
    {
        $user = User::factory()->create();
        $house = House::create(['name' => 'Rumah Merah']);
        $sport = Sport::create(['name' => 'Pindah Cawan', 'category' => 'Kanak-Kanak', 'is_active' => true]);

        $this->actingAs($user)->post('/admin/participants', [
            'name' => 'Peserta Admin Kanak',
            'age' => 10,
            'phone' => '0131111111',
            'category' => 'Dewasa',
            'house_id' => $house->id,
            'status' => 'Aktif',
            'sport_ids' => [$sport->id],
            'sport_statuses' => [$sport->id => 'Diterima'],
            'guardian_name' => 'Penjaga Admin',
            'guardian_phone' => '0121111111',
            'guardian_relationship' => 'Ibu',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('participants', [
            'name' => 'Peserta Admin Kanak',
            'category' => 'Kanak-Kanak',
        ]);
    }

    public function test_admin_create_derives_teenager_category_from_age(): void
    {
        $user = User::factory()->create();
        $house = House::create(['name' => 'Rumah Merah']);
        $sport = Sport::create(['name' => 'Acara Remaja Admin', 'category' => 'Remaja', 'is_active' => true]);

        $this->actingAs($user)->post('/admin/participants', [
            'name' => 'Peserta Admin Remaja',
            'age' => 15,
            'phone' => '0132222222',
            'category' => 'Dewasa',
            'house_id' => $house->id,
            'status' => 'Aktif',
            'sport_ids' => [$sport->id],
            'sport_statuses' => [$sport->id => 'Diterima'],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('participants', [
            'name' => 'Peserta Admin Remaja',
            'category' => 'Remaja',
            'guardian_id' => null,
        ]);
    }

    public function test_admin_can_create_teenager_sport_category(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/admin/sports', [
            'name' => 'Acara Khas Remaja',
            'category' => 'Remaja',
            'max_players_per_group' => 12,
            'duration_minutes' => 20,
            'is_active' => '1',
        ])->assertRedirect('/admin/sports');

        $this->assertDatabaseHas('sports', [
            'name' => 'Acara Khas Remaja',
            'category' => 'Remaja',
        ]);
    }

    public function test_admin_category_forms_and_filters_include_teenager(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin/sports/create')
            ->assertOk()
            ->assertSee('Remaja');

        $this->actingAs($user)
            ->get('/admin/participants')
            ->assertOk()
            ->assertSee('Remaja');

        $this->actingAs($user)
            ->get('/admin/reports')
            ->assertOk()
            ->assertSee('Remaja');
    }

    public function test_admin_can_update_multiple_event_registrations_with_statuses(): void
    {
        $user = User::factory()->create();
        $house = House::create(['name' => 'Rumah Hijau']);
        $firstSport = Sport::create(['name' => 'Catch the Scammer', 'category' => 'Terbuka', 'is_active' => true]);
        $secondSport = Sport::create(['name' => 'Radio Rosak', 'category' => 'Terbuka', 'is_active' => true]);

        $response = $this->actingAs($user)->post('/admin/participants', [
            'name' => 'Peserta Multi Admin',
            'age' => 30,
            'phone' => '0122223333',
            'house_id' => $house->id,
            'status' => 'Aktif',
            'sport_ids' => [$firstSport->id, $secondSport->id],
            'sport_statuses' => [
                $firstSport->id => 'Diterima',
                $secondSport->id => 'Senarai Menunggu',
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $participant = Participant::where('name', 'Peserta Multi Admin')->first();

        $this->assertDatabaseHas('sport_registrations', [
            'participant_id' => $participant->id,
            'sport_id' => $firstSport->id,
            'status' => 'Diterima',
        ]);
        $this->assertDatabaseHas('sport_registrations', [
            'participant_id' => $participant->id,
            'sport_id' => $secondSport->id,
            'status' => 'Senarai Menunggu',
        ]);
    }

    public function test_whatsapp_button_uses_guardian_phone_when_child_has_no_phone(): void
    {
        $user = User::factory()->create();
        $house = House::create(['name' => 'Rumah Merah']);
        $guardian = Guardian::create([
            'name' => 'Penjaga WhatsApp',
            'phone' => '0127000000',
            'relationship' => 'Ibu',
        ]);
        $participant = Participant::create([
            'registration_code' => 'SRKB-260602-WHATS',
            'name' => 'Anak WhatsApp',
            'age' => 9,
            'phone' => null,
            'category' => 'Kanak-Kanak',
            'house_id' => $house->id,
            'guardian_id' => $guardian->id,
            'status' => 'Aktif',
        ]);

        $this->actingAs($user)
            ->get(route('admin.participants.show', $participant))
            ->assertOk()
            ->assertSee('https://wa.me/60127000000', false);
    }

    public function test_category_correction_migration_updates_existing_records(): void
    {
        $house = House::create(['name' => 'Rumah Kuning']);
        Participant::create([
            'registration_code' => 'SRKB-260602-FIX01',
            'name' => 'Kategori Salah Kanak',
            'age' => 11,
            'phone' => '0124444444',
            'category' => 'Dewasa',
            'house_id' => $house->id,
            'status' => 'Aktif',
        ]);
        Participant::create([
            'registration_code' => 'SRKB-260602-FIX02',
            'name' => 'Kategori Salah Dewasa',
            'age' => 12,
            'phone' => '0125555555',
            'category' => 'Kanak-Kanak',
            'house_id' => $house->id,
            'status' => 'Aktif',
        ]);

        $migration = include database_path('migrations/2026_06_02_123934_recalculate_participant_categories_from_age.php');
        $migration->up();

        $this->assertDatabaseHas('participants', ['name' => 'Kategori Salah Kanak', 'category' => 'Kanak-Kanak']);
        $this->assertDatabaseHas('participants', ['name' => 'Kategori Salah Dewasa', 'category' => 'Dewasa']);
    }

    public function test_teenager_category_correction_migration_updates_existing_records(): void
    {
        $house = House::create(['name' => 'Rumah Kuning']);
        Participant::create([
            'registration_code' => 'SRKB-260703-FIX12',
            'name' => 'Kategori Umur Dua Belas',
            'age' => 12,
            'phone' => '0124444444',
            'category' => 'Dewasa',
            'house_id' => $house->id,
            'status' => 'Aktif',
        ]);
        Participant::create([
            'registration_code' => 'SRKB-260703-FIX15',
            'name' => 'Kategori Umur Lima Belas',
            'age' => 15,
            'phone' => '0125555555',
            'category' => 'Dewasa',
            'house_id' => $house->id,
            'status' => 'Aktif',
        ]);
        Participant::create([
            'registration_code' => 'SRKB-260703-FIX18',
            'name' => 'Kategori Umur Lapan Belas',
            'age' => 18,
            'phone' => '0126666666',
            'category' => 'Remaja',
            'house_id' => $house->id,
            'status' => 'Aktif',
        ]);

        $migration = include database_path('migrations/2026_07_03_000001_recalculate_participant_categories_for_teenagers.php');
        $migration->up();

        $this->assertDatabaseHas('participants', ['name' => 'Kategori Umur Dua Belas', 'category' => 'Kanak-Kanak']);
        $this->assertDatabaseHas('participants', ['name' => 'Kategori Umur Lima Belas', 'category' => 'Remaja']);
        $this->assertDatabaseHas('participants', ['name' => 'Kategori Umur Lapan Belas', 'category' => 'Dewasa']);
    }
}

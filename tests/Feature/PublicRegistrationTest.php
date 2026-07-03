<?php

namespace Tests\Feature;

use App\Models\House;
use App\Models\Participant;
use App\Models\Setting;
use App\Models\Sport;
use App\Models\SportRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function register(array $data)
    {
        $this->withSession(['registration_captcha_answer' => 7]);

        return $this->post('/daftar', $data + [
            'captcha_answer' => 7,
            'consent_agreement' => '1',
        ]);
    }

    private function adultPayload(House $house, Sport $sport): array
    {
        return [
            'name' => 'Peserta Captcha',
            'age' => 25,
            'phone' => '0123456789',
            'house_id' => $house->id,
            'sport_ids' => [$sport->id],
        ];
    }

    public function test_public_participant_can_register_and_receive_a_registration_code(): void
    {
        $house = House::create(['name' => 'Rumah Hijau']);
        $sport = Sport::create(['name' => 'Balloon Rush', 'category' => 'Dewasa', 'max_players_per_group' => 12, 'is_active' => true]);

        $response = $this->register([
            'name' => 'Ali Budiman',
            'age' => 25,
            'phone' => '0123456789',
            'house_id' => $house->id,
            'sport_ids' => [$sport->id],
        ]);

        $participant = Participant::first();

        $this->assertNotNull($participant);
        $this->assertStringStartsWith('SRKB-', $participant->registration_code);
        $this->assertDatabaseHas('sport_registrations', [
            'participant_id' => $participant->id,
            'sport_id' => $sport->id,
            'status' => 'Diterima',
        ]);
        $response->assertRedirect(route('public.success', $participant->registration_code));
    }

    public function test_child_registration_requires_guardian_details(): void
    {
        $house = House::create(['name' => 'Rumah Merah']);
        $sport = Sport::create(['name' => 'Pindah Cawan', 'category' => 'Kanak-Kanak', 'is_active' => true]);

        $response = $this->register([
            'name' => 'Amin Budiman',
            'age' => 8,
            'phone' => '0133456789',
            'house_id' => $house->id,
            'sport_ids' => [$sport->id],
        ]);

        $response->assertSessionHasErrors(['guardian_name', 'guardian_phone', 'guardian_relationship']);
    }

    public function test_existing_participant_can_add_a_new_event(): void
    {
        $house = House::create(['name' => 'Rumah Biru']);
        $existingSport = Sport::create(['name' => 'Catch the Scammer', 'category' => 'Terbuka', 'is_active' => true]);
        $newSport = Sport::create(['name' => 'Radio Rosak', 'category' => 'Terbuka', 'is_active' => true]);

        $participant = Participant::create([
            'registration_code' => 'SRKB-260602-ABCDE',
            'name' => 'Ali Budiman',
            'age' => 20,
            'phone' => '0123456789',
            'category' => 'Dewasa',
            'house_id' => $house->id,
            'status' => 'Aktif',
        ]);
        SportRegistration::create(['participant_id' => $participant->id, 'sport_id' => $existingSport->id, 'status' => 'Diterima']);

        $this->register([
            'name' => 'Ali Budiman',
            'age' => 20,
            'phone' => '+6012-3456789',
            'house_id' => $house->id,
            'sport_ids' => [$newSport->id],
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, Participant::count());
        $this->assertDatabaseHas('sport_registrations', [
            'participant_id' => $participant->id,
            'sport_id' => $newSport->id,
        ]);
    }

    public function test_duplicate_existing_event_registration_is_blocked(): void
    {
        $house = House::create(['name' => 'Rumah Biru']);
        $sport = Sport::create(['name' => 'Radio Rosak', 'category' => 'Terbuka', 'is_active' => true]);
        $participant = Participant::create([
            'registration_code' => 'SRKB-260602-ABCDE',
            'name' => 'Ali Budiman',
            'age' => 20,
            'phone' => '0123456789',
            'category' => 'Dewasa',
            'house_id' => $house->id,
            'status' => 'Aktif',
        ]);
        SportRegistration::create(['participant_id' => $participant->id, 'sport_id' => $sport->id, 'status' => 'Diterima']);

        $this->register([
            'name' => 'Ali Budiman',
            'age' => 20,
            'phone' => '+6012-3456789',
            'house_id' => $house->id,
            'sport_ids' => [$sport->id],
        ])->assertSessionHasErrors('sport_ids');
    }

    public function test_inactive_event_cannot_be_selected_publicly(): void
    {
        $house = House::create(['name' => 'Rumah Kuning']);
        $sport = Sport::create(['name' => 'Acara Tutup', 'category' => 'Terbuka', 'is_active' => false]);

        $this->register([
            'name' => 'Siti Budiman',
            'age' => 18,
            'phone' => '0143456789',
            'house_id' => $house->id,
            'sport_ids' => [$sport->id],
        ])->assertSessionHasErrors('sport_ids.0');
    }

    public function test_incompatible_event_category_is_rejected(): void
    {
        $house = House::create(['name' => 'Rumah Hijau']);
        $sport = Sport::create(['name' => 'Pindah Cawan', 'category' => 'Kanak-Kanak', 'is_active' => true]);

        $this->register([
            'name' => 'Dewasa Budiman',
            'age' => 22,
            'phone' => '0163456789',
            'house_id' => $house->id,
            'sport_ids' => [$sport->id],
        ])->assertSessionHasErrors('sport_ids.0');
    }

    public function test_child_age_cannot_register_for_adult_event(): void
    {
        $house = House::create(['name' => 'Rumah Hijau']);
        $sport = Sport::create(['name' => 'Balloon Rush', 'category' => 'Dewasa', 'is_active' => true]);

        $this->register([
            'name' => 'Kanak Budiman',
            'age' => 11,
            'phone' => '0169999999',
            'house_id' => $house->id,
            'sport_ids' => [$sport->id],
            'guardian_name' => 'Penjaga Kanak',
            'guardian_phone' => '0129999999',
            'guardian_relationship' => 'Ibu',
        ])->assertSessionHasErrors('sport_ids.0');
    }

    public function test_age_11_creates_child_category_even_if_payload_is_tampered(): void
    {
        $house = House::create(['name' => 'Rumah Biru']);
        $sport = Sport::create(['name' => 'Radio Rosak', 'category' => 'Terbuka', 'is_active' => true]);

        $this->register([
            'name' => 'Anak Budiman',
            'age' => 11,
            'phone' => '0153456789',
            'category' => 'Dewasa',
            'house_id' => $house->id,
            'sport_ids' => [$sport->id],
            'guardian_name' => 'Ibu Budiman',
            'guardian_phone' => '0128888888',
            'guardian_relationship' => 'Ibu',
        ]);

        $this->assertDatabaseHas('participants', [
            'name' => 'Anak Budiman',
            'category' => 'Kanak-Kanak',
        ]);
    }

    public function test_age_12_creates_child_category_even_if_payload_is_tampered(): void
    {
        $house = House::create(['name' => 'Rumah Kuning']);
        $sport = Sport::create(['name' => 'Catch the Scammer', 'category' => 'Terbuka', 'is_active' => true]);

        $this->register([
            'name' => 'Kanak Dua Belas',
            'age' => 12,
            'phone' => '0193456789',
            'category' => 'Dewasa',
            'house_id' => $house->id,
            'sport_ids' => [$sport->id],
            'guardian_name' => 'Penjaga Dua Belas',
            'guardian_phone' => '0124567890',
            'guardian_relationship' => 'Ibu',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('participants', [
            'name' => 'Kanak Dua Belas',
            'category' => 'Kanak-Kanak',
        ]);
    }

    public function test_teenager_age_boundaries_create_teenager_category_without_guardian(): void
    {
        $house = House::create(['name' => 'Rumah Kuning']);
        $sport = Sport::create(['name' => 'Acara Terbuka Remaja', 'category' => 'Terbuka', 'is_active' => true]);

        foreach ([13, 17] as $age) {
            $this->register([
                'name' => "Remaja {$age}",
                'age' => $age,
                'phone' => "01934567{$age}",
                'category' => 'Dewasa',
                'house_id' => $house->id,
                'sport_ids' => [$sport->id],
            ])->assertSessionHasNoErrors();

            $this->assertDatabaseHas('participants', [
                'name' => "Remaja {$age}",
                'category' => 'Remaja',
                'guardian_id' => null,
            ]);
        }
    }

    public function test_age_18_creates_adult_category_without_guardian(): void
    {
        $house = House::create(['name' => 'Rumah Kuning']);
        $sport = Sport::create(['name' => 'Acara Dewasa Terbuka', 'category' => 'Terbuka', 'is_active' => true]);

        $this->register([
            'name' => 'Dewasa Lapan Belas',
            'age' => 18,
            'phone' => '0193456718',
            'category' => 'Kanak-Kanak',
            'house_id' => $house->id,
            'sport_ids' => [$sport->id],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('participants', [
            'name' => 'Dewasa Lapan Belas',
            'category' => 'Dewasa',
            'guardian_id' => null,
        ]);
    }

    public function test_open_event_accepts_child_teenager_and_adult_categories(): void
    {
        $house = House::create(['name' => 'Rumah Merah']);
        $sport = Sport::create(['name' => 'Radio Rosak', 'category' => 'Terbuka', 'max_players_per_group' => 10, 'is_active' => true]);

        $this->register([
            'name' => 'Anak Terbuka',
            'age' => 9,
            'phone' => '0111111111',
            'house_id' => $house->id,
            'sport_ids' => [$sport->id],
            'guardian_name' => 'Penjaga Terbuka',
            'guardian_phone' => '0127777777',
            'guardian_relationship' => 'Bapa',
        ])->assertSessionHasNoErrors();

        $this->register([
            'name' => 'Remaja Terbuka',
            'age' => 15,
            'phone' => '0113333333',
            'house_id' => $house->id,
            'sport_ids' => [$sport->id],
        ])->assertSessionHasNoErrors();

        $this->register([
            'name' => 'Dewasa Terbuka',
            'age' => 30,
            'phone' => '0112222222',
            'house_id' => $house->id,
            'sport_ids' => [$sport->id],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('participants', ['name' => 'Anak Terbuka', 'category' => 'Kanak-Kanak']);
        $this->assertDatabaseHas('participants', ['name' => 'Remaja Terbuka', 'category' => 'Remaja']);
        $this->assertDatabaseHas('participants', ['name' => 'Dewasa Terbuka', 'category' => 'Dewasa']);
    }

    public function test_teenager_can_register_for_teenager_event(): void
    {
        $house = House::create(['name' => 'Rumah Hijau']);
        $sport = Sport::create(['name' => 'Acara Remaja', 'category' => 'Remaja', 'is_active' => true]);

        $this->register([
            'name' => 'Remaja Sukan',
            'age' => 15,
            'phone' => '0165555555',
            'house_id' => $house->id,
            'sport_ids' => [$sport->id],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('participants', [
            'name' => 'Remaja Sukan',
            'category' => 'Remaja',
        ]);
    }

    public function test_teenager_cannot_register_for_child_or_adult_event(): void
    {
        $house = House::create(['name' => 'Rumah Hijau']);
        $childSport = Sport::create(['name' => 'Acara Kanak Sahaja', 'category' => 'Kanak-Kanak', 'is_active' => true]);
        $adultSport = Sport::create(['name' => 'Acara Dewasa Sahaja', 'category' => 'Dewasa', 'is_active' => true]);

        $this->register([
            'name' => 'Remaja Cuba Kanak',
            'age' => 15,
            'phone' => '0166666666',
            'house_id' => $house->id,
            'sport_ids' => [$childSport->id],
        ])->assertSessionHasErrors('sport_ids.0');

        $this->register([
            'name' => 'Remaja Cuba Dewasa',
            'age' => 15,
            'phone' => '0167777777',
            'house_id' => $house->id,
            'sport_ids' => [$adultSport->id],
        ])->assertSessionHasErrors('sport_ids.0');
    }

    public function test_full_event_places_public_registration_on_waiting_list(): void
    {
        $house = House::create(['name' => 'Rumah Merah']);
        $sport = Sport::create(['name' => 'Tarik Tali Final', 'category' => 'Terbuka', 'max_players_per_group' => 1, 'is_active' => true]);
        $accepted = Participant::create([
            'registration_code' => 'SRKB-260602-FIRST',
            'name' => 'Peserta Pertama',
            'age' => 20,
            'phone' => '0173456789',
            'category' => 'Terbuka',
            'house_id' => $house->id,
            'status' => 'Aktif',
        ]);
        SportRegistration::create(['participant_id' => $accepted->id, 'sport_id' => $sport->id, 'status' => 'Diterima']);

        $this->register([
            'name' => 'Peserta Kedua',
            'age' => 21,
            'phone' => '0183456789',
            'house_id' => $house->id,
            'sport_ids' => [$sport->id],
        ]);

        $this->assertDatabaseHas('sport_registrations', [
            'sport_id' => $sport->id,
            'status' => 'Senarai Menunggu',
        ]);
    }

    public function test_public_registration_can_include_multiple_events(): void
    {
        $house = House::create(['name' => 'Rumah Hijau']);
        $firstSport = Sport::create(['name' => 'Catch the Scammer', 'category' => 'Terbuka', 'is_active' => true]);
        $secondSport = Sport::create(['name' => 'Radio Rosak', 'category' => 'Terbuka', 'is_active' => true]);

        $this->register([
            'name' => 'Multi Budiman',
            'age' => 24,
            'phone' => '0121231234',
            'house_id' => $house->id,
            'sport_ids' => [$firstSport->id, $secondSport->id],
        ])->assertSessionHasNoErrors();

        $participant = Participant::where('name', 'Multi Budiman')->first();

        $this->assertCount(2, $participant->sportRegistrations);
    }

    public function test_child_can_register_without_own_phone_when_guardian_phone_is_present(): void
    {
        $house = House::create(['name' => 'Rumah Merah']);
        $sport = Sport::create(['name' => 'Pindah Cawan', 'category' => 'Kanak-Kanak', 'is_active' => true]);

        $this->register([
            'name' => 'Anak Tanpa Telefon',
            'age' => 9,
            'house_id' => $house->id,
            'sport_ids' => [$sport->id],
            'guardian_name' => 'Penjaga Telefon',
            'guardian_phone' => '0127000000',
            'guardian_relationship' => 'Ibu',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('participants', [
            'name' => 'Anak Tanpa Telefon',
            'phone' => null,
            'category' => 'Kanak-Kanak',
        ]);
    }

    public function test_registration_form_shows_captcha_and_consent_agreement(): void
    {
        $this->get('/daftar')
            ->assertOk()
            ->assertSee('Captcha')
            ->assertSee('Saya mengesahkan maklumat yang diberikan adalah benar');
    }

    public function test_registration_requires_captcha_answer(): void
    {
        $house = House::create(['name' => 'Rumah Hijau']);
        $sport = Sport::create(['name' => 'Balloon Rush', 'category' => 'Dewasa', 'is_active' => true]);

        $this->withSession(['registration_captcha_answer' => 7])
            ->post('/daftar', $this->adultPayload($house, $sport) + [
                'consent_agreement' => '1',
            ])
            ->assertSessionHasErrors('captcha_answer');
    }

    public function test_registration_rejects_wrong_captcha_answer(): void
    {
        $house = House::create(['name' => 'Rumah Hijau']);
        $sport = Sport::create(['name' => 'Balloon Rush', 'category' => 'Dewasa', 'is_active' => true]);

        $this->withSession(['registration_captcha_answer' => 7])
            ->post('/daftar', $this->adultPayload($house, $sport) + [
                'captcha_answer' => 8,
                'consent_agreement' => '1',
            ])
            ->assertSessionHasErrors('captcha_answer');
    }

    public function test_registration_requires_consent_agreement(): void
    {
        $house = House::create(['name' => 'Rumah Hijau']);
        $sport = Sport::create(['name' => 'Balloon Rush', 'category' => 'Dewasa', 'is_active' => true]);

        $this->withSession(['registration_captcha_answer' => 7])
            ->post('/daftar', $this->adultPayload($house, $sport) + [
                'captcha_answer' => 7,
            ])
            ->assertSessionHasErrors('consent_agreement');
    }

    public function test_registration_is_blocked_when_settings_close_it(): void
    {
        Setting::setValue('registration_is_open', '0');

        $this->get('/daftar')
            ->assertOk()
            ->assertSee('Pendaftaran Ditutup');
    }

    public function test_public_pages_do_not_show_admin_login_link(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('Admin')
            ->assertDontSee('/login');
    }
}

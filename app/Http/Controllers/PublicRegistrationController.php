<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreParticipantRequest;
use App\Models\Guardian;
use App\Models\House;
use App\Models\Participant;
use App\Models\Setting;
use App\Models\Sport;
use App\Models\SportRegistration;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PublicRegistrationController extends Controller
{
    public function landing()
    {
        return view('public.landing', [
            'settings' => Setting::allAsArray(),
            'registrationIsOpen' => Setting::registrationIsOpen(),
            'houses' => House::orderBy('name')->get(),
            'sports' => Sport::where('is_active', true)
                ->withCount(['acceptedRegistrations', 'waitingListRegistrations'])
                ->orderBy('category')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create()
    {
        if (! Setting::registrationIsOpen()) {
            return view('public.closed', ['settings' => Setting::allAsArray()]);
        }

        $captcha = $this->generateCaptcha();

        return view('public.register', [
            'houses' => House::orderBy('name')->get(),
            'sports' => Sport::where('is_active', true)->withCount([
                'acceptedRegistrations',
                'waitingListRegistrations',
            ])->orderBy('category')->orderBy('name')->get(),
            'childAgeThreshold' => Participant::CHILD_AGE_THRESHOLD,
            'adultAgeThreshold' => Participant::ADULT_AGE_THRESHOLD,
            'captchaQuestion' => $captcha['question'],
        ]);
    }

    public function store(StoreParticipantRequest $request)
    {
        if (! Setting::registrationIsOpen()) {
            return response()->view('public.closed', ['settings' => Setting::allAsArray()], 403);
        }

        $data = $request->validated();
        try {
            $participant = DB::transaction(function () use ($data) {
                $category = Participant::categoryForAge((int) $data['age']);
                $participant = $this->findExistingParticipant($data, $category);

                if ($participant && $participant->category !== $category) {
                    throw ValidationException::withMessages([
                        'age' => 'Kategori umur tidak sepadan dengan rekod sedia ada. Sila hubungi urusetia untuk pembetulan.',
                    ]);
                }

                if (! $participant) {
                    $guardian = null;

                    if ($category === Participant::CATEGORY_CHILD) {
                        $guardian = Guardian::create([
                            'name' => $data['guardian_name'],
                            'phone' => $data['guardian_phone'],
                            'relationship' => $data['guardian_relationship'],
                        ]);
                    }

                    $participant = Participant::create([
                        'registration_code' => $this->generateRegistrationCode(),
                        'name' => $data['name'],
                        'age' => $data['age'],
                        'phone' => $data['phone'] ?? null,
                        'category' => $category,
                        'house_id' => $data['house_id'],
                        'guardian_id' => $guardian?->id,
                        'status' => 'Aktif',
                    ]);
                }

                $existingSportIds = $participant->sportRegistrations()->pluck('sport_id')->all();
                $newSportIds = collect($data['sport_ids'])->map(fn ($id) => (int) $id)->diff($existingSportIds)->values();

                if ($newSportIds->isEmpty()) {
                    throw ValidationException::withMessages([
                        'sport_ids' => 'Peserta ini telah didaftarkan untuk semua acara yang dipilih.',
                    ]);
                }

                foreach ($newSportIds as $sportId) {
                    $sport = Sport::lockForUpdate()->findOrFail($sportId);
                    SportRegistration::create([
                        'participant_id' => $participant->id,
                        'sport_id' => $sport->id,
                        'status' => $sport->hasCapacity() ? 'Diterima' : 'Senarai Menunggu',
                    ]);
                }

                return $participant;
            });
        } finally {
            $request->session()->forget('registration_captcha_answer');
        }

        return redirect()->route('public.success', $participant->registration_code);
    }

    public function success(string $registrationCode)
    {
        $participant = Participant::with(['house', 'sportRegistrations.sport'])->where('registration_code', $registrationCode)->firstOrFail();

        return view('public.success', compact('participant'));
    }

    public function check()
    {
        return view('public.check');
    }

    public function lookup(Request $request)
    {
        $request->validate([
            'search' => ['required', 'string', 'max:255'],
        ], ['search.required' => 'Sila masukkan kod pendaftaran atau nombor telefon.']);

        $search = $this->normalizeSearch($request->input('search'));

        $participants = Participant::with(['house', 'guardian', 'sportRegistrations.sport'])
            ->where(function ($query) use ($search) {
                $query->where('registration_code', $search)
                    ->orWhere('phone', $search)
                    ->orWhereHas('guardian', fn ($query) => $query->where('phone', $search));
            })
            ->latest()
            ->get();

        return view('public.status', compact('participants', 'search'));
    }

    public function status(string $registrationCode)
    {
        $participants = Participant::with(['house', 'guardian', 'sportRegistrations.sport'])
            ->where('registration_code', $registrationCode)
            ->get();

        abort_if($participants->isEmpty(), 404);

        return view('public.status', ['participants' => $participants, 'search' => $registrationCode]);
    }

    private function generateRegistrationCode(): string
    {
        do {
            $code = 'SRKB-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
        } while (Participant::where('registration_code', $code)->exists());

        return $code;
    }

    private function findExistingParticipant(array $data, string $category): ?Participant
    {
        $query = Participant::with('guardian')->where('name', $data['name']);

        if (! empty($data['phone'])) {
            return $query->where('phone', $data['phone'])->first();
        }

        if ($category === Participant::CATEGORY_CHILD && ! empty($data['guardian_phone'])) {
            return $query
                ->whereHas('guardian', fn ($query) => $query->where('phone', $data['guardian_phone']))
                ->first();
        }

        return null;
    }

    private function generateCaptcha(): array
    {
        $first = random_int(2, 9);
        $second = random_int(1, 9);
        session(['registration_captcha_answer' => $first + $second]);

        return ['question' => "{$first} + {$second} = ?"];
    }

    private function normalizeSearch(string $search): string
    {
        $search = trim($search);

        if (str_starts_with(strtoupper($search), 'SRKB-')) {
            return strtoupper($search);
        }

        return PhoneNumber::normalize($search) ?: $search;
    }
}

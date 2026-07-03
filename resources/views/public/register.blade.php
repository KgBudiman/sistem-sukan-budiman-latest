<x-public-layout title="Daftar Peserta">
    <section class="overflow-hidden">
        <div class="bg-budiman-footer py-10">
            <div class="kb-container">
                <div class="max-w-3xl text-white">
                    <p class="text-sm font-bold uppercase tracking-[0.16em] text-white/70">Pendaftaran Peserta</p>
                    <h1 class="mt-2 text-4xl font-extrabold">Daftar Peserta</h1>
                    <p class="mt-3 text-sm leading-6 text-white/80">Sila pastikan maklumat tepat sebelum dihantar. Peserta tidak boleh mengedit maklumat selepas pendaftaran.</p>
                </div>
            </div>
        </div>

        <div class="kb-container py-10">
            <form method="POST" action="{{ route('public.register.store') }}" class="kb-card mx-auto max-w-3xl space-y-6 p-5 sm:p-6" x-data="{ age: '{{ old('age') }}', category() { const participantAge = Number(this.age); if (participantAge <= 0) return 'Ditentukan melalui umur'; if (participantAge < {{ $childAgeThreshold }}) return 'Kanak-Kanak'; if (participantAge < {{ $adultAgeThreshold }}) return 'Remaja'; return 'Dewasa' }, compatible(sportCategory) { return this.category() === 'Ditentukan melalui umur' || sportCategory === 'Terbuka' || sportCategory === this.category() } }">
                @csrf

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="kb-label" for="name">Nama peserta</label>
                        <input class="kb-input" id="name" name="name" value="{{ old('name') }}" required>
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="kb-label" for="age">Umur</label>
                        <input class="kb-input" id="age" name="age" type="number" min="1" max="120" x-model="age" value="{{ old('age') }}" required>
                        @error('age') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="kb-label" for="phone">Nombor telefon</label>
                        <input class="kb-input" id="phone" name="phone" value="{{ old('phone') }}" placeholder="Contoh: 0123456789" x-bind:required="category() !== 'Kanak-Kanak'">
                        <p class="mt-1 text-xs text-stone-500">Wajib untuk peserta remaja/dewasa. Untuk kanak-kanak, nombor penjaga boleh digunakan.</p>
                        @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="kb-label">Kategori peserta</label>
                        <div class="rounded-xl border border-budiman-primary/10 bg-budiman-cream px-3 py-2 text-sm font-semibold text-budiman-primary" x-text="category()"></div>
                        <p class="mt-1 text-xs text-stone-500">Kategori ditentukan secara automatik berdasarkan umur.</p>
                    </div>
                    <div>
                        <label class="kb-label" for="house_id">Rumah sukan</label>
                        <select class="kb-input" id="house_id" name="house_id" required>
                            <option value="">Pilih rumah sukan</option>
                            @foreach ($houses as $house)
                                <option value="{{ $house->id }}" @selected(old('house_id') == $house->id)>{{ $house->name }}</option>
                            @endforeach
                        </select>
                        @error('house_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="kb-label">Acara pilihan</label>
                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach ($sports as $sport)
                                <label class="rounded-xl border border-stone-200 bg-white p-3 text-sm shadow-sm transition hover:border-budiman-primary hover:shadow-md" x-show="compatible('{{ $sport->category }}')" x-bind:class="compatible('{{ $sport->category }}') ? '' : 'opacity-50'">
                                    <div class="flex items-start gap-3">
                                        <input class="mt-1 rounded border-stone-300 text-budiman-primary focus:ring-budiman-primary" type="checkbox" name="sport_ids[]" value="{{ $sport->id }}" @checked(in_array($sport->id, old('sport_ids', []))) x-bind:disabled="! compatible('{{ $sport->category }}')">
                                        <span>
                                            <span class="block font-semibold text-stone-900">{{ $sport->name }}</span>
                                            <span class="mt-1 block text-xs text-stone-500">{{ $sport->category }} - {{ $sport->availabilityLabel() }}</span>
                                        </span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        <p class="mt-2 text-xs text-stone-500">Acara Kanak-Kanak/Remaja/Dewasa akan dipadankan mengikut umur. Acara Terbuka boleh disertai semua peserta. Jika acara penuh, pendaftaran anda akan dimasukkan ke Senarai Menunggu.</p>
                        @error('sport_ids') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        @error('sport_ids.*') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="rounded-xl border border-budiman-primary/20 bg-budiman-cream p-4" x-show="Number(age) > 0 && Number(age) < {{ $childAgeThreshold }}">
                    <h2 class="font-semibold text-budiman-primary">Maklumat Penjaga</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="kb-label" for="guardian_name">Nama penjaga</label>
                            <input class="kb-input" id="guardian_name" name="guardian_name" value="{{ old('guardian_name') }}">
                            @error('guardian_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="kb-label" for="guardian_phone">Nombor telefon penjaga</label>
                            <input class="kb-input" id="guardian_phone" name="guardian_phone" value="{{ old('guardian_phone') }}">
                            @error('guardian_phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="kb-label" for="guardian_relationship">Hubungan</label>
                            <select class="kb-input" id="guardian_relationship" name="guardian_relationship">
                                <option value="">Pilih hubungan</option>
                                @foreach (['Ibu', 'Bapa', 'Penjaga', 'Lain-lain'] as $relationship)
                                    <option value="{{ $relationship }}" @selected(old('guardian_relationship') === $relationship)>{{ $relationship }}</option>
                                @endforeach
                            </select>
                            @error('guardian_relationship') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 border-t border-stone-200 pt-5 sm:grid-cols-2">
                    <div>
                        <label class="kb-label" for="captcha_answer">Captcha</label>
                        <div class="rounded-xl border border-budiman-primary/10 bg-budiman-cream px-3 py-2 text-sm font-semibold text-budiman-primary">{{ $captchaQuestion }}</div>
                        <input class="kb-input mt-2" id="captcha_answer" name="captcha_answer" type="number" inputmode="numeric" required>
                        @error('captcha_answer') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="rounded-xl border border-stone-200 bg-white p-4 text-sm text-stone-700">
                        <label class="flex items-start gap-3">
                            <input class="mt-1 rounded border-stone-300 text-budiman-primary focus:ring-budiman-primary" type="checkbox" name="consent_agreement" value="1" required @checked(old('consent_agreement'))>
                            <span>Saya mengesahkan maklumat yang diberikan adalah benar dan bersetuju maklumat ini digunakan oleh pihak penganjur untuk tujuan pendaftaran, pengurusan acara, keselamatan peserta, laporan dan komunikasi berkaitan Sukan Rakyat Kampung Budiman. Bagi peserta bawah 18 tahun, saya mengesahkan persetujuan ibu/bapa/penjaga telah diberikan.</span>
                        </label>
                        @error('consent_agreement') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex flex-col gap-3 border-t border-stone-200 pt-5 sm:flex-row sm:justify-end">
                    <a href="{{ route('public.landing') }}" class="kb-btn-secondary">Kembali</a>
                    <button class="kb-btn-primary" type="submit">Hantar Pendaftaran</button>
                </div>
            </form>
        </div>
    </section>
</x-public-layout>

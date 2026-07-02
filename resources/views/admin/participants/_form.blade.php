@php
    $isEdit = $participant->exists;
    $oldSportIds = collect(old('sport_ids', $selectedRegistrations->keys()->all()))->map(fn ($id) => (int) $id)->all();
    $oldSportStatuses = old('sport_statuses', $selectedRegistrations->mapWithKeys(fn ($registration, $sportId) => [$sportId => $registration->status])->all());
@endphp

@csrf
@if ($isEdit)
    @method('PUT')
@endif

<div class="grid gap-4 sm:grid-cols-2" x-data="{ age: '{{ old('age', $participant->age) }}', category() { const participantAge = Number(this.age); if (participantAge <= 0) return 'Ditentukan melalui umur'; if (participantAge < {{ \App\Models\Participant::CHILD_AGE_THRESHOLD }}) return 'Kanak-Kanak'; if (participantAge < {{ \App\Models\Participant::ADULT_AGE_THRESHOLD }}) return 'Remaja'; return 'Dewasa' }, compatible(sportCategory) { return this.category() === 'Ditentukan melalui umur' || sportCategory === 'Terbuka' || sportCategory === this.category() } }">
    <div class="sm:col-span-2">
        <label class="kb-label" for="name">Nama peserta</label>
        <input class="kb-input" id="name" name="name" value="{{ old('name', $participant->name) }}" required>
        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="kb-label" for="age">Umur</label>
        <input class="kb-input" id="age" name="age" type="number" min="1" max="120" x-model="age" value="{{ old('age', $participant->age) }}" required>
        @error('age') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="kb-label" for="phone">Nombor telefon</label>
        <input class="kb-input" id="phone" name="phone" value="{{ old('phone', $participant->phone) }}" x-bind:required="category() !== 'Kanak-Kanak'">
        <p class="mt-1 text-xs text-stone-500">Wajib untuk peserta remaja/dewasa. Untuk kanak-kanak, nombor penjaga boleh digunakan.</p>
        @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="kb-label">Kategori</label>
        <div class="rounded-lg border border-stone-200 bg-stone-100 px-3 py-2 text-sm font-semibold text-budiman-primary" x-text="category()"></div>
        <p class="mt-1 text-xs text-stone-500">Kategori dikira automatik daripada umur.</p>
    </div>
    <div>
        <label class="kb-label" for="house_id">Rumah sukan</label>
        <select class="kb-input" id="house_id" name="house_id" required>
            <option value="">Pilih rumah</option>
            @foreach ($houses as $house)
                <option value="{{ $house->id }}" @selected(old('house_id', $participant->house_id) == $house->id)>{{ $house->name }}</option>
            @endforeach
        </select>
        @error('house_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="kb-label" for="status">Status peserta</label>
        <select class="kb-input" id="status" name="status" required>
            @foreach (['Aktif', 'Dibatalkan'] as $status)
                <option value="{{ $status }}" @selected(old('status', $participant->status ?: 'Aktif') === $status)>{{ $status }}</option>
            @endforeach
        </select>
    </div>
    <div class="sm:col-span-2">
        <label class="kb-label">Acara sukan</label>
        <div class="space-y-3">
            @foreach ($sports as $sport)
                @php($isSelected = in_array($sport->id, $oldSportIds, true))
                <div class="rounded-lg border border-stone-200 bg-white p-3" x-show="compatible('{{ $sport->category }}')" x-bind:class="compatible('{{ $sport->category }}') ? '' : 'opacity-50'">
                    <div class="grid gap-3 md:grid-cols-[1fr_220px] md:items-center">
                        <label class="flex items-start gap-3 text-sm">
                            <input class="mt-1 rounded border-stone-300 text-budiman-primary focus:ring-budiman-primary" type="checkbox" name="sport_ids[]" value="{{ $sport->id }}" @checked($isSelected) x-bind:disabled="! compatible('{{ $sport->category }}')">
                            <span>
                                <span class="block font-semibold text-stone-900">{{ $sport->name }}</span>
                                <span class="mt-1 block text-xs text-stone-500">{{ $sport->category }} - {{ $sport->is_active ? $sport->availabilityLabel() : 'Tidak Aktif' }}</span>
                            </span>
                        </label>
                        <select class="kb-input" name="sport_statuses[{{ $sport->id }}]">
                            @foreach (['Menunggu', 'Diterima', 'Ditolak', 'Senarai Menunggu', 'Dibatalkan'] as $status)
                                <option value="{{ $status }}" @selected(($oldSportStatuses[$sport->id] ?? 'Menunggu') === $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    @error("sport_statuses.$sport->id") <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            @endforeach
        </div>
        <p class="mt-1 text-xs text-stone-500">Pilihan acara dikawal oleh kategori umur. Acara Terbuka dibenarkan untuk semua peserta.</p>
        @error('sport_ids') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        @error('sport_ids.*') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2 rounded-xl border border-budiman-primary/20 bg-budiman-cream p-4" x-show="Number(age) > 0 && Number(age) < {{ \App\Models\Participant::CHILD_AGE_THRESHOLD }}">
        <h2 class="font-semibold text-budiman-primary">Maklumat Penjaga</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="kb-label" for="guardian_name">Nama penjaga</label>
                <input class="kb-input" id="guardian_name" name="guardian_name" value="{{ old('guardian_name', $participant->guardian?->name) }}">
                @error('guardian_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="kb-label" for="guardian_phone">Telefon penjaga</label>
                <input class="kb-input" id="guardian_phone" name="guardian_phone" value="{{ old('guardian_phone', $participant->guardian?->phone) }}">
                @error('guardian_phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="kb-label" for="guardian_relationship">Hubungan</label>
                <input class="kb-input" id="guardian_relationship" name="guardian_relationship" value="{{ old('guardian_relationship', $participant->guardian?->relationship) }}">
                @error('guardian_relationship') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <div class="sm:col-span-2">
        <label class="kb-label" for="notes">Catatan</label>
        <textarea class="kb-input" id="notes" name="notes" rows="3">{{ old('notes', $participant->notes) }}</textarea>
    </div>
</div>

<div class="mt-6 flex justify-end gap-3 border-t border-stone-200 pt-5">
    <a href="{{ route('admin.participants.index') }}" class="kb-btn-secondary">Batal</a>
    <button class="kb-btn-primary" type="submit">Simpan</button>
</div>

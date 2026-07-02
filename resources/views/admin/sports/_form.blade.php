@csrf
@if ($sport->exists)
    @method('PUT')
@endif

<div class="grid gap-4 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label class="kb-label" for="name">Nama acara</label>
        <input class="kb-input" id="name" name="name" value="{{ old('name', $sport->name) }}" required>
        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="kb-label" for="category">Kategori</label>
        <select class="kb-input" id="category" name="category" required>
            @foreach (\App\Models\Participant::SPORT_CATEGORIES as $category)
                <option value="{{ $category }}" @selected(old('category', $sport->category) === $category)>{{ $category }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="kb-label" for="group_code">Kod kumpulan</label>
        <input class="kb-input" id="group_code" name="group_code" value="{{ old('group_code', $sport->group_code) }}">
    </div>
    <div>
        <label class="kb-label" for="max_players_per_group">Pemain / kumpulan</label>
        <input class="kb-input" id="max_players_per_group" name="max_players_per_group" type="number" min="1" value="{{ old('max_players_per_group', $sport->max_players_per_group) }}">
    </div>
    <div>
        <label class="kb-label" for="duration_minutes">Tempoh minit</label>
        <input class="kb-input" id="duration_minutes" name="duration_minutes" type="number" min="1" value="{{ old('duration_minutes', $sport->duration_minutes) }}">
    </div>
    <div class="sm:col-span-2">
        <label class="kb-label" for="description">Penerangan</label>
        <textarea class="kb-input" id="description" name="description" rows="3">{{ old('description', $sport->description) }}</textarea>
    </div>
    <label class="flex items-center gap-2 text-sm font-semibold text-stone-700">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $sport->is_active ?? true))>
        Aktif
    </label>
</div>
<div class="mt-6 flex justify-end gap-3 border-t border-stone-200 pt-5">
    <a href="{{ route('admin.sports.index') }}" class="kb-btn-secondary">Batal</a>
    <button class="kb-btn-primary" type="submit">Simpan</button>
</div>

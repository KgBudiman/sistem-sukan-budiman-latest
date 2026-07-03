<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h1 class="text-2xl font-bold text-budiman-secondary">Pengurusan Peserta</h1>
            <a href="{{ route('admin.participants.create') }}" class="kb-btn-primary">Tambah Peserta</a>
        </div>
    </x-slot>

    <div class="kb-container py-6">
        @include('admin.shared.flash')
        <form class="kb-card grid gap-3 p-4 md:grid-cols-5">
            <input class="kb-input md:col-span-2" name="search" value="{{ request('search') }}" placeholder="Cari nama, telefon atau kod">
            <select class="kb-input" name="house_id">
                <option value="">Semua rumah</option>
                @foreach ($houses as $house)
                    <option value="{{ $house->id }}" @selected(request('house_id') == $house->id)>{{ $house->name }}</option>
                @endforeach
            </select>
            <select class="kb-input" name="category">
                <option value="">Semua kategori</option>
                @foreach (\App\Models\Participant::PARTICIPANT_CATEGORIES as $category)
                    <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                @endforeach
            </select>
            <button class="kb-btn-primary" type="submit">Tapis</button>
        </form>

        <div class="kb-card mt-5 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-stone-100 text-stone-700">
                        <tr><th class="px-4 py-3">Kod</th><th class="px-4 py-3">Nama</th><th class="px-4 py-3">Telefon</th><th class="px-4 py-3">Kategori</th><th class="px-4 py-3">Rumah</th><th class="px-4 py-3">Status</th><th class="px-4 py-3"></th></tr>
                    </thead>
                    <tbody>
                        @forelse ($participants as $participant)
                            <tr class="border-t border-stone-100">
                                <td class="px-4 py-3">{{ $participant->registration_code }}</td>
                                <td class="px-4 py-3 font-semibold">{{ $participant->name }}</td>
                                <td class="px-4 py-3">{{ $participant->phone ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $participant->category }}</td>
                                <td class="px-4 py-3">{{ $participant->house?->name }}</td>
                                <td class="px-4 py-3">{{ $participant->status }}</td>
                                <td class="px-4 py-3 text-right"><a class="font-semibold text-budiman-primary" href="{{ route('admin.participants.show', $participant) }}">Lihat</a></td>
                            </tr>
                        @empty
                            <tr><td class="px-4 py-6 text-center text-stone-500" colspan="7">Tiada rekod peserta.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-stone-200 p-4">{{ $participants->links() }}</div>
        </div>
    </div>
</x-app-layout>

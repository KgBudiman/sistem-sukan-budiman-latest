<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-budiman-secondary">Dashboard Admin</h1>
    </x-slot>

    <div class="kb-container py-6">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            @foreach ([['Jumlah Peserta', $totalParticipants], ['Kanak-Kanak', $childParticipants], ['Remaja', $teenagerParticipants], ['Dewasa', $adultParticipants], ['Aktif', $activeParticipants]] as [$label, $value])
                <div class="kb-card p-5">
                    <p class="text-sm font-semibold text-stone-500">{{ $label }}</p>
                    <p class="mt-2 text-3xl font-bold text-budiman-secondary">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <div class="kb-card p-5">
                <h2 class="font-bold text-budiman-secondary">Peserta Mengikut Rumah Sukan</h2>
                <div class="mt-4 space-y-3">
                    @foreach ($participantsByHouse as $house)
                        <div class="flex items-center justify-between border-b border-stone-100 pb-2">
                            <span>{{ $house->name }}</span>
                            <span class="font-semibold">{{ $house->participants_count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="kb-card p-5">
                <h2 class="font-bold text-budiman-secondary">Pendaftaran Mengikut Acara</h2>
                <div class="mt-4 space-y-3">
                    @foreach ($sports as $sport)
                        <div class="flex items-center justify-between border-b border-stone-100 pb-2">
                            <span>{{ $sport->name }}</span>
                            <span class="font-semibold">{{ $sport->registrations_count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="kb-card mt-6 overflow-hidden">
            <div class="flex items-center justify-between border-b border-stone-200 p-5">
                <h2 class="font-bold text-budiman-secondary">Pendaftaran Terkini</h2>
                <a class="text-sm font-semibold text-budiman-primary" href="{{ route('admin.participants.index') }}">Lihat semua</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-stone-100 text-stone-700">
                        <tr><th class="px-4 py-3">Kod</th><th class="px-4 py-3">Nama</th><th class="px-4 py-3">Rumah</th><th class="px-4 py-3">Status</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($latestParticipants as $participant)
                            <tr class="border-t border-stone-100">
                                <td class="px-4 py-3">{{ $participant->registration_code }}</td>
                                <td class="px-4 py-3">{{ $participant->name }}</td>
                                <td class="px-4 py-3">{{ $participant->house?->name }}</td>
                                <td class="px-4 py-3">{{ $participant->status }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>

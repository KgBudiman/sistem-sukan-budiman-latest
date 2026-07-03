<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\House;
use App\Models\Participant;
use App\Models\Sport;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return view('admin.dashboard', [
            'totalParticipants' => Participant::count(),
            'childParticipants' => Participant::where('category', Participant::CATEGORY_CHILD)->count(),
            'teenagerParticipants' => Participant::where('category', Participant::CATEGORY_TEENAGER)->count(),
            'adultParticipants' => Participant::where('category', Participant::CATEGORY_ADULT)->count(),
            'activeParticipants' => Participant::where('status', 'Aktif')->count(),
            'participantsByHouse' => House::withCount('participants')->orderBy('name')->get(),
            'sports' => Sport::withCount('registrations')->orderBy('name')->get(),
            'latestParticipants' => Participant::with('house')->latest()->take(8)->get(),
        ]);
    }
}

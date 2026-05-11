<?php

namespace App\Http\Controllers;

use App\Models\ApplicationOpening;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PublicPageController extends Controller
{
    public function home(): View|RedirectResponse
    {
        if (Auth::check()) {
            $user = Auth::user();

            if (in_array($user->role, ['admin', 'staff'], true)) {
                return redirect()->route('admin.dashboard');
            }

            if ($user->role === 'broker') {
                return redirect()->route('broker.dashboard');
            }

            return redirect()->route('applications.index');
        }

        return view('welcome');
    }

    public function about(): View
    {
        return view('public.about');
    }

    public function services(): View
    {
        return view('public.services', [
            'services' => $this->servicesList(),
        ]);
    }

    public function stalls(): View
    {
        $openings = ApplicationOpening::with(['stall', 'requirementTypes'])
            ->availableForApplication()
            ->orderBy('start_date')
            ->get();

        return view('public.stalls', compact('openings'));
    }

    private function servicesList(): array
    {
        return [
            [
                'title' => 'Stall Application Management',
                'description' => 'Publish vacant stalls, organize requirements, and guide applicants through the secured application process.',
                'icon' => 'building',
            ],
            [
                'title' => 'Broker Transactions',
                'description' => 'Support broker sales activity with structured transaction records and clear operational tracking.',
                'icon' => 'receipt',
            ],
            [
                'title' => 'Fish Box Tracking',
                'description' => 'Monitor fish box movement, availability, and return status for better facility accountability.',
                'icon' => 'box',
            ],
            [
                'title' => 'Sales Monitoring',
                'description' => 'Help administrators review sales activity and facility performance from organized records.',
                'icon' => 'chart',
            ],
            [
                'title' => 'LEEO Administration',
                'description' => 'Provide LEEO tools for application openings, review support, bidding schedules, and public notices.',
                'icon' => 'shield',
            ],
        ];
    }
}

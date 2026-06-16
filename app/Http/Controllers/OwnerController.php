<?php

namespace App\Http\Controllers;

use App\Models\LoyaltyProgram;
use App\Models\Reward;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OwnerController extends Controller
{
    public function settings(): View
    {
        $this->authorizeOwner();

        $merchant = auth()->user()->merchant;
        $program = $merchant->activeProgram() ?? $merchant->loyaltyPrograms()->create([
            'name' => 'Program Stempel',
            'stamps_per_reward' => 10,
        ]);

        return view('owner.settings', [
            'program' => $program,
            'reward' => $program->rewards()->where('is_active', true)->first(),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $this->authorizeOwner();

        $data = $request->validate([
            'stamps_per_reward' => ['required', 'integer', 'min:1', 'max:100'],
            'reward_name' => ['required', 'string', 'max:100'],
            'carry_over' => ['nullable', 'boolean'],
        ]);

        $merchant = auth()->user()->merchant;
        $program = $merchant->activeProgram();

        $program->update([
            'stamps_per_reward' => $data['stamps_per_reward'],
            'carry_over' => $request->boolean('carry_over'),
        ]);

        $reward = $program->rewards()->where('is_active', true)->first();
        if ($reward) {
            $reward->update(['name' => $data['reward_name'], 'cost_stamps' => $data['stamps_per_reward']]);
        } else {
            $program->rewards()->create([
                'name' => $data['reward_name'],
                'cost_stamps' => $data['stamps_per_reward'],
                'is_active' => true,
            ]);
        }

        return back()->with('success', 'Pengaturan program loyalitas tersimpan.');
    }

    private function authorizeOwner(): void
    {
        abort_unless(auth()->user()->isOwner(), 403, 'Hanya owner yang dapat mengakses halaman ini.');
    }
}

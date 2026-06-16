<?php

namespace App\Http\Controllers;

use App\Models\LoyaltyProgram;
use App\Models\Reward;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class OwnerProgramController extends Controller
{
    public function edit(): View
    {
        $program = $this->program();

        return view('owner.program', [
            'program' => $program,
            'rewards' => $program->rewards()->orderBy('milestone')->get(),
        ]);
    }

    /** Atur ukuran kartu (jumlah stempel total). */
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'card_size' => ['required', 'integer', 'min:1', 'max:100'],
            'carry_over' => ['nullable', 'boolean'],
        ]);

        $this->program()->update([
            'card_size' => $data['card_size'],
            'carry_over' => $request->boolean('carry_over'),
        ]);

        return back()->with('success', 'Pengaturan kartu tersimpan.');
    }

    /** Tambah hadiah pada milestone tertentu. */
    public function storeReward(Request $request): RedirectResponse
    {
        $program = $this->program();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'milestone' => ['required', 'integer', 'min:1', 'max:' . $program->card_size],
            'terms' => ['nullable', 'string', 'max:500'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        $reward = new Reward([
            'name' => $data['name'],
            'milestone' => $data['milestone'],
            'cost_stamps' => $data['milestone'],
            'terms' => $data['terms'] ?? null,
            'is_active' => true,
        ]);

        if ($request->hasFile('image')) {
            $reward->image_path = $request->file('image')->store('rewards', 'public');
        }

        $program->rewards()->save($reward);

        return back()->with('success', 'Hadiah ditambahkan.');
    }

    public function updateReward(Request $request, Reward $reward): RedirectResponse
    {
        $this->authorizeReward($reward);
        $program = $this->program();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'milestone' => ['required', 'integer', 'min:1', 'max:' . $program->card_size],
            'terms' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('image')) {
            if ($reward->image_path) {
                Storage::disk('public')->delete($reward->image_path);
            }
            $reward->image_path = $request->file('image')->store('rewards', 'public');
        }

        $reward->fill([
            'name' => $data['name'],
            'milestone' => $data['milestone'],
            'cost_stamps' => $data['milestone'],
            'terms' => $data['terms'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ])->save();

        return back()->with('success', 'Hadiah diperbarui.');
    }

    public function destroyReward(Reward $reward): RedirectResponse
    {
        $this->authorizeReward($reward);

        if ($reward->image_path) {
            Storage::disk('public')->delete($reward->image_path);
        }
        $reward->delete();

        return back()->with('success', 'Hadiah dihapus.');
    }

    private function program(): LoyaltyProgram
    {
        $merchant = auth()->user()->merchant;

        return $merchant->activeProgram() ?? $merchant->loyaltyPrograms()->create([
            'name' => 'Program Stempel',
            'card_size' => 10,
            'stamps_per_reward' => 10,
        ]);
    }

    private function authorizeReward(Reward $reward): void
    {
        abort_unless(
            $reward->loyaltyProgram->merchant_id === auth()->user()->merchant_id,
            404
        );
    }
}

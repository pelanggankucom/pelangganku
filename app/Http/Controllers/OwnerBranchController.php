<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OwnerBranchController extends Controller
{
    public function index(): View
    {
        return view('owner.branches', [
            'branches' => auth()->user()->merchant->branches()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        auth()->user()->merchant->branches()->create($data + ['is_active' => true]);

        return back()->with('success', 'Outlet ditambahkan.');
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $this->authorizeBranch($branch);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $branch->update([
            'name' => $data['name'],
            'address' => $data['address'] ?? null,
            'phone' => $data['phone'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Outlet diperbarui.');
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        $this->authorizeBranch($branch);
        $branch->delete();

        return back()->with('success', 'Outlet dihapus.');
    }

    private function authorizeBranch(Branch $branch): void
    {
        abort_unless($branch->merchant_id === auth()->user()->merchant_id, 404);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class OwnerController extends Controller
{
    public function dashboard(): View
    {
        $merchant = auth()->user()->merchant;

        return view('owner.dashboard', [
            'merchant' => $merchant,
            'branchCount' => $merchant->branches()->count(),
            'program' => $merchant->activeProgram(),
        ]);
    }
}

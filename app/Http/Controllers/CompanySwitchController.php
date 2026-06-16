<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

class CompanySwitchController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function switch(Request $request)
    {
        $user = auth()->user();

        abort_unless($user->is_super_admin, 403);

        $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        session(['active_company_id' => (int) $request->company_id]);

        return back()->with('success', 'Company context switched.');
    }
}

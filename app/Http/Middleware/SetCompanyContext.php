<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Company;

class SetCompanyContext
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            $user = auth()->user();

            if ($user->is_super_admin) {
                // Default super admin to first company if no session set yet
                if (!session()->has('active_company_id')) {
                    $firstCompany = Company::orderBy('id')->first();
                    session(['active_company_id' => $firstCompany?->id]);
                }
                $companyId = session('active_company_id');
            } else {
                $companyId = $user->company_id;
                session(['active_company_id' => $companyId]);
            }

            app()->instance('current_company_id', $companyId);
        } elseif ($request->hasHeader('session') && $request->header('session')) {
            // API driver authentication via session header (not Laravel Auth)
            $driver = \App\Models\Driver::where('session', $request->header('session'))->first();
            if ($driver && $driver->company_id) {
                app()->instance('current_company_id', $driver->company_id);
            }
        }

        return $next($request);
    }
}

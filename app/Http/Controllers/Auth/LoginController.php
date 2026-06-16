<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
    
    public function authenticated(Request $request)
    {
        $user = $request->user();

        // Set the active company in session on login
        if (!$user->is_super_admin) {
            session(['active_company_id' => $user->company_id]);
        }
        // Super admin: leave active_company_id unset (null = view all), they switch later

        // Super admin: default to first company then go to invoices
        if ($user->is_super_admin) {
            $firstCompany = Company::orderBy('id')->first();
            session(['active_company_id' => $firstCompany?->id]);
            return redirect()->route('invoices.index');
        }

        $role = $user->roles()->first();
        $permissions = $role ? $role->permissions()->pluck('name')->toArray() : [];

        if (in_array('inventorybalance', $permissions) && !in_array('invoice', $permissions)) {
            return redirect()->route('inventoryBalances.index');
        } elseif ($role && $role->name === 'admin') {
            return redirect()->route('invoices.index');
        }

        return redirect(RouteServiceProvider::HOME);
    }
}           

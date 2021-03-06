<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ConnectionTrait;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use LaravelEnso\Companies\Models\Company;
use LaravelEnso\Core\Events\Login;
use LaravelEnso\Multitenancy\Enums\Connections;
use LaravelEnso\Multitenancy\Services\Tenant;

class LoginController extends Controller
{
    use AuthenticatesUsers, ConnectionTrait;

    protected $redirectTo = '/';

    public function __construct()
    {
        $this->middleware('guest')->except('logout');

        $this->maxAttempts = config('enso.auth.maxLoginAttempts');
    }

    public function logout(Request $request)
    {
        $this->guard()->logout();

        $request->session()->invalidate();
    }

    protected function attemptLogin(Request $request)
    {
        $user = $this->loggableUser($request);

        if (! $user) {
            return false;
        }

        Auth::login($user, $request->input('remember'));

        Login::dispatch($user, $request->ip(), $request->header('User-Agent'));

        return true;
    }

    protected function authenticated(Request $request, $user)
    {
        return response()->json([
            'auth' => Auth::check(),
            'csrfToken' => csrf_token(),
        ]);
    }

    private function loggableUser(Request $request)
    {
        $user = User::whereEmail($request->input('email'))->first();
        $company = $user->company();
        $tanent = false;
        if ($company) {
            $tanent = true;
        }
        // set company id as default
        $main_company = $user->person->company();
        if ($main_company !== null && ! ($user->isAdmin())) {
            $c_id = $main_company->id;
            $db = Connections::Tenant.$c_id;
            $this->setConnection(Connections::Tenant, $db);
        }

        if ($main_company == null && ! $user->isAdmin()) {
//          if ($main_company == null) {
            $company_count = Company::count();

            $company = Company::create([
                'name' => $user->email.($company_count + 1),
                'email' => $user->email,
                // 'is_active' => 1,
                'is_tenant' => 1,
                'status' => 1,
            ]);
            $user->person->companies()->attach($company->id, ['person_id' => $user->person->id, 'is_main' => 1, 'is_mandatary' => 1, 'company_id' => $company->id]);

            /**            Tree::create([
             * 'name' => 'Default Tree',
             * 'description' => 'Automatically created tree as only tree remaining was deleted.',
             * 'user_id' => $user->id,
             * 'company_id' => $company->id,
             * ]);.
             */
            $company_id = $company->id;
            $user_id = $user->id;
            $person_name = $user->person->name;
            $user_email = $user->email;

            $db = $company_id;
            $this->setConnection(Connections::Tenant, $db, $user_id);
            $this->getConnection();

            CreateDB::dispatch($company, $user_id);
            Migration::dispatch($company_id, $user_id, $person_name, $user_email);
        }
        if (! optional($user)->currentPasswordIs($request->input('password'))) {
            return;
        }

        if ($user->passwordExpired()) {
            throw ValidationException::withMessages([
                'email' => 'Password expired. Please set a new one.',
            ]);
        }
        if ($user->isInactive()) {
            throw ValidationException::withMessages([
                'email' => 'Account disabled. Please contact the administrator.',
            ]);
        }

        return $user;
    }
}

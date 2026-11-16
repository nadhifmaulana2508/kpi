<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SsoSessionEstablisher
{
    public function __construct(private readonly SsoIdentityResolver $identities) {}

    /** The caller must validate the IdP response before passing its claims here. */
    public function establishFromVerifiedIdentity(Request $request, string $issuer, string $subject): ?Employee
    {
        $identity = $this->identities->findMappedIdentity($issuer, $subject);
        $employee = $identity?->employee;

        if (! $identity || ! $employee) {
            Auth::guard('web')->logout();
            $request->session()->forget('sso_identity_id');
            $request->session()->regenerate();

            return null;
        }

        Auth::guard('web')->login($employee);
        $request->session()->regenerate();
        $request->session()->put('sso_identity_id', $identity->id);

        return $employee;
    }
}

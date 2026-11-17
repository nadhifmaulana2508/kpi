<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DemoAccessContext;
use App\Services\SsoIdentityResolver;
use App\Services\SsoSessionEstablisher;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SsoLoginController extends Controller
{
    public function store(
        Request $request,
        DemoAccessContext $context,
        SsoIdentityResolver $identities,
        SsoSessionEstablisher $sessions,
    ): JsonResponse {
        $validated = $request->validate([
            'id_peg' => ['required', 'string', 'max:80'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $baseUrl = rtrim((string) config('services.sso.base_url'), '/');
        $app = (string) config('services.sso.app');
        $appToken = (string) config('services.sso.app_token');

        if ($baseUrl === '' || $app === '' || $appToken === '') {
            return $this->failure('Konfigurasi layanan identitas belum lengkap.', 503, 'SSO_NOT_CONFIGURED');
        }

        try {
            $headers = [
                'App' => $app,
                'X-App-Token' => $appToken,
            ];

            $loginResponse = Http::acceptJson()
                ->withHeaders($headers)
                ->withOptions(['verify' => config('services.sso.ca_bundle')])
                ->timeout(12)
                ->post($baseUrl.'/api/auth/login', [
                    'id_peg' => $validated['id_peg'],
                    'password' => $validated['password'],
                ]);

            $loginStatus = $loginResponse->json('status');
            if (
                in_array($loginResponse->status(), [401, 422], true)
                || in_array((int) $loginStatus, [401, 422], true)
            ) {
                return $this->failure('ID pegawai atau password belum cocok.', 401, 'SSO_CREDENTIALS_INVALID');
            }

            if (! $loginResponse->successful() || ($loginStatus !== null && (int) $loginStatus !== 200)) {
                return $this->failure('Layanan identitas belum dapat memproses login.', 502, 'SSO_LOGIN_FAILED');
            }

            $accessToken = $loginResponse->json('data.token');
            if (! is_string($accessToken) || $accessToken === '') {
                return $this->failure('Respons login dari layanan identitas belum lengkap.', 502, 'SSO_TOKEN_MISSING');
            }

            $whoamiResponse = Http::acceptJson()
                ->withHeaders($headers)
                ->withOptions(['verify' => config('services.sso.ca_bundle')])
                ->withToken($accessToken)
                ->timeout(12)
                ->get($baseUrl.'/api/auth/whoami');

            $whoamiStatus = $whoamiResponse->json('status');
            if (! $whoamiResponse->successful() || ($whoamiStatus !== null && (int) $whoamiStatus !== 200)) {
                return $this->failure('Token berhasil dibuat, tetapi profil pegawai belum dapat dibaca.', 502, 'SSO_PROFILE_FAILED');
            }

            $profile = $whoamiResponse->json('data');
            $subject = is_array($profile) ? ($profile['employee_id'] ?? null) : null;
            if (! is_string($subject) || trim($subject) === '') {
                return $this->failure('Profil dari layanan identitas belum memuat employee_id.', 502, 'SSO_SUBJECT_MISSING');
            }

            $issuer = (string) (config('services.sso.issuer') ?: $baseUrl);
            $identity = $identities->findMappedIdentity($issuer, $subject);

            if (! $identity) {
                return $this->failure(
                    'Login SSO berhasil, tetapi akun ini belum dipetakan ke identitas KPI. Hubungkan employee_id pada master SSO terlebih dahulu.',
                    403,
                    'SSO_IDENTITY_UNMAPPED',
                );
            }

            $employee = $sessions->establishFromVerifiedIdentity($request, $issuer, $subject);
            if (! $employee) {
                return $this->failure('Identitas KPI tidak aktif atau penugasan utamanya sudah berakhir.', 403, 'SSO_IDENTITY_INACTIVE');
            }

            return response()->json([
                'data' => $context->for($employee) + ['auth_source' => 'SSO'],
            ]);
        } catch (ConnectionException $exception) {
            $detail = strtolower($exception->getMessage());
            $certificateFailure = str_contains($detail, 'certificate')
                || str_contains($detail, 'trust anchor')
                || str_contains($detail, 'curl error 60')
                || str_contains($detail, 'curl error 77');

            if ($certificateFailure) {
                return $this->failure(
                    'Sertifikat HTTPS SSO tidak bisa diverifikasi. Periksa CA bundle PHP Herd dan rantai sertifikat server SSO.',
                    503,
                    'SSO_TLS_CERTIFICATE_INVALID',
                );
            }

            return $this->failure('Layanan identitas tidak dapat dijangkau saat ini.', 503, 'SSO_UNAVAILABLE');
        }
    }

    private function failure(string $message, int $status, string $code): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], $status);
    }
}

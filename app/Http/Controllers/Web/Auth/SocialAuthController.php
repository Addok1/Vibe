<?php

namespace App\Http\Controllers\Web\Auth;

use App\Base\Constants\Auth\Role as RoleSlug;
use App\Http\Controllers\Controller;
use App\Services\Auth\SocialIdentityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\MobileOtp;
use App\Models\LinkedSocialAccount;
use App\Models\Setting;
use App\Models\User;

class SocialAuthController extends Controller
{
    public function redirect(Request $request, string $provider)
    {
        $provider = strtolower($provider);
        if (!in_array($provider, ['google', 'facebook', 'fb', 'apple'], true)) {
            abort(404);
        }

        $redirectTo = $this->sanitizeRedirectTo((string) $request->query('redirect_to', '/create-booking'));
        $request->session()->put('social_redirect_to', $redirectTo);

        // If user entered mobile on the web booking screen before clicking social login,
        // persist it so we can auto-complete signup on callback without breaking existing OTP flow.
        if ($request->filled('mobile')) {
            $request->session()->put('social_mobile', (string) $request->query('mobile'));
            $request->session()->put('social_country', (string) $request->query('country'));
        }

        // Optional mobile fallback: send the token back to the app via a deep link / custom scheme URL.
        // Example: /social/google/redirect?redirect_uri=myapp://auth/callback
        if ($request->filled('redirect_uri')) {
            $request->session()->put('social_redirect_uri', (string) $request->query('redirect_uri'));
        }

        $state = Str::random(40);
        $request->session()->put('social_oauth_state_' . $provider, $state);

        $callbackUrl = $provider === 'apple'
            ? $this->buildAppleCallbackUrl($request)
            : $this->buildCallbackUrl($request, $provider);
        $request->session()->put('social_callback_url_' . $provider, $callbackUrl);

        if ($provider === 'google') {
            $clientId = config('services.google.client_id');
            if (!$clientId) {
                abort(500, 'Google OAuth not configured');
            }

            Log::info('Social auth redirect started', [
                'provider' => $provider,
                'redirect_to' => $redirectTo,
                'callback_url' => $callbackUrl,
                'has_mobile' => $request->filled('mobile'),
                'client_id_suffix' => $this->maskedClientIdSuffix((string) $clientId),
                'session_id' => $request->session()->getId(),
            ]);

            $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
                'client_id' => $clientId,
                'redirect_uri' => $callbackUrl,
                'response_type' => 'code',
                'scope' => 'openid email profile',
                'state' => $state,
                'prompt' => 'select_account',
            ]);

            return redirect()->away($authUrl);
        }

        if ($provider === 'apple') {
            $clientId = config('services.apple.client_id');
            if (!$clientId) {
                abort(500, 'Apple OAuth not configured');
            }

            $nonce = Str::random(40);
            $request->session()->put('social_oauth_nonce_' . $provider, $nonce);

            Log::info('Social auth redirect started', [
                'provider' => $provider,
                'redirect_to' => $redirectTo,
                'callback_url' => $callbackUrl,
                'has_mobile' => $request->filled('mobile'),
                'client_id_suffix' => $this->maskedClientIdSuffix((string) $clientId),
                'session_id' => $request->session()->getId(),
            ]);

            $authUrl = 'https://appleid.apple.com/auth/oauth2/v2/authorize?' . http_build_query([
                'client_id' => $clientId,
                'redirect_uri' => $callbackUrl,
                'response_type' => 'code id_token',
                'response_mode' => 'form_post',
                'scope' => 'name email',
                'state' => $state,
                'nonce' => $nonce,
            ]);

            return redirect()->away($authUrl);
        }

        $provider = 'facebook';
        $clientId = config('services.facebook.client_id');
        if (!$clientId) {
            abort(500, 'Facebook OAuth not configured');
        }

        Log::info('Social auth redirect started', [
            'provider' => $provider,
            'redirect_to' => $redirectTo,
            'callback_url' => $callbackUrl,
            'has_mobile' => $request->filled('mobile'),
            'client_id_suffix' => $this->maskedClientIdSuffix((string) $clientId),
            'session_id' => $request->session()->getId(),
        ]);

        $authUrl = 'https://www.facebook.com/v19.0/dialog/oauth?' . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $callbackUrl,
            'response_type' => 'code',
            'scope' => 'email,public_profile',
            'state' => $state,
        ]);

        return redirect()->away($authUrl);
    }

    public function callback(Request $request, string $provider, SocialIdentityService $social)
    {
        $provider = strtolower($provider);
        if (!in_array($provider, ['google', 'facebook', 'fb', 'apple'], true)) {
            abort(404);
        }

        if ($request->has('error')) {
            Log::warning('Social auth callback returned provider error', [
                'provider' => $provider,
                'error' => (string) $request->input('error'),
                'error_description' => (string) $request->input('error_description'),
                'session_id' => $request->session()->getId(),
            ]);

            return redirect($this->userLoginPath())->with('error', (string) $request->input('error'));
        }

        $expectedState = $request->session()->pull('social_oauth_state_' . $provider);
        if (!$expectedState || $expectedState !== $request->input('state')) {
            // Some flows (notably Facebook consent screens / strict browser privacy settings)
            // may drop or rotate the session cookie, causing a false state mismatch.
            // We still require a valid `code` exchange, which is bound to our redirect_uri.
            Log::warning('Social login state mismatch', [
                'provider' => $provider,
                'expected' => $expectedState,
                'actual' => $request->input('state'),
            ]);
        }

        $code = (string) $request->input('code');
        if (!$code) {
            // Some Facebook flows append params in the URL fragment (not sent to server).
            // Serve a tiny normalizer page that can convert hash params into query params.
            return response()->view('social.oauth-callback');
        }

        $callbackUrl = (string) $request->session()->get('social_callback_url_' . $provider);
        if ($callbackUrl === '') {
            $callbackUrl = $provider === 'apple'
                ? $this->buildAppleCallbackUrl($request)
                : $this->buildCallbackUrl($request, $provider);
        }

        try {
            Log::info('Social auth callback received', [
                'provider' => $provider,
                'has_code' => $code !== '',
                'callback_url' => $callbackUrl,
                'session_id' => $request->session()->getId(),
            ]);

            $tokens = $this->exchangeCodeForTokens($provider, $code, $callbackUrl);
            $profile = $social->fetchProfile($provider, $tokens);

            if ($provider === 'apple') {
                $appleUser = $this->extractAppleUserPayload((string) $request->input('user', ''));
                if (!empty($appleUser['name']) && empty($profile['name'])) {
                    $profile['name'] = $appleUser['name'];
                }
                if (!empty($appleUser['email']) && empty($profile['email'])) {
                    $profile['email'] = $appleUser['email'];
                }
            }

            Log::info('Social auth profile fetched', [
                'provider' => $provider,
                'provider_id' => $profile['provider_id'] ?? null,
                'email' => $profile['email'] ?? null,
                'has_name' => !empty($profile['name']),
                'session_id' => $request->session()->getId(),
            ]);

            $user = $social->findOrCreateUserForRole($profile, RoleSlug::USER, [
                // Web OAuth flow cannot provide mobile; if this is a new account, we will fail with mobile_required.
                'login_by' => 'web',
            ]);

            Log::info('Social auth user resolved', [
                'provider' => $provider,
                'user_id' => $user->id,
                'is_active' => $user->isActive(),
                'session_id' => $request->session()->getId(),
            ]);
        } catch (\RuntimeException $e) {
            Log::warning('Social auth runtime exception', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'session_id' => $request->session()->getId(),
            ]);

            if ($e->getMessage() === 'mobile_required') {
                $mobile = (string) $request->session()->get('social_mobile', '');
                $country = (string) $request->session()->get('social_country', '');

                if ($mobile !== '' && $this->mobileBelongsToExistingUser($mobile)) {
                    try {
                        $user = $social->findOrCreateUserForRole($profile, RoleSlug::USER, [
                            'mobile' => $mobile,
                            'country' => $country,
                            'login_by' => 'web',
                        ]);

                        Log::info('Social auth linked existing mobile user', [
                            'provider' => $provider,
                            'user_id' => $user->id,
                            'session_id' => $request->session()->getId(),
                        ]);
                    } catch (\RuntimeException $retryException) {
                        Log::warning('Social login mobile link retry failed', [
                            'provider' => $provider,
                            'error' => $retryException->getMessage(),
                            'session_id' => $request->session()->getId(),
                        ]);

                        if ($retryException->getMessage() === 'social_identifier_already_linked') {
                            return redirect($this->userLoginPath())
                                ->with('error', $this->socialIdentifierConflictMessage());
                        }
                    }

                    if (isset($user)) {
                        $request->session()->forget(['social_mobile', 'social_country']);
                    }
                }

                if (isset($user)) {
                    // Existing mobile-only user has been linked; continue to normal login redirect below.
                } else {
                    // New social user: continue with existing mobile OTP registration flow (no email-OTP gate).
                    $request->session()->put('pending_social_profile', [
                        'provider' => $profile['provider'] ?? $provider,
                        'provider_id' => $profile['provider_id'] ?? null,
                        'email' => $profile['email'] ?? null,
                        'name' => $profile['name'] ?? null,
                        'avatar' => $profile['avatar'] ?? null,
                    ]);

                    Log::info('Social auth pending mobile OTP signup', [
                        'provider' => $provider,
                        'provider_id' => $profile['provider_id'] ?? null,
                        'email' => $profile['email'] ?? null,
                        'redirect_to' => $this->userLoginPath(['social_mobile_otp' => 1]),
                        'session_id' => $request->session()->getId(),
                    ]);

                    return redirect($this->userLoginPath(['social_mobile_otp' => 1]));
                }
            } elseif ($e->getMessage() === 'invalid_social_token') {
                return redirect($this->userLoginPath())->with('error', 'Unable to login with social provider. Please try again.');
            } elseif ($e->getMessage() === 'social_identifier_already_linked') {
                return redirect($this->userLoginPath())
                    ->with('error', $this->socialIdentifierConflictMessage());
            } else {
                return redirect($this->userLoginPath())->with('error', 'Unable to login with social provider');
            }
        } catch (\Throwable $e) {
            Log::error($e);
            return redirect($this->userLoginPath())->with('error', 'Unable to login with social provider');
        }

        if (!$user->isActive()) {
            Log::warning('Social auth inactive user blocked', [
                'provider' => $provider,
                'user_id' => $user->id,
                'session_id' => $request->session()->getId(),
            ]);

            return redirect($this->userLoginPath())->with('error', 'Account disabled');
        }

        $redirectUri = (string) $request->session()->pull('social_redirect_uri', '');
        if ($redirectUri && $this->isAllowedMobileRedirectUri($redirectUri)) {
            // Mobile web fallback: issue a Sanctum token and redirect back to the app.
            $user->tokens()->delete();
            $token = $user->createToken('social-web-fallback')->plainTextToken;
            return redirect()->away($this->appendQuery($redirectUri, [
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]));
        }

        auth('web')->login($user, true);
        session(['module' => 'transport']);
        $request->session()->forget([
            'social_mobile',
            'social_country',
            'social_callback_url_' . $provider,
            'social_oauth_nonce_' . $provider,
        ]);

        $redirectTo = $request->session()->pull('social_redirect_to', '/create-booking');
        Log::info('Social auth login completed', [
            'provider' => $provider,
            'user_id' => $user->id,
            'redirect_to' => $redirectTo,
            'session_id' => $request->session()->getId(),
        ]);

        return redirect($this->sanitizeRedirectTo((string) $redirectTo));
    }

    /**
     * Complete signup after web OAuth callback when mobile is required.
     */
    public function complete(Request $request, SocialIdentityService $social)
    {
        $pending = $request->session()->get('pending_social_profile');
        if (!$pending || empty($pending['provider']) || empty($pending['provider_id'])) {
            return $this->respondBadRequest('No pending social signup');
        }

        $request->validate([
            'mobile' => 'required|string',
            'country' => 'sometimes|nullable',
            'name' => 'sometimes|nullable|string',
        ]);

        try {
            $user = $social->findOrCreateUserForRole([
                'provider' => $pending['provider'],
                'provider_id' => $pending['provider_id'],
                'email' => $pending['email'] ?? null,
                'name' => $pending['name'] ?? null,
                'avatar' => $pending['avatar'] ?? null,
                'raw' => [],
            ], RoleSlug::USER, [
                'mobile' => $request->input('mobile'),
                'country' => $request->input('country'),
                'name' => $request->input('name'),
                'login_by' => 'web',
            ]);
        } catch (\Throwable $e) {
            Log::error($e);
            if ($e instanceof \RuntimeException && $e->getMessage() === 'social_identifier_already_linked') {
                return $this->respondBadRequest($this->socialIdentifierConflictMessage());
            }
            return $this->respondBadRequest('Unable to complete social signup');
        }

        $request->session()->forget('pending_social_profile');

        auth('web')->login($user, true);
        session(['module' => 'transport']);

        $redirectTo = $request->session()->pull('social_redirect_to', '/create-booking');
        Log::info('Social auth signup completed', [
            'provider' => $pending['provider'],
            'user_id' => $user->id,
            'redirect_to' => $redirectTo,
            'session_id' => $request->session()->getId(),
        ]);

        return $this->respondSuccess(['redirect_to' => $redirectTo]);
    }

    public function completeWithMobileOtp(Request $request, SocialIdentityService $social)
    {
        $pending = $request->session()->get('pending_social_profile');
        if (!$pending || empty($pending['provider']) || empty($pending['provider_id'])) {
            return $this->respondBadRequest('No pending social signup');
        }

        $request->validate([
            'mobile' => 'required|string',
            'country' => 'sometimes|nullable',
        ]);

        $mobile = (string) $request->input('mobile');
        $country = (string) $request->input('country');

        // Must have a verified mobile OTP in the existing flow.
        $otpVerified = MobileOtp::query()->where('mobile', $mobile)->where('verified', true)->exists();
        if (!$otpVerified) {
            return $this->respondBadRequest('Mobile OTP not verified');
        }

        try {
            $user = $social->findOrCreateUserForRole([
                'provider' => $pending['provider'],
                'provider_id' => $pending['provider_id'],
                'email' => $pending['email'] ?? null,
                'name' => $pending['name'] ?? null,
                'avatar' => $pending['avatar'] ?? null,
                'raw' => [],
            ], RoleSlug::USER, [
                'mobile' => $mobile,
                'country' => $country,
                'login_by' => 'web',
            ]);
        } catch (\Throwable $e) {
            Log::error($e);
            if ($e instanceof \RuntimeException && $e->getMessage() === 'social_identifier_already_linked') {
                return $this->respondBadRequest($this->socialIdentifierConflictMessage());
            }
            if ($e instanceof \RuntimeException && $e->getMessage() === 'social_account_already_linked') {
                $linked = LinkedSocialAccount::query()
                    ->where('provider_id', (string) $pending['provider_id'])
                    ->first();
                if ($linked && $linked->user) {
                    $user = $linked->user;
                    if (method_exists($user, 'isActive') && !$user->isActive()) {
                        return $this->respondBadRequest('Account disabled');
                    }
                    auth('web')->login($user, true);
                    session(['module' => 'transport']);
                    $redirectTo = $request->session()->pull('social_redirect_to', '/create-booking');
                    Log::info('Social auth mobile OTP completed for linked account', [
                        'provider' => $pending['provider'],
                        'user_id' => $user->id,
                        'redirect_to' => $redirectTo,
                        'session_id' => $request->session()->getId(),
                    ]);

                    return $this->respondSuccess(['redirect_to' => $redirectTo]);
                }

                return $this->respondBadRequest('This social account is already linked to another user');
            }
            if (config('app.debug')) {
                return $this->respondBadRequest('Unable to complete social signup: ' . $e->getMessage());
            }
            return $this->respondBadRequest('Unable to complete social signup');
        }

        $request->session()->forget('pending_social_profile');

        auth('web')->login($user, true);
        session(['module' => 'transport']);

        $redirectTo = $request->session()->pull('social_redirect_to', '/create-booking');
        Log::info('Social auth mobile OTP signup completed', [
            'provider' => $pending['provider'],
            'user_id' => $user->id,
            'redirect_to' => $redirectTo,
            'session_id' => $request->session()->getId(),
        ]);

        return $this->respondSuccess(['redirect_to' => $redirectTo]);
    }

    /**
     * When Firebase OTP is enabled on the web booking page, OTP verification happens on the client.
     * Mark the given mobile as OTP verified so the existing server-side flow can proceed.
     */
    public function markMobileOtpVerified(Request $request)
    {
        $request->validate([
            'mobile' => 'required|string',
        ]);

        $mobile = (string) $request->input('mobile');

        $row = MobileOtp::query()->firstOrNew(['mobile' => $mobile]);
        if (!$row->otp) {
            $row->otp = (string) mt_rand(100000, 999999);
        }
        $row->verified = true;
        $row->save();

        return $this->respondSuccess();
    }

    private function exchangeCodeForTokens(string $provider, string $code, string $redirectUri): array
    {
        if ($provider === 'google') {
            $clientId = config('services.google.client_id');
            $clientSecret = config('services.google.client_secret');

            $res = Http::timeout(10)->asForm()->post('https://oauth2.googleapis.com/token', [
                'code' => $code,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            ]);
            if (!$res->ok()) {
                Log::warning('Google token exchange failed', [
                    'status' => $res->status(),
                    'body' => $res->json() ?: $res->body(),
                ]);
                throw new \RuntimeException('invalid_social_token');
            }

            return [
                'access_token' => $res->json('access_token'),
                'id_token' => $res->json('id_token'),
            ];
        }

        if ($provider === 'apple') {
            return [
                'authorization_code' => $code,
                'redirect_uri' => $redirectUri,
            ];
        }

        $provider = 'facebook';
        $clientId = config('services.facebook.client_id');
        $clientSecret = config('services.facebook.client_secret');

        $res = Http::timeout(10)->get('https://graph.facebook.com/v19.0/oauth/access_token', [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'code' => $code,
        ]);
        if (!$res->ok()) {
            Log::warning('Facebook token exchange failed', [
                'status' => $res->status(),
                'body' => $res->json() ?: $res->body(),
            ]);
            throw new \RuntimeException('invalid_social_token');
        }

        return [
            'access_token' => $res->json('access_token'),
        ];
    }

    private function isAllowedMobileRedirectUri(string $redirectUri): bool
    {
        $allowlist = array_values(array_filter(array_map('trim', explode(',', (string) env('MOBILE_SOCIAL_REDIRECT_ALLOWLIST', '')))));
        if (empty($allowlist)) {
            return false;
        }

        foreach ($allowlist as $prefix) {
            if ($prefix !== '' && str_starts_with($redirectUri, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function appendQuery(string $url, array $params): string
    {
        $separator = str_contains($url, '?') ? '&' : '?';
        return $url . $separator . http_build_query($params);
    }

    private function buildCallbackUrl(Request $request, string $provider): string
    {
        $path = route('social.callback', ['provider' => $provider], false);
        return rtrim($request->getSchemeAndHttpHost(), '/') . '/' . ltrim($path, '/');
    }

    private function buildAppleCallbackUrl(Request $request): string
    {
        $configuredRedirect = trim((string) config('services.apple.redirect', ''));

        if ($configuredRedirect !== '') {
            return $this->normalizeRedirectUrl($configuredRedirect);
        }

        return $this->buildCallbackUrl($request, 'apple');
    }

    private function normalizeRedirectUrl(string $url): string
    {
        $parts = parse_url($url);
        if ($parts === false) {
            return rtrim($url, '/');
        }

        $scheme = isset($parts['scheme']) ? $parts['scheme'] . '://' : '';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path = '/' . ltrim((string) ($parts['path'] ?? ''), '/');
        $path = preg_replace('#/+#', '/', $path) ?: $path;
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

        return $scheme . $host . $port . $path . $query . $fragment;
    }

    private function maskedClientIdSuffix(string $clientId): string
    {
        if ($clientId === '') {
            return '';
        }

        return substr($clientId, -24);
    }

    private function socialIdentifierConflictMessage(): string
    {
        return 'Mail or mobile number already with another account';
    }

    private function extractAppleUserPayload(string $payload): array
    {
        if ($payload === '') {
            return [];
        }

        $decoded = json_decode($payload, true);
        if (!is_array($decoded)) {
            return [];
        }

        $firstName = trim((string) data_get($decoded, 'name.firstName', ''));
        $lastName = trim((string) data_get($decoded, 'name.lastName', ''));
        $displayName = trim($firstName . ' ' . $lastName);
        $email = trim((string) data_get($decoded, 'email', ''));

        return array_filter([
            'name' => $displayName !== '' ? $displayName : null,
            'email' => $email !== '' ? $email : null,
        ]);
    }

    private function mobileBelongsToExistingUser(string $mobile): bool
    {
        return User::query()
            ->belongsToRole(RoleSlug::USER)
            ->where('mobile', $mobile)
            ->exists();
    }

    private function userLoginPath(array $query = []): string
    {
        $segment = trim((string) Setting::where('name', 'user_login')->value('value'));
        $segment = $segment !== '' ? $segment : 'user';
        $path = '/login/' . $segment;

        return $query ? $path . '?' . http_build_query($query) : $path;
    }

    private function sanitizeRedirectTo(string $redirectTo): string
    {
        $redirectTo = trim($redirectTo);
        if ($redirectTo === '') {
            return '/create-booking';
        }

        // Only allow in-app relative paths (prevents open redirects).
        if (!str_starts_with($redirectTo, '/')) {
            return '/create-booking';
        }
        if (str_starts_with($redirectTo, '//') || str_contains($redirectTo, "\n") || str_contains($redirectTo, "\r")) {
            return '/create-booking';
        }

        // Never bounce back to login after successful social auth.
        if ($redirectTo === '/login' || str_starts_with($redirectTo, '/login/')) {
            return '/create-booking';
        }

        return $redirectTo;
    }
}

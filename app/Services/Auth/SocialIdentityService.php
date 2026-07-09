<?php

namespace App\Services\Auth;

use App\Base\Constants\Auth\Role as RoleSlug;
use App\Events\Auth\UserRegistered;
use App\Models\LinkedSocialAccount;
use App\Models\User;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SocialIdentityService
{
    private function normalizeProvider(string $provider): string
    {
        $provider = strtolower($provider);
        if ($provider === 'fb') {
            return 'facebook';
        }
        return $provider;
    }

    private function providerAliases(string $provider): array
    {
        $provider = $this->normalizeProvider($provider);
        return match ($provider) {
            'facebook' => ['facebook', 'fb'],
            default => [$provider],
        };
    }

    /**
     * Fetch a normalized social profile from the provider token(s).
     *
     * Returns: ['provider','provider_id','email','name','avatar','raw']
     */
    public function fetchProfile(string $provider, array $tokens): array
    {
        $provider = $this->normalizeProvider($provider);

        return match ($provider) {
            'google' => $this->fetchGoogleProfile($tokens),
            'facebook' => $this->fetchFacebookProfile($tokens),
            'apple' => $this->fetchAppleProfile($tokens),
            default => throw new \InvalidArgumentException('Unsupported provider'),
        };
    }

    /**
     * Find or create a USER role account and link the provider identity.
     *
     * $attrs can include: mobile, country, name, email, device_token, login_by, apn_token
     */
    public function findOrCreateUserForRole(array $profile, string $roleSlug, array $attrs = []): User
    {

        Log::info('findOrCreateUserForRole', [
            'provider' => $profile['provider'] ?? null,
            'provider_id_preview' => $this->maskTokenForLog((string) ($profile['provider_id'] ?? '')),
            'email' => $profile['email'] ?? null,
            'name' => $profile['name'] ?? null,
            'role' => $roleSlug,
            'mobile' => $this->maskMobileForLog((string) Arr::get($attrs, 'mobile', '')),
            'profile_picture' => Arr::get($attrs, 'profile_picture'),
            'avatar' => $profile['avatar'] ?? null,
            'attrs' => $attrs,
        ]);
        $provider = $this->normalizeProvider($profile['provider']);
        $providerId = $profile['provider_id'];
        $email = $profile['email'] ?? null;
        $mobile = Arr::get($attrs, 'mobile');

        // 0) Provider id already linked (provider_name may be legacy 'fb' vs 'facebook')
        $linkedAny = LinkedSocialAccount::query()
            ->where('provider_id', $providerId)
            ->first();
        if ($linkedAny && $linkedAny->user) {
            Log::info('social_identity_existing_linked_any', [
                'provider' => $provider,
                'provider_id_preview' => $this->maskTokenForLog((string) $providerId),
                'linked_user_id' => $linkedAny->user->id ?? null,
            ]);
            return $linkedAny->user;
        }

        // 1) Already linked via linked_social_accounts
        $linked = LinkedSocialAccount::query()
            ->whereIn('provider_name', $this->providerAliases($provider))
            ->where('provider_id', $providerId)
            ->first();
        if ($linked && $linked->user) {
            Log::info('social_identity_existing_linked_provider', [
                'provider' => $provider,
                'provider_id_preview' => $this->maskTokenForLog((string) $providerId),
                'linked_user_id' => $linked->user->id ?? null,
            ]);
            return $linked->user;
        }

        // 2) Backwards-compatibility fields on users table
        $user = User::query()
            ->belongsToRole($roleSlug)
            ->whereIn('social_provider', $this->providerAliases($provider))
            ->where('social_id', $providerId)
            ->first();
        if ($user) {
            Log::info('social_identity_existing_user_columns', [
                'provider' => $provider,
                'provider_id_preview' => $this->maskTokenForLog((string) $providerId),
                'user_id' => $user->id ?? null,
            ]);
            $this->linkIdentity($user, $provider, $providerId);
            return $user;
        }

        // 3) Match by email (if present)
        if ($email) {
            $user = User::query()
                ->belongsToRole($roleSlug)
                ->where('email', $email)
                ->first();
            if ($user) {
                Log::info('social_identity_match_by_email', [
                    'provider' => $provider,
                    'provider_id_preview' => $this->maskTokenForLog((string) $providerId),
                    'user_id' => $user->id ?? null,
                    'email' => $email,
                ]);
                if ($this->hasConflictingSocialIdentity($user, $provider, $providerId)) {
                    throw new \RuntimeException('social_identifier_already_linked');
                }

                $this->linkIdentity($user, $provider, $providerId);
                return $this->updateUserFromProfile($user, $profile, $attrs);
            }
        }

        // 4) Match by mobile (optional)
        if ($mobile) {
            $user = User::query()
                ->belongsToRole($roleSlug)
                ->where('mobile', $mobile)
                ->first();
            if ($user) {
                Log::info('social_identity_match_by_mobile', [
                    'provider' => $provider,
                    'provider_id_preview' => $this->maskTokenForLog((string) $providerId),
                    'user_id' => $user->id ?? null,
                    'mobile' => $this->maskMobileForLog((string) $mobile),
                ]);
                if ($this->hasConflictingSocialIdentity($user, $provider, $providerId)) {
                    throw new \RuntimeException('social_identifier_already_linked');
                }

                $this->linkIdentity($user, $provider, $providerId);
                return $this->updateUserFromProfile($user, $profile, $attrs);
            }
        }

        // 5) Create new user
        $name = Arr::get($attrs, 'name') ?: ($profile['name'] ?? null) ?: 'User';

        if (!$mobile) {
            throw new \RuntimeException('mobile_required');
        }

        $user = new User();
        $user->name = Str::limit($name, 50, '');
        $user->email = $email ?: Arr::get($attrs, 'email');
        $user->mobile = $mobile;
        $user->country = Arr::get($attrs, 'country');
        $user->active = true;
        $user->login_by = Arr::get($attrs, 'login_by');
        $user->fcm_token = Arr::get($attrs, 'device_token');
        $user->apn_token = Arr::get($attrs, 'apn_token');
        $user->profile_picture = Arr::get($attrs, 'profile_picture');
        $user->save();

        Log::info('social_identity_new_user_created', [
            'provider' => $provider,
            'provider_id_preview' => $this->maskTokenForLog((string) $providerId),
            'user_id' => $user->id ?? null,
            'email' => $user->email ?? null,
            'mobile' => $this->maskMobileForLog((string) $user->mobile),
        ]);

        // Create empty wallet if relation exists (keeps parity with normal registration flow).
        if (method_exists($user, 'userWallet')) {
            $user->userWallet()->firstOrCreate([], ['amount_added' => 0]);
        }

        $user->attachRole($roleSlug);

        $this->linkIdentity($user, $provider, $providerId);
        $this->updateUserFromProfile($user, $profile, $attrs);

        if ($roleSlug === RoleSlug::USER) {
            event(new UserRegistered($user));
        }

        return $user;
    }

    private function hasConflictingSocialIdentity(User $user, string $provider, string $providerId): bool
    {
        $provider = $this->normalizeProvider($provider);
        $aliases = $this->providerAliases($provider);

        $hasLinkedConflict = LinkedSocialAccount::query()
            ->where('user_id', $user->id)
            ->where(function ($query) use ($aliases, $providerId) {
                $query->whereNotIn('provider_name', $aliases)
                    ->orWhere(function ($query) use ($aliases, $providerId) {
                        $query->whereIn('provider_name', $aliases)
                            ->where('provider_id', '<>', $providerId);
                    });
            })
            ->exists();

        if ($hasLinkedConflict) {
            return true;
        }

        if (!$user->social_provider || !$user->social_id) {
            return false;
        }

        return !in_array($user->social_provider, $aliases, true)
            || (string) $user->social_id !== (string) $providerId;
    }

    public function linkIdentity(User $user, string $provider, string $providerId): void
    {
        $provider = $this->normalizeProvider($provider);

        Log::info('social_identity_link_start', [
            'provider' => $provider,
            'provider_id_preview' => $this->maskTokenForLog((string) $providerId),
            'user_id' => $user->id ?? null,
            'existing_social_provider' => $user->social_provider ?? null,
            'existing_social_id_preview' => $this->maskTokenForLog((string) ($user->social_id ?? '')),
        ]);

        // provider_id is globally unique; old data may have provider_name 'fb' vs 'facebook'.
        $existing = LinkedSocialAccount::query()->where('provider_id', $providerId)->first();
        if ($existing) {
            if ((int) $existing->user_id !== (int) $user->id) {
                Log::warning('social_identity_link_conflict_existing_row', [
                    'provider' => $provider,
                    'provider_id_preview' => $this->maskTokenForLog((string) $providerId),
                    'existing_user_id' => $existing->user_id,
                    'current_user_id' => $user->id,
                ]);
                throw new \RuntimeException('social_account_already_linked');
            }
            if ($existing->provider_name !== $provider) {
                $existing->provider_name = $provider;
                $existing->save();
                Log::info('social_identity_link_existing_row_provider_updated', [
                    'provider' => $provider,
                    'provider_id_preview' => $this->maskTokenForLog((string) $providerId),
                    'user_id' => $user->id ?? null,
                ]);
            }
        } else {
            $existing = new LinkedSocialAccount();
            $existing->provider_name = $provider;
            $existing->provider_id = $providerId;
            $existing->user_id = $user->id;
            $existing->save();
            Log::info('social_identity_link_row_created', [
                'provider' => $provider,
                'provider_id_preview' => $this->maskTokenForLog((string) $providerId),
                'user_id' => $user->id ?? null,
                'linked_social_account_id' => $existing->id ?? null,
            ]);
        }

        // Backward-compatible columns.
        $user->social_provider = $provider;
        $user->social_id = $providerId;
        $user->save();

        Log::info('social_identity_link_completed', [
            'provider' => $provider,
            'provider_id_preview' => $this->maskTokenForLog((string) $providerId),
            'user_id' => $user->id ?? null,
            'stored_social_provider' => $user->social_provider ?? null,
            'stored_social_id_preview' => $this->maskTokenForLog((string) ($user->social_id ?? '')),
        ]);
    }

    public function updateUserFromProfile(User $user, array $profile, array $attrs = []): User
    {

        Log::info('updateUserFromProfile', [
                'user_id' => $user->id ?? null,
                'provider' => $profile['provider'] ?? null,
                'provider_id_preview' => $this->maskTokenForLog((string) ($profile['provider_id'] ?? '')),
                'profile_picture' => Arr::get($attrs, 'profile_picture'),
                'avatar' => $profile['avatar'] ?? null,
                'attrs' => $attrs,
            ]);
        $dirty = false;

        if (!$user->email && !empty($profile['email'])) {
            $user->email = $profile['email'];
            $dirty = true;
        }
        if (!empty($profile['name']) && $user->name !== $profile['name']) {
            $user->name = Str::limit($profile['name'], 50, '');
            $dirty = true;
        }
        if (!empty($profile['avatar'])) {
            $user->social_avatar = $profile['avatar'];
            $dirty = true;
        }
        $profilePicture = Arr::get($attrs, 'profile_picture') ?: ($profile['avatar'] ?? null);
        Log::info('$profilePicture' .$profilePicture);
        if ($profilePicture && $user->getRawOriginal('profile_picture') !== $profilePicture) {
            $user->profile_picture = $profilePicture;
            $dirty = true;
        }

        foreach (['device_token' => 'fcm_token', 'login_by' => 'login_by', 'apn_token' => 'apn_token'] as $in => $col) {
            if (array_key_exists($in, $attrs)) {
                $user->{$col} = $attrs[$in] ?: null;
                $dirty = true;
            }
        }

        if ($dirty) {
            $user->save();
        }

        return $user;
    }

    private function maskTokenForLog(string $value, int $visible = 6): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (strlen($value) <= $visible * 2) {
            return substr($value, 0, max(0, $visible)) . '***';
        }

        return substr($value, 0, $visible) . '...' . substr($value, -$visible);
    }

    private function maskMobileForLog(string $mobile): string
    {
        $mobile = trim($mobile);
        if ($mobile === '') {
            return '';
        }

        return strlen($mobile) <= 4 ? '****' : substr($mobile, 0, 2) . str_repeat('*', max(0, strlen($mobile) - 4)) . substr($mobile, -2);
    }

    private function fetchGoogleProfile(array $tokens): array
    {
        $idToken = Arr::get($tokens, 'id_token');
        $accessToken = Arr::get($tokens, 'access_token');
        $oauthToken = Arr::get($tokens, 'oauth_token');

        // Older mobile builds send Google's ID token in oauth_token/access_token.
        // ID tokens are JWTs and must be checked with tokeninfo, not userinfo.
        if (!$idToken) {
            foreach ([$oauthToken, $accessToken] as $token) {
                if ($this->looksLikeJwt($token)) {
                    $idToken = $token;
                    break;
                }
            }
        }

        if (!$accessToken && !$this->looksLikeJwt($oauthToken)) {
            $accessToken = $oauthToken;
        }

        if ($idToken) {
            $res = Http::timeout(10)->get('https://oauth2.googleapis.com/tokeninfo', [
                'id_token' => $idToken,
            ]);
            if (!$res->ok()) {
                throw new \RuntimeException('invalid_social_token');
            }
            $data = $res->json();
            $providerId = (string) ($data['sub'] ?? '');
            if ($providerId === '') {
                throw new \RuntimeException('invalid_social_token');
            }
            return [
                'provider' => 'google',
                'provider_id' => $providerId,
                'email' => $data['email'] ?? null,
                'name' => $data['name'] ?? null,
                'avatar' => $data['picture'] ?? null,
                'raw' => $data,
            ];
        }

        if ($accessToken) {
            $res = Http::timeout(10)
                ->withToken($accessToken)
                ->get('https://www.googleapis.com/oauth2/v3/userinfo');
            if (!$res->ok()) {
                throw new \RuntimeException('invalid_social_token');
            }
            $data = $res->json();
            $providerId = (string) ($data['sub'] ?? $data['id'] ?? '');
            if ($providerId === '') {
                throw new \RuntimeException('invalid_social_token');
            }
            return [
                'provider' => 'google',
                'provider_id' => $providerId,
                'email' => $data['email'] ?? null,
                'name' => $data['name'] ?? null,
                'avatar' => $data['picture'] ?? null,
                'raw' => $data,
            ];
        }

        throw new \RuntimeException('missing_social_token');
    }

    private function looksLikeJwt($token): bool
    {
        return is_string($token)
            && substr_count($token, '.') === 2
            && str_starts_with($token, 'eyJ');
    }

    private function fetchFacebookProfile(array $tokens): array
    {
        $accessToken = Arr::get($tokens, 'access_token') ?: Arr::get($tokens, 'oauth_token');
        if (!$accessToken) {
            throw new \RuntimeException('missing_social_token');
        }

        $res = Http::timeout(10)->get('https://graph.facebook.com/me', [
            'fields' => 'id,name,email,picture.type(large)',
            'access_token' => $accessToken,
        ]);
        if (!$res->ok()) {
            throw new \RuntimeException('invalid_social_token');
        }
        $data = $res->json();
        $avatar = data_get($data, 'picture.data.url');

        return [
            'provider' => 'facebook',
            'provider_id' => (string) ($data['id'] ?? ''),
            'email' => $data['email'] ?? null,
            'name' => $data['name'] ?? null,
            'avatar' => $avatar,
            'raw' => $data,
        ];
    }

    private function fetchAppleProfile(array $tokens): array
    {
        $idToken = Arr::get($tokens, 'id_token')
            ?: Arr::get($tokens, 'identity_token')
            ?: Arr::get($tokens, 'identityToken');

        $authorizationCode = Arr::get($tokens, 'authorization_code')
            ?: Arr::get($tokens, 'authorizationCode')
            ?: Arr::get($tokens, 'code');

        if (!$idToken && $authorizationCode) {
            $redirectUri = trim((string) Arr::get($tokens, 'redirect_uri', ''));
            $tokenResponse = $this->exchangeAppleAuthorizationCode($authorizationCode, $redirectUri);
            $idToken = $tokenResponse['id_token'] ?? null;
        }

        if (!$idToken) {
            throw new \RuntimeException('missing_social_token');
        }

        try {
            $claims = $this->decodeAppleIdentityToken($idToken);
        } catch (\RuntimeException $e) {
            if ($authorizationCode) {
                $redirectUri = trim((string) Arr::get($tokens, 'redirect_uri', ''));
                $tokenResponse = $this->exchangeAppleAuthorizationCode($authorizationCode, $redirectUri);
                $idToken = $tokenResponse['id_token'] ?? null;

                if (!$idToken) {
                    throw $e;
                }

                $claims = $this->decodeAppleIdentityToken($idToken);
            } else {
                throw $e;
            }
        }

        return [
            'provider' => 'apple',
            'provider_id' => (string) ($claims->sub ?? ''),
            'email' => $claims->email ?? null,
            'name' => $claims->name ?? null,
            'avatar' => null,
            'raw' => (array) $claims,
        ];
    }

    public function exchangeAppleAuthorizationCode(string $authorizationCode, string $redirectUri = ''): array
    {
        $clientIds = $this->appleClientIds();
        if (empty($clientIds)) {
            throw new \RuntimeException('invalid_social_token');
        }

        $lastError = null;
        foreach ($clientIds as $clientId) {
            $clientSecret = trim((string) $this->generateAppleClientSecret($clientId));
            if ($clientSecret === '') {
                continue;
            }

            $payload = [
                'grant_type' => 'authorization_code',
                'code' => $authorizationCode,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ];

            if ($redirectUri !== '') {
                $payload['redirect_uri'] = $redirectUri;
            }

            $response = Http::timeout(10)->asForm()->post('https://appleid.apple.com/auth/token', $payload);

            if ($response->ok()) {
                return $response->json() ?: [];
            }

            $lastError = [
                'client_id_suffix' => $this->maskTokenForLog($clientId),
                'status' => $response->status(),
                'body' => $response->json() ?: $response->body(),
            ];
        }

        Log::warning('Apple token exchange failed', $lastError ?? [
            'status' => null,
            'body' => null,
        ]);
        throw new \RuntimeException('invalid_social_token');
    }

    private function decodeAppleIdentityToken(string $idToken): object
    {
        $clientIds = $this->appleClientIds();
        if (empty($clientIds)) {
            throw new \RuntimeException('invalid_social_token');
        }

        try {
            $jwksResponse = Http::timeout(10)->get('https://appleid.apple.com/auth/keys');
            if (!$jwksResponse->ok()) {
                throw new \RuntimeException('invalid_social_token');
            }

            $keys = JWK::parseKeySet($jwksResponse->json() ?: []);
            $claims = JWT::decode($idToken, $keys);
        } catch (\Throwable $e) {
            Log::warning('Apple identity token validation failed', [
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('invalid_social_token');
        }

        if (($claims->iss ?? '') !== 'https://appleid.apple.com') {
            throw new \RuntimeException('invalid_social_token');
        }

        $audience = $claims->aud ?? null;
        $audienceMatches = false;
        foreach ($clientIds as $clientId) {
            $audienceMatches = is_array($audience)
                ? in_array($clientId, $audience, true)
                : (string) $audience === $clientId;
            if ($audienceMatches) {
                break;
            }
        }

        if (!$audienceMatches || empty($claims->sub)) {
            Log::warning('Apple identity token audience mismatch', [
                'audience' => $audience,
                'allowed_client_ids' => array_map(fn ($value) => $this->maskTokenForLog((string) $value), $clientIds),
            ]);
            throw new \RuntimeException('invalid_social_token');
        }

        return $claims;
    }

    private function generateAppleClientSecret(?string $clientId = null): string
    {
        $clientId = trim((string) ($clientId ?? config('services.apple.client_id')));
        $teamId = trim((string) config('services.apple.team_id'));
        $keyId = trim((string) config('services.apple.key_id'));
        $privateKey = trim((string) config('services.apple.private_key'));

        if ($clientId === '' || $teamId === '' || $keyId === '' || $privateKey === '') {
            throw new \RuntimeException('invalid_social_token');
        }

        $issuedAt = time();
        $payload = [
            'iss' => $teamId,
            'iat' => $issuedAt,
            'exp' => $issuedAt + (60 * 60 * 24 * 180),
            'aud' => 'https://appleid.apple.com',
            'sub' => $clientId,
        ];

        return JWT::encode($payload, $privateKey, 'ES256', $keyId);
    }

    private function appleClientIds(): array
    {
        $clientIds = array_filter(array_map('trim', [
            (string) config('services.apple.client_id'),
            (string) env('APPLE_IOS_CLIENT_ID', ''),
            (string) env('APPLE_MOBILE_CLIENT_ID', ''),
        ]));

        if (class_exists(\App\Models\Master\ProjectFlavour::class)) {
            try {
                $bundleIds = \App\Models\Master\ProjectFlavour::query()
                    ->whereNotNull('bundle_identifier')
                    ->pluck('bundle_identifier')
                    ->map(fn ($value) => trim((string) $value))
                    ->filter()
                    ->all();
                $clientIds = array_merge($clientIds, $bundleIds);
            } catch (\Throwable $e) {
                Log::debug('Apple client id discovery skipped', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return array_values(array_unique($clientIds));
    }
}

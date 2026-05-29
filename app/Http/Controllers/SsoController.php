<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Laravel\Passport\Client;
use Laravel\Passport\Token;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Firebase\JWT\JWT;

class SsoController extends Controller
{
    /**
     * Authorization endpoint - OIDC compliant
     * Handles authorization requests and redirects with authorization code
     */
    public function handleAuthorization(Request $request)
    {
        $request->validate([
            'client_id' => 'required',
            'redirect_uri' => 'required|url',
            'response_type' => 'required|in:code',
            'scope' => 'nullable|string',
            'state' => 'nullable|string',
            'nonce' => 'nullable|string',
        ]);

        $clientId = $request->query('client_id');
        $redirectUri = $request->query('redirect_uri');
        $state = $request->query('state');
        $nonce = $request->query('nonce');
        $scope = $request->query('scope', 'openid');

        $client = Client::find($clientId);
        
        if (!$client) {
            return response()->json([
                'error' => 'invalid_client',
                'error_description' => 'Client not found'
            ], 400);
        }

        if ($redirectUri && !$this->isValidRedirectUri($client, $redirectUri)) {
            return response()->json([
                'error' => 'invalid_client',
                'error_description' => 'Invalid redirect_uri'
            ], 400);
        }

        if(!Auth::check()) {
            return redirect("/");
        }

        return $this->generateAuthorizationCode($clientId, $redirectUri, $state, $scope, $nonce);
    }

    /**
     * Continue authorization after login
     * This should be called after successful login when SSO request is pending
     */
    public function continueAuthorization(Request $request)
    {
        if(!Auth::check()) {
            return redirect("/");
        }

        $authRequest = Session::get('sso_authorization_request');
        
        if (!$authRequest) {
            return redirect()->route('home');
        }

        Session::forget('sso_authorization_request');

        return $this->generateAuthorizationCode(
            $authRequest['client_id'],
            $authRequest['redirect_uri'],
            $authRequest['state'] ?? null,
            $authRequest['scope'] ?? 'openid',
            $authRequest['nonce'] ?? null
        );
    }

    /**
     * Generate authorization code and redirect
     */
    private function generateAuthorizationCode($clientId, $redirectUri, $state, $scope, $nonce = null)
    {
        $authCode = Str::random(40);

        $userId = Auth::id();
        
        Cache::put('auth_code:' . $authCode, [
            'client_id' => $clientId,
            'user_id' => $userId,
            'redirect_uri' => $redirectUri,
            'scope' => $scope,
            'nonce' => $nonce,
        ], now()->addMinutes(10));

        $params = [
            'code' => $authCode,
        ];
        
        if ($state) {
            $params['state'] = $state;
        }

        return redirect()->to($redirectUri . '?' . http_build_query($params));
    }

    /**
     * Token endpoint - OIDC compliant
     * Exchanges authorization code for access token
     */
    public function token(Request $request)
    {
        $grantType = $request->input('grant_type');
        
        if ($grantType === 'authorization_code') {
            return $this->handleAuthorizationCodeGrant($request);
        } elseif ($grantType === 'password') {
            return $this->handlePasswordGrant($request);
        } elseif ($grantType === 'client_credentials') {
            return $this->handleClientCredentialsGrant($request);
        } elseif ($grantType === 'refresh_token') {
            return $this->handleRefreshTokenGrant($request);
        }

        return response()->json(['error' => 'unsupported_grant_type'], 400);
    }

    /**
     * Handle authorization code grant
     */
    private function handleAuthorizationCodeGrant(Request $request)
    {
        $request->validate([
            'code' => 'required',
            'client_id' => 'required',
            'client_secret' => 'required',
            'redirect_uri' => 'required|url',
        ]);

        $code = $request->input('code');
        $clientId = $request->input('client_id');
        $clientSecret = $request->input('client_secret');
        $redirectUri = $request->input('redirect_uri');

        $client = Client::where('id', $clientId)
            ->where('secret', $clientSecret)
            ->first();

        if (!$client) {
            return response()->json(['error' => 'invalid_client'], 401);
        }

        $authData = Cache::get('auth_code:' . $code);
        
        if (!$authData || $authData['client_id'] != $clientId || $authData['redirect_uri'] != $redirectUri) {
            return response()->json(['error' => 'invalid_grant'], 400);
        }

        Cache::forget('auth_code:' . $code);

        $user = Auth::loginUsingId($authData['user_id']);
        
        if (!$user) {
            return response()->json(['error' => 'invalid_grant'], 400);
        }

        $scopes = explode(' ', $authData['scope']);
        $token = $user->createToken('SSO Access Token', $scopes)->accessToken;
        
        $nonce = $authData['nonce'] ?? null;
        $idToken = $this->generateIdToken($user, $clientId, $scopes, $nonce);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 86400,
            'scope' => $authData['scope'],
            'id_token' => $idToken,
        ]);
    }

    /**
     * Handle password grant
     */
    private function handlePasswordGrant(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
            'client_id' => 'required',
            'client_secret' => 'required',
        ]);

        $client = Client::where('id', $request->client_id)
            ->where('secret', $request->client_secret)
            ->first();

        if (!$client) {
            return response()->json(['error' => 'invalid_client'], 401);
        }

        $user = User::where('email', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'invalid_credentials'], 401);
        }

        $scopes = explode(' ', $request->input('scope', 'openid profile email'));
        $token = $user->createToken('SSO Access Token', $scopes)->accessToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 86400,
        ]);
    }

    /**
     * Handle client credentials grant
     */
    private function handleClientCredentialsGrant(Request $request)
    {
        $request->validate([
            'client_id' => 'required',
            'client_secret' => 'required',
        ]);

        $client = Client::where('id', $request->client_id)
            ->where('secret', $request->client_secret)
            ->first();

        if (!$client) {
            return response()->json(['error' => 'invalid_client'], 401);
        }

        return response()->json([
            'access_token' => Str::random(40),
            'token_type' => 'Bearer',
            'expires_in' => 86400,
        ]);
    }

    /**
     * Handle refresh token grant
     */
    private function handleRefreshTokenGrant(Request $request)
    {
        $tokenRequest = Request::create('/oauth/token', 'POST', $request->all());
        return app()->handle($tokenRequest);
    }

    /**
     * User Info endpoint - OIDC compliant
     * Returns user information based on access token
     */
    public function userInfo(Request $request)
    {
        $user = $request->user('api');

        if (!$user) {
            return response()->json(['error' => 'invalid_token'], 401);
        }

        $userInfo = [
            'sub' => (string) $user->ID_User,
        ];

        $token = $request->user('api')->token();
        $scopes = $token ? $token->scopes : ['openid', 'profile', 'email'];

        if (in_array('profile', $scopes)) {
            $userInfo['name'] = $user->Nom;
            $userInfo['given_name'] = $user->Prenom ?? '';
            $userInfo['family_name'] = $user->Nom ?? '';
            $userInfo['preferred_username'] = $user->login ?? $user->Email;
        }

        if (in_array('email', $scopes)) {
            $userInfo['email'] = $user->Email;
            $userInfo['email_verified'] = $user->email_verified_at !== null;
        }
        
        return response()->json($userInfo);
    }

    /**
     * JWK Set endpoint - OIDC compliant
     * Returns public keys for token verification
     */
    public function jwkSet()
    {
        $publicKeyPath = storage_path('oauth-public.key');
        
        if (!file_exists($publicKeyPath)) {
            return response()->json(['error' => 'Public key not found'], 500);
        }

        $publicKey = file_get_contents($publicKeyPath);
        
        $keyResource = openssl_pkey_get_public($publicKey);
        $keyDetails = openssl_pkey_get_details($keyResource);

        if (!$keyDetails || !isset($keyDetails['rsa'])) {
            return response()->json(['error' => 'Invalid public key'], 500);
        }

        $modulus = base64_encode($keyDetails['rsa']['n']);
        $exponent = base64_encode($keyDetails['rsa']['e']);

        return response()->json([
            'keys' => [
                [
                    'kty' => 'RSA',
                    'use' => 'sig',
                    'alg' => 'RS256',
                    'n' => rtrim(strtr($modulus, '+/', '-_'), '='),
                    'e' => rtrim(strtr($exponent, '+/', '-_'), '='),
                    'kid' => 'passport-' . md5($publicKey),
                ],
            ],
        ]);
    }

    /**
     * OIDC Discovery endpoint
     * Provides metadata about the OIDC provider
     */
    public function discovery()
    {
        $baseUrl = url('/');

        return response()->json([
            'issuer' => $baseUrl,
            'authorization_endpoint' => route('oidc.authorize'),
            'token_endpoint' => route('oidc.token'),
            'userinfo_endpoint' => route('oidc.userinfo'),
            'jwks_uri' => route('oidc.jwks'),
            'response_types_supported' => ['code', 'token'],
            'subject_types_supported' => ['public'],
            'id_token_signing_alg_values_supported' => ['RS256'],
            'scopes_supported' => ['openid', 'profile', 'email'],
            'token_endpoint_auth_methods_supported' => ['client_secret_basic', 'client_secret_post'],
            'claims_supported' => ['sub', 'name', 'email', 'email_verified', 'preferred_username'],
        ]);
    }

    private function generateIdToken(User $user, string $clientId, array $scopes, ?string $nonce = null): string
    {
        $privateKeyPath = storage_path('oauth-private.key');
        
        if (!file_exists($privateKeyPath)) {
            \Log::error('Private key not found at: ' . $privateKeyPath);
            throw new \Exception('Private key not found');
        }
        
        $privateKey = file_get_contents($privateKeyPath);
        
        $now = time();
        $payload = [
            'iss' => url('/'),
            'sub' => (string) $user->email,
            'aud' => $clientId,
            'exp' => $now + 86400,
            'iat' => $now,
        ];
        
        if ($nonce) {
            $payload['nonce'] = $nonce;
        }
        
        if (in_array('profile', $scopes)) {
            $payload['name'] = $user->firstname . ' ' . $user->name;
            $payload['given_name'] = $user->firstname ?? '';
            $payload['family_name'] = $user->name ?? '';
            $payload['preferred_username'] = $user->alias ?? $user->email;
        }
        
        if (in_array('email', $scopes)) {
            $payload['email'] = $user->email;
            $payload['email_verified'] = $user->email_verified_at !== null;
        }
        
        return JWT::encode($payload, $privateKey, 'RS256');
    }

    /**
     * Validate redirect URI against client configuration
     */
    private function isValidRedirectUri(Client $client, string $redirectUri): bool
    {
        if (empty($redirectUri)) {
            return true;
        }
        
        if (empty($client->redirect)) {
            return true;
        }
        
        if ($client->redirect === $redirectUri) {
            return true;
        }
        
        if (Str::startsWith($redirectUri, rtrim($client->redirect, '/*'))) {
            return true;
        }
        
        return false;
    }
}
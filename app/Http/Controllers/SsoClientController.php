<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use App\Models\User;

class SsoClientController extends Controller
{
    private $clientId;
    private $clientSecret;
    private $redirectUri;
    private $authorizationUrl;
    private $tokenUrl;
    private $userInfoUrl;
    private $jwksUrl;
    private function setProvider($providerName)
    {
        $provider = config("services.sso.{$providerName}");

        if (!$provider) {
            throw new \Exception("Provider '{$providerName}' non configuré");
        }

        $this->clientId = $provider['id'];
        $this->clientSecret = $provider['secret'];
        $this->redirectUri = config('services.sso.redirect_uri');
        $this->authorizationUrl = $provider['authorization'];
        $this->tokenUrl = $provider['token'];
        $this->userInfoUrl = $provider['userinfo'];
        $this->jwksUrl = $provider['jwks'];

        if (!$this->clientId || !$this->clientSecret || !$this->authorizationUrl || !$this->tokenUrl || !$this->userInfoUrl || !$this->jwksUrl) {
            throw new \Exception("Provider '{$providerName}' incomplet dans la configuration");
        }
    }

    public function redirectToProvider(Request $request, $provider)
    {
        if(!$provider) {
            return redirect('/')->with('error', 'Fournisseur SSO non spécifié');
        }

        $this->setProvider($provider);

        $state = Str::random(40);
        $nonce = Str::random(40);
        Session::put('sso_state', $state);
        Session::put('sso_nonce', $nonce);
        Session::put('sso_provider', $provider);

        $params = http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email',
            'state' => $state,
            'nonce' => $nonce,
        ]);

        $authUrl = $this->authorizationUrl . '?' . $params;

        return redirect()->away($authUrl);
    }

    public function handleCallback(Request $request)
    {
        $provider = Session::get('sso_provider');

        if (!$provider) {
            return redirect('/')
                ->with('error', 'Fournisseur SSO non trouvé');
        }

        $this->setProvider($provider);
        
        $state = Session::get('sso_state');
        if (!$state || $state !== $request->input('state')) {
            return redirect('/')
                ->with('error', 'Échec de la vérification de sécurité SSO');
        }

        $code = $request->input('code');
        if (!$code) {
            return redirect('/')
                ->with('error', 'Code d\'autorisation manquant');
        }

        try {
            $tokenResponse = $this->getAccessToken($code);

            if (!isset($tokenResponse['access_token'])) {
                throw new \Exception('Access token non reçu');
            }

            if (!isset($tokenResponse['id_token'])) {
                throw new \Exception('ID token non reçu');
            }

            $accessToken = $tokenResponse['access_token'];
            $this->assertNonce($tokenResponse['id_token']);

            $id_token = $tokenResponse['id_token'];
            $email = $this->decodeJwtPayload($id_token)['email'] ?? null;

            $user = User::where('email', $email)->first();

            if(!$user) {
                return redirect('/')
                    ->with('error', 'Utilisateur non autorisé à se connecter via SSO');
            }

            Auth::login($user);

            Session::put('sso_access_token', $accessToken);

            return redirect()->route($user->homeRoute())
                ->with('success', 'Connexion réussie via SSO');
        } catch (\Exception $e) {
            return redirect('/')
                ->with('error', 'Erreur lors de la connexion SSO: ' . $e->getMessage());
        }
    }

    private function getAccessToken($code)
    {
        $response = Http::asForm()->post($this->tokenUrl, [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
        ]);

        if ($response->failed()) {
            throw new \Exception('Échec de l\'obtention du token: ' . $response->body());
        }

        return $response->json();
    }

    public function logout(Request $request)
    {
        $accessToken = Session::get('sso_access_token');

        Auth::logout();
        Session::flush();

        return redirect('/')
            ->with('success', 'Déconnexion réussie');
    }

    private function refreshAccessToken($refreshToken)
    {
        $response = Http::asForm()->post($this->tokenUrl, [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        if ($response->failed()) {
            throw new \Exception('Échec du refresh du token');
        }

        return $response->json();
    }

    private function assertNonce($idToken)
    {
        $expectedNonce = Session::get('sso_nonce');

        if (!$expectedNonce) {
            throw new \Exception('Nonce manquant en session');
        }

        $payload = $this->decodeJwtPayload($idToken);

        if (!isset($payload['nonce']) || !hash_equals($expectedNonce, $payload['nonce'])) {
            throw new \Exception('Nonce invalide');
        }

        Session::forget('sso_nonce');
    }

    private function decodeJwtPayload($jwt)
    {
        $parts = explode('.', $jwt);

        if (count($parts) < 2) {
            throw new \Exception('ID token mal formé');
        }

        $payload = $this->base64UrlDecode($parts[1]);
        $decoded = json_decode($payload, true);

        if (!is_array($decoded)) {
            throw new \Exception('Payload ID token invalide');
        }

        return $decoded;
    }

    private function base64UrlDecode($data)
    {
        $data = strtr($data, '-_', '+/');
        $padding = strlen($data) % 4;

        if ($padding > 0) {
            $data .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($data, true);

        if ($decoded === false) {
            throw new \Exception('Décodage base64url échoué');
        }

        return $decoded;
    }
}
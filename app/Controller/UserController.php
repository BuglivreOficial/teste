<?php

namespace App\Controller;

use Core\SupabaseClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Respect\Validation\Validator as v;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller de Gerenciamento de Usuários
 *
 * Rotas:
 * - POST /user              Retorna dados do usuário autenticado (Authorization: Bearer <token>)
 * - POST /user/update       Atualiza email/senha/metadata do usuário autenticado
 * - POST /logout            Realiza logout do usuário (revoga sessão atual)
 * - POST /refresh-token     Gera novo access_token a partir do refresh_token
 */
class UserController
{
    private SupabaseClient $client;

    private Response $response;

    public function __construct()
    {
        $this->client = new SupabaseClient();
        $this->response = new Response();
    }

    /**
     * Helper: extrai Bearer token do header Authorization.
     */
    private function extractBearerToken(Request $request): ?string
    {
        $authHeader = $request->headers->get('Authorization');
        if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    /**
     * Retorna dados do usuário autenticado.
     * Header: Authorization: Bearer <token>
     */
    public function me(): JsonResponse
    {
        $request = Request::createFromGlobals();
        $token = $this->extractBearerToken($request);
        if (!$token) {
            return new JsonResponse(['error' => 'Token não fornecido'], 401);
        }

        $auth = $this->client->getService()->createAuth();
        try {
            $user = $auth->getUser($token);
            return new JsonResponse(['user' => $user], 200);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $auth->getError() ?? 'Falha ao obter usuário autenticado',
            ], 401);
        }
    }

    /**
     * Atualiza email/senha/metadata do usuário autenticado.
     * Header: Authorization: Bearer <token>
     * Body JSON: { "email"?: string, "password"?: string, "metadata"?: object }
     */
    public function update(): JsonResponse
    {
        $request = Request::createFromGlobals();
        $token = $this->extractBearerToken($request);
        if (!$token) {
            return new JsonResponse(['error' => 'Token não fornecido'], 401);
        }

        $payload = json_decode($request->getContent(), true) ?? [];
        $email = $payload['email'] ?? null;
        $password = $payload['password'] ?? null;
        $metadata = (isset($payload['metadata']) && is_array($payload['metadata'])) ? $payload['metadata'] : [];

        $errors = [];
        if (!is_null($email) && !v::email()->validate($email)) {
            $errors['email'] = 'E-mail inválido';
        }
        if (!is_null($password) && !v::stringType()->length(6, null)->validate($password)) {
            $errors['password'] = 'Senha deve ter ao menos 6 caracteres';
        }
        if (!empty($errors)) {
            return new JsonResponse(['errors' => $errors], 422);
        }

        $auth = $this->client->getService()->createAuth();
        try {
            $data = $auth->updateUser($token, $email, $password, $metadata);
            return new JsonResponse(['user' => $data], 200);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $auth->getError() ?? 'Falha ao atualizar dados do usuário',
            ], 400);
        }
    }

    /**
     * Logout: revoga sessão atual.
     * Header: Authorization: Bearer <token>
     */
    public function logout(): JsonResponse
    {
        $request = Request::createFromGlobals();
        $token = $this->extractBearerToken($request);
        if (!$token) {
            return new JsonResponse(['error' => 'Token não fornecido'], 401);
        }

        $auth = $this->client->getService()->createAuth();
        try {
            $auth->logout($token);
            return new JsonResponse(['message' => 'Logout realizado com sucesso'], 200);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $auth->getError() ?? 'Falha ao realizar logout',
            ], 400);
        }
    }

    /**
     * Gera novo access_token a partir de refresh_token.
     * Body JSON: { "refresh_token": string }
     */
    public function refreshToken()
    {
        $request = Request::createFromGlobals();
        $payload = json_decode($request->getContent(), true) ?? [];
        $refreshToken = $payload['refresh_token'] ?? '';

        if (!v::stringType()->length(1, null)->validate($refreshToken)) {
            $this->response->setContent(json_encode(['error' => 'refresh_token é obrigatório']));
            $this->response->setStatusCode(422);
            $this->response->headers->set('Content-Type', 'application/json');
            $this->response->send();
        }

        $auth = $this->client->getService()->createAuth();
        try {
            $auth->signInWithRefreshToken($refreshToken);
            $data = $auth->data();

            $this->response->setContent(json_encode([
                'access_token' => $data->access_token ?? null,
                'refresh_token' => $data->refresh_token ?? null,
                'token_type' => $data->token_type ?? 'bearer',
                'expires_in' => $data->expires_in ?? null,
                'user' => $data->user ?? null,
            ]));
            $this->response->setStatusCode(200);
            $this->response->headers->set('Content-Type', 'application/json');
            $this->response->send();
        } catch (\Exception $e) {
            $this->response->setContent(json_encode(['error' => $auth->getError() ?? 'Falha ao renovar token']));
            $this->response->setStatusCode(401);
            $this->response->headers->set('Content-Type', 'application/json');
            $this->response->send();
        }
    }
}
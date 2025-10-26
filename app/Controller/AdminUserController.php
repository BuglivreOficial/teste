<?php

namespace App\Controller;

use Core\SupabaseClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Respect\Validation\Validator as v;

/**
 * Controller Admin para gerenciamento de usuários (Supabase Auth Admin)
 *
 * IMPORTANTE:
 * - Estas rotas exigem o uso da chave `service_role` no `SUPABASE_KEY`.
 * - A autorização é feita com `Authorization: Bearer <service_role_key>`.
 *
 * Rotas:
 * - POST /admin/users            Lista usuários com paginação
 * - POST /admin/users/get        Busca usuário por ID
 * - POST /admin/users/update     Atualiza email/senha/metadata/app_metadata/ban_duration
 * - POST /admin/users/delete     Deleta usuário por ID
 * - POST /admin/users/ban        Bane usuário (define ban_duration)
 * - POST /admin/users/unban      Remove ban (ban_duration = 'none')
 */
class AdminUserController
{
    private SupabaseClient $client;

    public function __construct()
    {
        $this->client = new SupabaseClient();
    }

    /**
     * Helper para garantir que Authorization contenha a service_role key.
     */
    private function withServiceRoleAuth(): void
    {
        $service = $this->client->getService();
        $key = $_ENV['SUPABASE_KEY'] ?? '';
        if (!$key) {
            throw new \RuntimeException('SUPABASE_KEY não configurada');
        }
        $service->setHeader('Authorization', 'Bearer ' . $key);
    }

    /**
     * Lista usuários com paginação.
     * Body JSON: { "page"?: int, "per_page"?: int }
     */
    public function list(): JsonResponse
    {
        $request = Request::createFromGlobals();
        $payload = json_decode($request->getContent(), true) ?? [];
        $page = max(1, (int)($payload['page'] ?? 1));
        $perPage = max(1, min(1000, (int)($payload['per_page'] ?? 50)));

        try {
            $this->withServiceRoleAuth();
            $service = $this->client->getService();
            $uri = $service->getUriBase('auth/v1/admin/users?page=' . $page . '&per_page=' . $perPage);
            $data = $service->executeHttpRequest('GET', $uri, [ 'headers' => $service->getHeaders() ]);
            return new JsonResponse([ 'page' => $page, 'per_page' => $perPage, 'data' => $data ], 200);
        } catch (\Exception $e) {
            return new JsonResponse([ 'error' => $this->client->getService()->getError() ?? $e->getMessage() ], 400);
        }
    }

    /**
     * Busca usuário por ID.
     * Body JSON: { "id": string }
     */
    public function get(): JsonResponse
    {
        $request = Request::createFromGlobals();
        $payload = json_decode($request->getContent(), true) ?? [];
        $id = $payload['id'] ?? '';

        if (!v::stringType()->length(1, null)->validate($id)) {
            return new JsonResponse([ 'error' => 'id é obrigatório' ], 422);
        }

        try {
            $this->withServiceRoleAuth();
            $service = $this->client->getService();
            $uri = $service->getUriBase('auth/v1/admin/users/' . urlencode($id));
            $data = $service->executeHttpRequest('GET', $uri, [ 'headers' => $service->getHeaders() ]);
            return new JsonResponse([ 'user' => $data ], 200);
        } catch (\Exception $e) {
            return new JsonResponse([ 'error' => $this->client->getService()->getError() ?? $e->getMessage() ], 400);
        }
    }

    /**
     * Atualiza dados do usuário por ID.
     * Body JSON: {
     *   "id": string,
     *   "email"?: string,
     *   "password"?: string,
     *   "user_metadata"?: object,
     *   "app_metadata"?: object,
     *   "ban_duration"?: string // ex: "24h" ou "none"
     * }
     */
    public function update(): JsonResponse
    {
        $request = Request::createFromGlobals();
        $payload = json_decode($request->getContent(), true) ?? [];
        $id = $payload['id'] ?? '';
        $email = $payload['email'] ?? null;
        $password = $payload['password'] ?? null;
        $userMetadata = (isset($payload['user_metadata']) && is_array($payload['user_metadata'])) ? $payload['user_metadata'] : [];
        $appMetadata  = (isset($payload['app_metadata']) && is_array($payload['app_metadata'])) ? $payload['app_metadata'] : [];
        $banDuration  = $payload['ban_duration'] ?? null;

        $errors = [];
        if (!v::stringType()->length(1, null)->validate($id)) {
            $errors['id'] = 'id é obrigatório';
        }
        if (!is_null($email) && !v::email()->validate($email)) {
            $errors['email'] = 'E-mail inválido';
        }
        if (!is_null($password) && !v::stringType()->length(6, null)->validate($password)) {
            $errors['password'] = 'Senha deve ter ao menos 6 caracteres';
        }
        if (!empty($errors)) {
            return new JsonResponse(['errors' => $errors], 422);
        }

        $body = [];
        if (!is_null($email)) { $body['email'] = $email; }
        if (!is_null($password)) { $body['password'] = $password; }
        if (!empty($userMetadata)) { $body['user_metadata'] = $userMetadata; }
        if (!empty($appMetadata)) { $body['app_metadata'] = $appMetadata; }
        if (!is_null($banDuration)) { $body['ban_duration'] = $banDuration; }

        try {
            $this->withServiceRoleAuth();
            $service = $this->client->getService();
            $uri = $service->getUriBase('auth/v1/admin/users/' . urlencode($id));
            $data = $service->executeHttpRequest('PUT', $uri, [
                'headers' => $service->getHeaders(),
                'body' => json_encode($body)
            ]);
            return new JsonResponse(['user' => $data], 200);
        } catch (\Exception $e) {
            return new JsonResponse([ 'error' => $this->client->getService()->getError() ?? $e->getMessage() ], 400);
        }
    }

    /**
     * Deleta usuário por ID.
     * Body JSON: { "id": string }
     */
    public function delete(): JsonResponse
    {
        $request = Request::createFromGlobals();
        $payload = json_decode($request->getContent(), true) ?? [];
        $id = $payload['id'] ?? '';

        if (!v::stringType()->length(1, null)->validate($id)) {
            return new JsonResponse([ 'error' => 'id é obrigatório' ], 422);
        }

        try {
            $this->withServiceRoleAuth();
            $service = $this->client->getService();
            $uri = $service->getUriBase('auth/v1/admin/users/' . urlencode($id));
            $service->executeHttpRequest('DELETE', $uri, [ 'headers' => $service->getHeaders() ]);
            return new JsonResponse(['message' => 'Usuário deletado com sucesso'], 200);
        } catch (\Exception $e) {
            return new JsonResponse([ 'error' => $this->client->getService()->getError() ?? $e->getMessage() ], 400);
        }
    }

    /**
     * Bane usuário por duração especificada (ex: '24h', '7d', 'none').
     * Body JSON: { "id": string, "duration": string }
     */
    public function ban(): JsonResponse
    {
        $request = Request::createFromGlobals();
        $payload = json_decode($request->getContent(), true) ?? [];
        $id = $payload['id'] ?? '';
        $duration = $payload['duration'] ?? '24h';

        if (!v::stringType()->length(1, null)->validate($id)) {
            return new JsonResponse([ 'error' => 'id é obrigatório' ], 422);
        }

        try {
            $this->withServiceRoleAuth();
            $service = $this->client->getService();
            $uri = $service->getUriBase('auth/v1/admin/users/' . urlencode($id));
            $data = $service->executeHttpRequest('PUT', $uri, [
                'headers' => $service->getHeaders(),
                'body' => json_encode([ 'ban_duration' => $duration ])
            ]);
            return new JsonResponse(['user' => $data, 'message' => 'Usuário banido'], 200);
        } catch (\Exception $e) {
            return new JsonResponse([ 'error' => $this->client->getService()->getError() ?? $e->getMessage() ], 400);
        }
    }

    /**
     * Remove ban do usuário (ban_duration = 'none').
     * Body JSON: { "id": string }
     */
    public function unban(): JsonResponse
    {
        $request = Request::createFromGlobals();
        $payload = json_decode($request->getContent(), true) ?? [];
        $id = $payload['id'] ?? '';

        if (!v::stringType()->length(1, null)->validate($id)) {
            return new JsonResponse([ 'error' => 'id é obrigatório' ], 422);
        }

        try {
            $this->withServiceRoleAuth();
            $service = $this->client->getService();
            $uri = $service->getUriBase('auth/v1/admin/users/' . urlencode($id));
            $data = $service->executeHttpRequest('PUT', $uri, [
                'headers' => $service->getHeaders(),
                'body' => json_encode([ 'ban_duration' => 'none' ])
            ]);
            return new JsonResponse(['user' => $data, 'message' => 'Ban removido'], 200);
        } catch (\Exception $e) {
            return new JsonResponse([ 'error' => $this->client->getService()->getError() ?? $e->getMessage() ], 400);
        }
    }
}
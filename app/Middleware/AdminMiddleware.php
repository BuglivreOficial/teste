<?php

namespace App\Middleware;

use Core\SupabaseClient;
use Symfony\Component\HttpFoundation\Response;


use Pecee\Http\Request;
use Pecee\Http\Middleware\IMiddleware;

class AdminMiddleware implements IMiddleware {
    private Response $response;

    private SupabaseClient $supabaseClient;

    public function __construct() {
        $this->response = new Response();
        $this->supabaseClient = new SupabaseClient();
    }
    public function handle(Request $request): void
    {
        $authHeader = $request->getHeader('Authorization');

        $token = null;
        if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $m)) {
            $token = trim($m[1]);
        }

        if (!$token) {
            $this->unauthorizedResponse();
            return;
        }

        if (!$this->isAdmin($token)) {
            $this->unauthorizedResponse();
            return;
        }
        // Autorizado: não é necessário chamar next(); o router continua automaticamente
    }
    private function isAdmin($token)
    {
        $service = $this->supabaseClient->getService();
        $auth = $service->createAuth();
        try {
            $user = $auth->getUser($token);
        } catch (\Exception $e) {
            return false;
        }

        // Extrai o ID do usuário retornado pelo Supabase
        $userId = null;
        if (is_object($user)) {
            $userId = $user->id ?? ($user->user->id ?? null);
        } elseif (is_array($user)) {
            $userId = $user['id'] ?? ($user['user']['id'] ?? null);
        }
        if (!$userId) {
            return false;
        }

        // Verifica apenas na tabela profiles (flexível): usa is_admin, role/role_name ou role_id
        try {
            // Garante que a consulta ao PostgREST use o token do usuário para respeitar RLS
            $service->setHeader('Authorization', 'Bearer ' . $token);
            $qb = $this->supabaseClient->getQueryBuilder();
            $profiles = $qb->from('profiles')
                ->select('*')
                ->where('id', 'eq.' . $userId)
                ->execute()
                ->getResult();

            if (empty($profiles)) {
                return false; // sem perfil -> tratar como não-admin
            }

            $rawProfile = $profiles[0];

            // Normaliza para array para acesso uniforme
            $profile = is_array($rawProfile) ? $rawProfile : (array)$rawProfile;

            // 1) is_admin boolean
            if (isset($profile['is_admin'])) {
                return (bool)$profile['is_admin'] === true;
            }

            // 2) role string: role_name ou role
            $roleStr = strtolower(trim($profile['role_name'] ?? ($profile['role'] ?? '')));
            if ($roleStr !== '') {
                // Regra fixa: bloquear 'user' e 'vip'; permitir demais
                return !in_array($roleStr, ['user','vip'], true);
            }

            // 3) role_id inteiro
            if (isset($profile['role_id'])) {
                $userRoleId = (int)($_ENV['ROLE_USER_ID'] ?? 1); // por padrão, 'user' é id 1
                return ((int)$profile['role_id'] !== $userRoleId);
            }

            // Sem informações de role -> negar
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function unauthorizedResponse()
    {
        $this->response->setContent(json_encode([
            'error' => 'Acesso negado. Usuário não é administrador.'
        ]));
        $this->response->setStatusCode(403);
        $this->response->headers->set('Content-Type','application/json');
        $this->response->send();
        // Interrompe execução para evitar que o router continue processando a rota
        exit;
    }
}

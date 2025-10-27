<?php

namespace App\Controller;

use Core\SupabaseClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Respect\Validation\Validator as v;

/**
 * Controller de Autenticação (MVC)
 *
 * Rotas:
 * - POST /login           Autentica usuário (email/senha)
 * - POST /register        Cria usuário (email/senha + metadata opcional)
 * - POST /reset-password  Envia link de recuperação de senha
 * - POST /profile         Retorna dados do usuário autenticado (Authorization: Bearer <token>)
 * - GET  /auth/callback   Página de retorno após verificação de e-mail
 * - GET  /auth/v1/verify  Alias para compatibilidade com links do Supabase
 *
 * Bibliotecas:
 * - pecee/simple-router         (roteamento)
 * - symfony/http-foundation     (Request/Response)
 * - respect/validation          (validação)
 * - rafaelwendel/phpsupabase    (integração com Supabase)
 * - vlucas/phpdotenv            (variáveis de ambiente)
 */
class AuthController
{
    private SupabaseClient $client;
    private Response $response;

    public function __construct()
    {
        $this->client = new SupabaseClient();
        $this->response = new Response();
    }

    /**
     * Autenticação com email e senha.
     * Body JSON: { "email": string, "password": string }
     */
    public function login()
    {
        $request = Request::createFromGlobals();
        $payload = json_decode($request->getContent(), true) ?? [];
        $email = $payload['email'] ?? '';
        $password = $payload['password'] ?? '';

        $errors = [];
        if (!v::email()->validate($email)) {
            $errors['email'] = 'E-mail inválido';
        }
        if (!v::stringType()->length(6, null)->validate($password)) {
            $errors['password'] = 'Senha deve ter ao menos 6 caracteres';
        }
        if (!empty($errors)) {
            $this->response->setContent(json_encode([
                'errors' => $errors
            ]));
            $this->response->setStatusCode(422);
            $this->response->headers->set('Content-Type', 'application/json');
            $this->response->send();
        }

        $auth = $this->client->getService()->createAuth();

        try {
            $auth->signInWithEmailAndPassword($email, $password);
            $data = $auth->data();

            $this->response->setContent(json_encode([
                'access_token' => $data->access_token ?? null,
                'refresh_token' => $data->refresh_token ?? null,
                'token_type' => $data->token_type ?? 'bearer',
                'expires_in' => $data->expires_in ?? null,
                'user' => $data->user ?? null
            ]));
            $this->response->setStatusCode(200);
            $this->response->headers->set('Content-Type', 'application/json');
            $this->response->send();
        } catch (\Exception $e) {
            $this->response->setContent(json_encode([
                'error' => $auth->getError() ?? 'Falha ao autenticar'
            ]));
            $this->response->setStatusCode(401);
            $this->response->headers->set('Content-Type', 'application/json');
            $this->response->send();
        }
    }

    /**
     * Registro de novo usuário.
     * Body JSON: { "email": string, "password": string, "metadata": object? }
     */
    public function register()
    {
        $request = Request::createFromGlobals();
        $payload = json_decode($request->getContent(), true) ?? [];
        $email = $payload['email'] ?? '';
        $password = $payload['password'] ?? '';
        $metadata = (isset($payload['metadata']) && is_array($payload['metadata'])) ? $payload['metadata'] : [];

        $errors = [];
        if (!v::email()->validate($email)) {
            $errors['email'] = 'E-mail inválido';
        }
        if (!v::stringType()->length(6, null)->validate($password)) {
            $errors['password'] = 'Senha deve ter ao menos 6 caracteres';
        }
        if (!empty($errors)) {
            $this->response->setContent(json_encode(['errors' => $errors]));
            $this->response->setStatusCode(422);
            $this->response->headers->set('Content-Type', 'application/json');

            $this->response->send();
            //return;
            exit;
        }

        $auth = $this->client->getService()->createAuth();

        try {
            // Registro usando o método padrão da biblioteca
            $auth->createUserWithEmailAndPassword($email, $password, $metadata);
            $data = $auth->data();

            // Mensagem diferente para desenvolvimento
            $message = ($_ENV['DISABLE_EMAIL_CONFIRMATION'] === 'true')
                ? 'Usuário criado com sucesso (desenvolvimento - confirmação de email desabilitada)'
                : 'Usuário criado! Um link de confirmação foi enviado por e-mail.';

            $this->response->setContent(json_encode([
                'message' => $message,
                'email' => $data->email ?? $email,
                'user' => $data
            ]));
            $this->response->setStatusCode(201);
            $this->response->headers->set('Content-Type', 'application/json');

            $this->response->send();
        } catch (\Exception $e) {
            $this->response->setContent(json_encode([
                'error' => $auth->getError() ?? $e->getMessage()
            ]));
            $this->response->setStatusCode(400);
            $this->response->headers->set('Content-Type', 'application/json');

            $this->response->send();
        }
    }

    /**
     * Reset de senha: envia link de recuperação para o e-mail informado.
     * Body JSON: { "email": string }
     */
    public function resetPassword(): JsonResponse
    {
        $request = Request::createFromGlobals();
        $payload = json_decode($request->getContent(), true) ?? [];
        $email = $payload['email'] ?? '';

        if (!v::email()->validate($email)) {
            return new JsonResponse(['error' => 'E-mail inválido'], 422);
        }

        $auth = $this->client->getService()->createAuth();

        try {
            $auth->recoverPassword($email);
            return new JsonResponse([
                'message' => 'Se o e-mail existir, um link de recuperação foi enviado.',
                'email' => $email,
            ], 200);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $auth->getError() ?? 'Falha ao solicitar recuperação de senha',
            ], 400);
        }
    }

    /**
     * Perfil do usuário autenticado.
     * Header: Authorization: Bearer <token>
     */
    public function profile(): JsonResponse
    {
        $request = Request::createFromGlobals();
        $authHeader = $request->headers->get('Authorization');
        $token = null;

        if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $m)) {
            $token = trim($m[1]);
        }

        if (!$token) {
            return new JsonResponse(['error' => 'Token não fornecido'], 401);
        }

        $auth = $this->client->getService()->createAuth();

        try {
            $user = $auth->getUser($token);
            return new JsonResponse(['user' => $user], 200);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $auth->getError() ?? 'Falha ao obter perfil do usuário',
            ], 401);
        }
    }

    // Callback de verificação: renderiza uma página simples para evitar 404 após confirmação
    public function verifyCallback(): Response
    {
        $request = Request::createFromGlobals();
        $type = $request->query->get('type', 'signup');
        $html = '<!doctype html><html><head><meta charset="utf-8"><title>Confirmação de e-mail</title></head><body style="font-family:sans-serif;padding:24px"><h2>E-mail confirmado</h2><p>Seu e-mail foi verificado ('
            . htmlspecialchars($type, ENT_QUOTES, 'UTF-8')
            . '). Você já pode acessar sua conta.</p></body></html>';
        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }
}

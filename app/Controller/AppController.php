<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Core\SupabaseClient;

/**
 * Controller de Status do Aplicativo
 *
 * Rotas:
 * - GET /app/version      Retorna a versão atual do app Android
 * - GET /app/maintenance  Retorna se o app está em manutenção
 *
 * Fonte de dados:
 * - Variáveis de ambiente (.env):
 *   - APP_ANDROID_VERSION="x.y.z"
 *   - APP_MAINTENANCE="true|false"
 *   - APP_MAINTENANCE_MESSAGE="texto opcional"
 *
 * Observação:
 * - Não é necessário criar tabela no Supabase para estas rotas.
 *   Elas são configuradas via .env. Se desejar gestão remota, podemos
 *   futuramente ler de uma tabela no Supabase.
 */
class AppController
{
    private Response $response;

    private SupabaseClient $supabaseClient;

    public function __construct() {
        $this->response = new Response();
        $this->supabaseClient = new SupabaseClient();
    }
    /**
     * GET /app/version
     * Retorna a versão atual do aplicativo Android.
     */
    public function version()    {
        $version = $_ENV['APP_ANDROID_VERSION'] ?? null;
        $this->response->setContent(json_encode([
            'platform' => 'android',
            'version' => $version,
            'source' => 'env'
        ]));
        $this->response->headers->set('Content-Type', 'application/json');
        $this->response->setStatusCode(200);
        $this->response->send();
    }

    /**
     * GET /app/maintenance
     * Retorna se o aplicativo está em manutenção e mensagem opcional.
     */
    public function maintenance()
    {
        $request = Request::createFromGlobals();
        $payload = json_decode($request->getContent(), true) ?? [];
        $package = $payload['package'] ?? '';
        $query = $this->supabaseClient->getQueryBuilder()
            ->from('maintenance')
            ->select('*')
            ->where('package', 'eq.' . $package);
        $result = $query->execute()->getResult();
        if (empty($result)) {
            $this->response->setContent(json_encode([
                'error' => 'Pacote não encontrado'
            ]));
            $this->response->setStatusCode(404);
            $this->response->headers->set('Content-Type', 'application/json');
            $this->response->send();
            return;
        }
        $this->response->setContent(json_encode($result[0]));
        $this->response->setStatusCode(200);
        $this->response->headers->set('Content-Type', 'application/json');
        $this->response->send();
    }
}
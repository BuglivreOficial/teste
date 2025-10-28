<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
    public function __construct() {
        $this->response = new Response();
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
        $maintenance = filter_var($_ENV['APP_MAINTENANCE'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
        $message = $_ENV['APP_MAINTENANCE_MESSAGE'] ?? null;

        if ($maintenance === false) {
            $this->response->setContent(json_encode([
                'maintenance' => $maintenance,
                'source' => 'env'
            ]));
            $this->response->headers->set('Content-Type', 'application/json');
            $this->response->setStatusCode(200);
            $this->response->send();
        }

        $this->response->setContent(json_encode([
            'maintenance' => $maintenance,
            'message' => $message,
            'source' => 'env'
        ]));
        $this->response->headers->set('Content-Type', 'application/json');
        $this->response->setStatusCode(200);
        $this->response->send();
    }
}
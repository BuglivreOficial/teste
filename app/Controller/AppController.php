<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

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
    /**
     * GET /app/version
     * Retorna a versão atual do aplicativo Android.
     */
    public function version(): JsonResponse
    {
        $version = $_ENV['APP_ANDROID_VERSION'] ?? null;
        return new JsonResponse([
            'platform' => 'android',
            'version' => $version,
            'source' => 'env',
        ], 200);
    }

    /**
     * GET /app/maintenance
     * Retorna se o aplicativo está em manutenção e mensagem opcional.
     */
    public function maintenance(): JsonResponse
    {
        $maintenance = filter_var($_ENV['APP_MAINTENANCE'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
        $message = $_ENV['APP_MAINTENANCE_MESSAGE'] ?? null;

        return new JsonResponse([
            'maintenance' => $maintenance,
            'message' => $message,
            'source' => 'env',
        ], 200);
    }
}
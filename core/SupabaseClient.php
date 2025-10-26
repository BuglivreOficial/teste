<?php

namespace Core;

use PHPSupabase\Service;

/**
 * Cliente Supabase centralizado
 *
 * Responsável por instanciar e fornecer o Service da biblioteca
 * rafaelwendel/phpsupabase usando as variáveis de ambiente.
 */
class SupabaseClient
{
    private Service $service;

    /**
     * Construtor: lê SUPABASE_URL e SUPABASE_KEY do .env
     * e inicializa o Service.
     */
    public function __construct()
    {
        $url = $_ENV['SUPABASE_URL'] ?? '';
        $key = $_ENV['SUPABASE_KEY'] ?? '';

        $this->service = new Service($key, $url);
    }

    /**
     * Retorna a instância de Service para uso em Auth/Database.
     */
    public function getService(): Service
    {
        return $this->service;
    }
}
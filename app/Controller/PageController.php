<?php

namespace App\Controller;

use App\Service\SupabaseClient;
use Symfony\Component\HttpFoundation\Response;

class PageController
{
    public Response $response;

    public function __construct() {
        $this->response = new Response();
    }
    public function notFound()
    {
        $this->response->setContent(json_encode([
            'error' => 'Página não encontrada'
        ]));
        $this->response->setStatusCode(404);
        $this->response->headers->set('Content-Type', 'application/json');

        $this->response->send();
    }

    public function forbidden() {
        $this->response->setContent(json_encode([
            'error' => 'Acesso negado'
        ]));
        $this->response->setStatusCode(403);
        $this->response->headers->set('Content-Type', 'application/json');

        $this->response->send();
    }
}
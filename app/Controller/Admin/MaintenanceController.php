<?php
namespace App\Controller\Admin;

use Core\SupabaseClient;
use Symfony\Component\HttpFoundation\Response;

class MaintenanceController {
    private Response $response;

    private SupabaseClient $supabaseClient;

    public function __construct() {
        $this->response = new Response();
        $this->supabaseClient = new SupabaseClient();
    }

    public function update() {


    }

    public function get() {
        $query = $this->supabaseClient->getQueryBuilder()
            ->from('maintenance')
            ->select('*');
        $response = $query->execute()->getResult();
        $this->response->setContent(json_encode($response));
        $this->response->setStatusCode(200);
        $this->response->headers->set('Content-Type', 'application/json');
        $this->response->send();
    }
}

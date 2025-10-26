<?php

require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use Pecee\SimpleRouter\SimpleRouter;

// Carrega variáveis de ambiente do arquivo .env
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

/* Carrega arquivo de rotas */
require __DIR__ . '/router/api.php';

// Inicia o roteamento
SimpleRouter::start();
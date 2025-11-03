<?php
use Pecee\SimpleRouter\SimpleRouter;
use Pecee\Http\Request;
use App\Controller\AuthController;
use App\Controller\UserController;
use App\Controller\AdminUserController;
use App\Controller\PageController;
use App\Controller\AppController;


use App\Controller\Admin\MaintenanceController;

// Rotas da API de autenticação
SimpleRouter::post('/login', [AuthController::class, 'login']);
SimpleRouter::post('/register', [AuthController::class, 'register']);
SimpleRouter::post('/reset-password', [AuthController::class, 'resetPassword']);
SimpleRouter::post('/profile', [AuthController::class, 'profile']);

// Rotas de gerenciamento de usuários
SimpleRouter::post('/user', [UserController::class, 'me']);
SimpleRouter::post('/user/update', [UserController::class, 'update']);
SimpleRouter::post('/logout', [UserController::class, 'logout']);
SimpleRouter::post('/refresh-token', [UserController::class, 'refreshToken']);

// Rotas de administração de usuários
SimpleRouter::post('/admin/users', [AdminUserController::class, 'list']);
SimpleRouter::post('/admin/users/get', [AdminUserController::class, 'get']);
SimpleRouter::post('/admin/users/update', [AdminUserController::class, 'update']);
SimpleRouter::post('/admin/users/delete', [AdminUserController::class, 'delete']);
SimpleRouter::post('/admin/users/ban', [AdminUserController::class, 'ban']);
SimpleRouter::post('/admin/users/unban', [AdminUserController::class, 'unban']);

// Callback de verificação de e-mail (redirecionamento do Supabase)
SimpleRouter::get('/auth/callback', [AuthController::class, 'verifyCallback']);
SimpleRouter::get('/auth/v1/verify', [AuthController::class, 'verifyCallback']);

// Rotas de status do aplicativo
SimpleRouter::get('/app/version', [AppController::class, 'version']);
// Rotas de páginas de erro
SimpleRouter::get('/not-found', [PageController::class, 'notFound']);
SimpleRouter::get('/forbidden', [PageController::class, 'forbidden']);

// Tratamento de erros globais
SimpleRouter::error(function(Request $request, \Exception $exception) {

    switch($exception->getCode()) {
        // Page not found
        case 404:
            SimpleRouter::response()->redirect('/not-found');
            break;
        // Forbidden
        //case 403:
        //    SimpleRouter::response()->redirect('/forbidden');
        //    break;
        default:
            // Sem redirecionamento para outros erros
            break;
    }
    
});


// Rotas de manutenção
// Protegidas por middleware de administrador (definidas abaixo no grupo)

SimpleRouter::get('/app/maintenance', [AppController::class, 'maintenance']);


SimpleRouter::group(['middleware' => \App\Middleware\AdminMiddleware::class], function () {
    SimpleRouter::post('/admin/maintenance', [MaintenanceController::class, 'update']);
    SimpleRouter::get('/admin/maintenance', [MaintenanceController::class, 'get']);
});


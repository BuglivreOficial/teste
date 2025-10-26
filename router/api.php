<?php
use Pecee\SimpleRouter\SimpleRouter;
use App\Controller\AuthController;
use App\Controller\UserController;
use App\Controller\AdminUserController;

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
# API de Autenticação (Supabase)

Guia de uso da API de autenticação construída com `pecee/simple-router`, `symfony/http-foundation`, `respect/validation` e `rafaelwendel/phpsupabase`, seguindo estilo MVC.

## Pré-requisitos
- Configurar `c:\Users\BUG\Desktop\teste\.env` com:
  - `SUPABASE_URL`: `https://SEU-PROJETO.supabase.co`
  - `SUPABASE_KEY`: chave `anon` ou `service_role` (recomendado `anon` para operações de auth básicas)
- Servidor que respeite `.htaccess` (Apache/Nginx) ou ajuste o roteamento conforme necessário.

## Base URL
- Em desenvolvimento local, considere `http://localhost/` como base.
- Rotas abaixo são relativas à raiz do projeto.

## Endpoints

### POST `/register`
- Objetivo: Criar usuário no Supabase por e-mail e senha.
- Body (JSON):
  ```json
  {
    "email": "novo@user.com",
    "password": "senha123",
    "metadata": { "first_name": "Ana" }
  }
  ```
- Campos obrigatórios:
  - `email` (string, e-mail válido)
  - `password` (string, mínimo 6 caracteres)
- Campos opcionais:
  - `metadata` (objeto JSON com metadados do usuário)
- Respostas:
  - Sucesso `201`:
    ```json
    {
      "message": "Usuário criado! Um link de confirmação foi enviado por e-mail.",
      "email": "novo@user.com"
    }
    ```
  - Erro de validação `422`:
    ```json
    { "errors": { "email": "E-mail inválido", "password": "Senha deve ter ao menos 6 caracteres" } }
    ```
  - Erro de criação `400`:
    ```json
    { "error": "Mensagem de erro retornada pelo Supabase" }
    ```
- Exemplo (curl):
  ```sh
  curl -X POST http://localhost/register \
    -H "Content-Type: application/json" \
    -d '{"email":"novo@user.com","password":"senha123","metadata":{"first_name":"Ana"}}'
  ```

### POST `/login`
- Objetivo: Autenticar usuário por e-mail e senha.
- Body (JSON):
  ```json
  {
    "email": "user@email.com",
    "password": "senha123"
  }
  ```
- Campos obrigatórios:
  - `email`, `password` (mesmos critérios do `/register`)
- Respostas:
  - Sucesso `200`:
    ```json
    {
      "access_token": "...",
      "refresh_token": "...",
      "token_type": "bearer",
      "expires_in": 3600,
      "user": { /* objeto do usuário */ }
    }
    ```
  - Erro de validação `422`:
    ```json
    { "errors": { "email": "E-mail inválido", "password": "Senha deve ter ao menos 6 caracteres" } }
    ```
  - Falha de autenticação `401`:
    ```json
    { "error": "Credenciais inválidas" }
    ```
- Exemplo (curl):
  ```sh
  curl -X POST http://localhost/login \
    -H "Content-Type: application/json" \
    -d '{"email":"user@email.com","password":"senha123"}'
  ```

### POST `/reset-password`
- Objetivo: Enviar link de recuperação de senha para o e-mail informado.
- Body (JSON):
  ```json
  { "email": "user@email.com" }
  ```
- Campos obrigatórios:
  - `email` (e-mail válido)
- Respostas:
  - Sucesso `200`:
    ```json
    { "message": "Se o e-mail existir, um link de recuperação foi enviado.", "email": "user@email.com" }
    ```
  - Erro de validação `422`:
    ```json
    { "error": "E-mail inválido" }
    ```
  - Erro de solicitação `400`:
    ```json
    { "error": "Mensagem de erro retornada pelo Supabase" }
    ```
- Exemplo (curl):
  ```sh
  curl -X POST http://localhost/reset-password \
    -H "Content-Type: application/json" \
    -d '{"email":"user@email.com"}'
  ```

### POST `/profile`
- Objetivo: Retornar dados do usuário autenticado.
- Headers:
  - `Authorization: Bearer <access_token>`
- Respostas:
  - Sucesso `200`:
    ```json
    { "user": { /* objeto do usuário retornado pelo Supabase */ } }
    ```
  - Falha `401`:
    ```json
    { "error": "Token não fornecido" }
    // ou
    { "error": "Token inválido ou expirado" }
    ```
- Exemplo (curl):
  ```sh
  curl -X POST http://localhost/profile \
    -H "Authorization: Bearer <ACCESS_TOKEN>"
  ```

## Códigos de Status
- `200`: OK
- `201`: Criado
- `400`: Erro de requisição (mensagem detalhada pelo Supabase)
- `401`: Não autorizado (token ausente/ inválido)
- `422`: Erros de validação de campos

## Estrutura de Erros
- Erros de validação:
  ```json
  { "errors": { "campo": "mensagem" } }
  ```
- Demais erros:
  ```json
  { "error": "mensagem descritiva" }
  ```

## Observações Importantes
- Confirmação de e-mail: o Supabase envia link de confirmação para o e-mail cadastrado. Usuário deve confirmar para efetivar login.
- `SUPABASE_KEY`: use a chave adequada ao ambiente. A `service_role` tem permissões elevadas; evite usá-la no cliente.
- Se usar tabelas com RLS, utilize o `access_token` obtido no login ao interagir com o banco via `Service->setBearerToken(...)`.
- Ajuste a configuração de redirecionamento (Site URL) nas configurações de autenticação do Supabase para links de confirmação/recuperação.

## Teste Rápido
1. Configure `.env` com `SUPABASE_URL` e `SUPABASE_KEY` válidos.
2. Inicie seu servidor web apontando para a raiz do projeto.
3. Faça `POST /register` para criar usuário.
4. Confirme o e-mail (via link enviado).
5. Faça `POST /login` e guarde `access_token`.
6. Consulte `POST /profile` com `Authorization: Bearer <token>`.

## Endpoints Admin (requer `service_role`)
- Para usar estes endpoints, `SUPABASE_KEY` deve ser a chave `service_role`.
- As chamadas usam `Authorization: Bearer <service_role_key>`.

### POST `/admin/users`
- Lista usuários com paginação.
- Body (JSON): `{ "page": 1, "per_page": 50 }`
- Sucesso `200`:
  ```json
  { "page": 1, "per_page": 50, "data": [ /* lista de usuários */ ] }
  ```
- Exemplo (curl):
  ```sh
  curl -X POST http://localhost/admin/users -H "Content-Type: application/json" -d '{"page":1,"per_page":50}'
  ```

### POST `/admin/users/get`
- Busca usuário por id.
- Body: `{ "id": "USER_ID" }`
- Sucesso `200`: `{ "user": { /* dados do usuário */ } }`

### POST `/admin/users/update`
- Atualiza dados: `email`, `password`, `user_metadata`, `app_metadata`, `ban_duration`.
- Body exemplo:
  ```json
  {
    "id": "USER_ID",
    "email": "novo@user.com",
    "password": "novaSenha123",
    "user_metadata": { "first_name": "Ana" },
    "app_metadata": { "role": "admin" },
    "ban_duration": "24h"
  }
  ```
- Sucesso `200`: `{ "user": { /* dados atualizados */ } }`

### POST `/admin/users/delete`
- Deleta usuário por id.
- Body: `{ "id": "USER_ID" }`
- Sucesso `200`: `{ "message": "Usuário deletado com sucesso" }`

### POST `/admin/users/ban`
- Bane um usuário por `duration` (ex.: `24h`, `7d`).
- Body: `{ "id": "USER_ID", "duration": "24h" }`
- Sucesso `200`: `{ "user": { /* dados */ }, "message": "Usuário banido" }`

### POST `/admin/users/unban`
- Remove ban de usuário.
- Body: `{ "id": "USER_ID" }`
- Sucesso `200`: `{ "user": { /* dados */ }, "message": "Ban removido" }`

## Segurança
- Use HTTPS em produção.
- Guarde tokens de forma segura (HTTP-only cookies, storage seguro).
- Tokens expiram; utilize `refresh_token` para renovar sessões quando necessário.
- Restringir acesso às rotas admin (ex.: verificação de role no `app_metadata`).
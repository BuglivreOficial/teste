# API de Status do Aplicativo

Rotas para expor publicamente a versão do aplicativo Android e o estado de manutenção, seguindo a mesma estrutura do projeto (`pecee/simple-router`, `symfony/http-foundation`).

## Pré-requisitos
- Configurar `c:\Users\BUG\Desktop\teste\.env` com:
  - `APP_ANDROID_VERSION`: versão atual do app (ex.: `"1.0.0"`)
  - `APP_MAINTENANCE`: `"true"` ou `"false"`
  - `APP_MAINTENANCE_MESSAGE` (opcional): mensagem exibida quando manutenção estiver ativa
- Não é necessário criar tabela no Supabase. As rotas usam variáveis de ambiente. Se desejar gestão remota, podemos evoluir para ler de uma tabela Supabase.

## Base URL
- Em desenvolvimento local: `http://localhost/`

## Endpoints

### GET `/app/version`
- Objetivo: retornar a versão atual do aplicativo Android.
- Resposta `200`:
  ```json
  {
    "platform": "android",
    "version": "1.0.0",
    "source": "env"
  }
  ```
- Exemplo (curl):
  ```sh
  curl -X GET http://localhost/app/version
  ```

### GET `/app/maintenance`
- Objetivo: retornar se o aplicativo está em manutenção e mensagem opcional.
- Resposta `200` (manutenção desligada):
  ```json
  {
    "maintenance": false,
    "message": null,
    "source": "env"
  }
  ```
- Resposta `200` (manutenção ligada):
  ```json
  {
    "maintenance": true,
    "message": "Estamos em manutenção. Tente novamente mais tarde.",
    "source": "env"
  }
  ```
- Exemplo (curl):
  ```sh
  curl -X GET http://localhost/app/maintenance
  ```

## Configuração (.env)
```env
APP_ANDROID_VERSION="1.0.0"
APP_MAINTENANCE="false"
# APP_MAINTENANCE_MESSAGE="Estamos em manutenção. Tente novamente mais tarde."
```

## Implementação
- Controller: `app/Controller/AppController.php`
  - `version()`: lê `APP_ANDROID_VERSION` e retorna JSON.
  - `maintenance()`: lê `APP_MAINTENANCE` (boolean) e `APP_MAINTENANCE_MESSAGE` (opcional).
- Rotas: `router/api.php`
  - `GET /app/version` → `AppController::version`
  - `GET /app/maintenance` → `AppController::maintenance`

## Observações
- Sem dependência de banco: simples, rápido, ideal para cPanel.
- Se desejar controle remoto sem deploy, podemos criar uma tabela Supabase (ex.: `app_config`) e ler as configurações via API Admin, mas isso não é necessário para o funcionamento atual.
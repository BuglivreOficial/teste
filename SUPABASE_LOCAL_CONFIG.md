# Configuração do Supabase Local para Desenvolvimento

## Problema Identificado
O erro `"Error sending confirmation email"` ocorre porque o Supabase local está configurado para exigir confirmação de email, mas não tem um servidor de email configurado.

## Soluções

### Solução 1: Desabilitar Confirmação de Email (Recomendada para desenvolvimento)

1. **Localize o arquivo de configuração do Supabase local:**
   - Geralmente está em `supabase/config.toml` no seu projeto Supabase
   - Ou em `~/.supabase/config.toml`

2. **Edite a seção `[auth]`:**
   ```toml
   [auth]
   enable_signup = true
   enable_confirmations = false  # Desabilita confirmação de email
   enable_recoveries = true
   ```

3. **Reinicie o Supabase local:**
   ```bash
   supabase stop
   supabase start
   ```

### Solução 2: Configurar SMTP Local (Para testar emails)

1. **Edite o arquivo `config.toml`:**
   ```toml
   [auth.email]
   enable_signup = true
   double_confirm_changes = true
   enable_confirmations = true
   
   [auth.email.smtp]
   host = "localhost"
   port = 587
   user = ""
   pass = ""
   admin_email = "admin@example.com"
   sender_name = "Supabase"
   ```

2. **Use um servidor SMTP local como MailHog:**
   ```bash
   # Instalar MailHog
   go install github.com/mailhog/MailHog@latest
   
   # Executar MailHog
   MailHog
   ```

### Solução 3: Usar Supabase Cloud (Produção)

1. **Crie um projeto no [Supabase Cloud](https://supabase.com)**

2. **Configure o `.env` com as credenciais reais:**
   ```env
   SUPABASE_URL="https://SEU-PROJETO.supabase.co"
   SUPABASE_KEY="SUA_CHAVE_ANON_AQUI"
   APP_ENV="production"
   DISABLE_EMAIL_CONFIRMATION="false"
   ```

3. **Configure o provedor de email no Dashboard do Supabase:**
   - Vá em Authentication > Settings
   - Configure SMTP ou use um provedor como SendGrid, Mailgun, etc.

## Teste Rápido

Após aplicar a **Solução 1**, teste o registro:

```bash
# PowerShell
Invoke-RestMethod -Uri "http://localhost/register" -Method POST -ContentType "application/json" -Body '{"email":"teste@exemplo.com","password":"123456"}'
```

## Arquivos Modificados

- `.env`: Adicionada variável `DISABLE_EMAIL_CONFIRMATION="true"`
- `AuthController.php`: Lógica para diferentes mensagens em desenvolvimento
- Este arquivo de documentação

## Próximos Passos

1. Aplique a **Solução 1** (recomendada)
2. Teste o registro novamente
3. Se funcionar, continue com o desenvolvimento
4. Para produção, use a **Solução 3** com Supabase Cloud
# Graviton — GitHub Contributor Analytics API

API multi-tenant para análise de contribuições em repositórios GitHub. Cada tenant (organização) rastreia seus repositórios, calcula o índice de gravidade de cada contribuidor e visualiza conexões orbitais entre projetos.

## Stack

- **Laravel 12** + PHP 8.2
- **PostgreSQL** (Supabase)
- **JWT** via `php-open-source-saver/jwt-auth`
- **Filas** com database driver (sync de repositórios GitHub)

## Conceitos do domínio

| Conceito | Descrição |
|---|---|
| **Tenant** | Organização/empresa isolada no sistema |
| **Admin** | Usuário autenticado dentro de um tenant |
| **Repository** | Repositório GitHub monitorado |
| **Contributor** | Usuário GitHub que contribuiu para um repositório |
| **Contribution** | Vínculo entre contributor e repositório com métricas de commits |
| **Gravity (G)** | Índice de impacto: `G = commits_do_contributor / total_commits_do_repo` |
| **Orbit Connection** | Conexão salva entre dois repositórios (modelo dos dois corpos) |

---

## Setup

### 1. Instalar dependências

```bash
composer install
```

### 2. Configurar ambiente

```bash
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```

Edite o `.env` com suas credenciais do banco e token do GitHub.

### 3. Executar migrations e seeders

```bash
php artisan migrate --seed
```

Isso cria um tenant demo com credenciais:
- **Tenant ID:** `graviton-demo`
- **Email:** `admin@graviton.dev`
- **Senha:** `password`

### 4. Iniciar o servidor

```bash
php artisan serve
```

### 5. Processar filas (em outro terminal)

```bash
php artisan queue:work
```

---

## Autenticação

Todas as rotas protegidas requerem o header:

```
Authorization: Bearer {token}
```

O token é obtido no endpoint de login.

---

## Endpoints

### Registro

```bash
curl -X POST http://localhost:8000/api/v1/register \
  -H "Content-Type: application/json" \
  -d '{
    "tenant_id": "minha-empresa",
    "tenant_name": "Minha Empresa Ltda",
    "tenant_email": "contato@minhaempresa.com",
    "plan": "starter",
    "name_admin": "João Silva",
    "email_admin": "joao@minhaempresa.com",
    "password_admin": "senha1234"
  }'
```

### Login

```bash
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -H "X-Tenant-ID: graviton-demo" \
  -d '{
    "email_admin": "admin@graviton.dev",
    "password_admin": "password",
    "tenant_id": "graviton-demo"
  }'
```

> Guarde o `access_token` retornado. Substitua `TOKEN` nos exemplos abaixo.

### Perfil do admin autenticado

```bash
curl http://localhost:8000/api/v1/me \
  -H "Authorization: Bearer TOKEN"
```

### Tenant (ver e editar o próprio)

```bash
# Ver
curl http://localhost:8000/api/v1/tenant \
  -H "Authorization: Bearer TOKEN"

# Atualizar plano
curl -X PATCH http://localhost:8000/api/v1/tenant \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"plan": "pro"}'
```

---

### Repositórios

```bash
# Listar
curl http://localhost:8000/api/v1/repositories \
  -H "Authorization: Bearer TOKEN"

# Adicionar (dispara sync assíncrono via fila)
curl -X POST http://localhost:8000/api/v1/repositories \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"github_url": "https://github.com/laravel/laravel"}'

# Deletar em lote
curl -X DELETE http://localhost:8000/api/v1/repositories \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"ids": ["uuid-1", "uuid-2"]}'
```

---

### Contributors

```bash
# Listar todos
curl http://localhost:8000/api/v1/contributors \
  -H "Authorization: Bearer TOKEN"

# Filtrar contratáveis
curl "http://localhost:8000/api/v1/contributors?hireable=true" \
  -H "Authorization: Bearer TOKEN"
```

---

### Contributions e Gravity

O índice **Gravity** (`G = commits / total`) mede o peso de cada contributor em um repositório. Valor entre 0 e 1.

```bash
# Listar contributions de um repositório (com gravity calculado)
curl "http://localhost:8000/api/v1/contributions?repository_id=UUID_DO_REPO" \
  -H "Authorization: Bearer TOKEN"
```

Resposta:
```json
{
  "repository": { "id": "...", "github_owner": "laravel", "github_repo": "laravel" },
  "total_commits": 447,
  "contributions": [
    {
      "contributor": { "username": "taylorotwell", "avatar_url": "..." },
      "commits_count": 320,
      "gravity": 0.716
    },
    {
      "contributor": { "username": "driesvints", "avatar_url": "..." },
      "commits_count": 85,
      "gravity": 0.190
    }
  ]
}
```

---

### Orbit Connections

Conexões entre dois repositórios (modelo dos dois corpos gravitacionais).

```bash
# Listar
curl http://localhost:8000/api/v1/orbit-connections \
  -H "Authorization: Bearer TOKEN"

# Criar
curl -X POST http://localhost:8000/api/v1/orbit-connections \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Core ↔ App",
    "primary_repository_id": "UUID_REPO_A",
    "secondary_repository_id": "UUID_REPO_B"
  }'
```

---

### Admins

```bash
# Listar admins do tenant
curl http://localhost:8000/api/v1/admins \
  -H "Authorization: Bearer TOKEN"

# Criar novo admin
curl -X POST http://localhost:8000/api/v1/admins \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name_admin": "Maria Costa",
    "email_admin": "maria@minhaempresa.com",
    "password_admin": "senha1234"
  }'
```

---

## Variáveis de ambiente relevantes

| Variável | Descrição |
|---|---|
| `JWT_SECRET` | Gerado com `php artisan jwt:secret` |
| `JWT_TTL` | Validade do token em minutos (padrão: 4320 = 3 dias) |
| `GITHUB_TOKEN` | PAT do GitHub com permissão `public_repo` |
| `FRONTEND_URL` | Origin permitida pelo CORS |
| `QUEUE_CONNECTION` | `database` em produção, `sync` para dev sem fila |

# Copilot / AI agent instructions for BT Log Transportes

Purpose: quick, actionable guidance so an AI coding agent can be immediately productive in this repo.

## Visão geral (big picture)
- **Arquitetura**: PHP monolito MVC simples — front controller em `public/index.php`, controllers em `app/controllers/`, modelos em `app/models/` e views em `app/views/`.
- **Entrypoint**: `public/index.php` decide a rota via `?page=` e usa um `switch` para incluir controllers ou views.
- **APIs**: Duas abordagens coexistem:
  - Endpoints simples: `public/api/*.php` (usam `action` e retornam JSON). Ex.: `public/api/drivers.php`.
  - Endpoints por controller: alguns controllers expõem `api()` ou `getApiData()` para chamadas AJAX (ex.: `BaseController::api()`).

## Convenções importantes (faça assim)
- Sempre use `require_once` com caminhos relativos iguais aos existentes — **não** adicione autoloaders sem motivo.
- Views são incluídas com header/footer: incluir `../app/views/layouts/header.php` + a view + `../app/views/layouts/footer.php`.
- Logging: use `error_log(...)` para mensagens de debug/erro (o código já utiliza este padrão e até emojis para facilitar debugging).
- Sessões e permissões:
  - Use `app/core/Session.php` para manipular session (métodos: `set`, `get`, `isLoggedIn`, `setUser`, `checkTimeout`).
  - `AuthMiddleware::handle($route)` é a regra de autorização central. Respeite o mapeamento de roles (ex.: `admin`, `financeiro`, `comercial`) e rotas permitidas em `AuthMiddleware::getRolePermissions()`.
- Database: use `app/core/Database.php` (singleton). Métodos úteis: `query($sql, $params)`, `fetch`, `fetchAll`, `insert`.

## Como rodar localmente / debug
- Este projeto é pensado para XAMPP/Apache + PHP. Coloque a pasta em `htdocs` e abra: `http://localhost/bt-log-transportes/public`.
- DEBUG: ative `DEBUG` em `app/config/config.php` para ver erros (o `public/index.php` já seta `display_errors`).
- `composer.json` está vazio (nenhuma dependência externa por padrão). Se adicionar dependências, atualize `composer.json` e rode `composer install`.

## Padrões de API e AJAX (exemplos práticos)
- `public/api/*.php` padrão:
  - Definir `Content-Type: application/json` no topo.
  - Incluir `config`, `database`, `Database.php`, `Session.php` e modelos/controllers necessários.
  - Iniciar sessão se necessário: `if (session_status() === PHP_SESSION_NONE) session_start();`.
  - Validar `action` e encaminhar para métodos do controller.
  - Sempre retornar JSON e finalizar com `exit;`.

Exemplo curto (padrão usado em `public/api/drivers.php`):
```php
header('Content-Type: application/json');
require_once __DIR__ . '/../../app/core/Database.php';
...
$action = $_GET['action'] ?? '';
switch ($action) { case 'save': $controller->save(); break; }
exit;
```

- Controllers que servem AJAX (ex.: `BaseController::api()`): retornam JSON, configuram headers e usam `http_response_code(...)` para status corretos.

## Exemplos de código úteis
- Query preparada com helper:
```php
$db = Database::getInstance();
$rows = $db->fetchAll('SELECT * FROM drivers WHERE company_id = ?', [$companyId]);
```
- Sessões / scopo por empresa:
```php
$session = new Session();
$companyId = $session->get('company_id'); // usado para filtrar queries por empresa
```

## Como adicionar uma página / rota
1. Criar controller em `app/controllers/` (ou uma view em `app/views/` se for simples).
2. Abrir `public/index.php` e adicionar um `case 'nova_rota':` que `require_once` do controller e chama `$controller->index()` — siga os exemplos já existentes.
3. Se precisar de AJAX, crie um `public/api/*.php` ou adicione `api()` ao controller conforme padrão do projeto.

## Arquivos a inspecionar antes de editar
- `public/index.php` — roteamento e middleware
- `app/middleware/AuthMiddleware.php` — regras de autorização e redirecionamentos
- `app/core/Database.php` — helper PDO (use-o para todas as queries)
- `app/core/Session.php` — padrões de sessão (timeout, setUser keys)
- `public/api/drivers.php` e `app/controllers/DriverController.php` — exemplos completos de API e controller

## Regras de alteração (práticas que já existem)
- Mantenha chamadas `require_once` e includes relativos (não introduza autoload global sem avaliar impacto).
- Prefira usar `Database` wrapper e prepared statements — evite concatenar SQL.
- Preserve o padrão de views com header/footer e de logging via `error_log()`.
- Ao alterar autenticação/permissões, atualize `AuthMiddleware` e faça testes manuais pelo browser (rotas protegidas redirecionam para `public/login.php`).

---
Se quiser, faço uma revisão rápida das alterações propostas (ou adiciono exemplos para endpoints específicos). Quer que eu resuma isto em 1–2 exemplos práticos a mais (ex.: criar rota + endpoint AJAX)?

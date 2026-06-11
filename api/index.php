<?php
// ══════════════════════════════════════════════
//  TROPICAL FM — API REST
//  index.php — roteador principal
//  PHP 8.0+ / MySQL 5.7+
// ══════════════════════════════════════════════

// Headers CORS e JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Autoload de classes
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/response.php';
require_once __DIR__ . '/config/validator.php';
require_once __DIR__ . '/middleware/auth.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/EmpresaController.php';
require_once __DIR__ . '/controllers/NoticiaController.php';
require_once __DIR__ . '/controllers/ProgramaController.php';
require_once __DIR__ . '/controllers/SlideController.php';
require_once __DIR__ . '/controllers/TopMusicaController.php';
require_once __DIR__ . '/controllers/PodcastController.php';
require_once __DIR__ . '/controllers/EnqueteController.php';
require_once __DIR__ . '/controllers/PublicidadeController.php';
require_once __DIR__ . '/controllers/ConfigController.php';
require_once __DIR__ . '/controllers/UploadController.php';

// Tratamento global de exceções
set_exception_handler(function (Throwable $e) {
    error_log('[API Error] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    Response::serverError('Ocorreu um erro interno. Tente novamente.');
});

// Lê o body JSON uma vez
$body = json_decode(file_get_contents('php://input'), true) ?? [];

// Rota: /api/...
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri    = preg_replace('#^/api#', '', $uri);
$uri    = trim($uri, '/');
$method = $_SERVER['REQUEST_METHOD'];
$parts  = explode('/', $uri);
$resource = $parts[0] ?? '';
$id       = $parts[1] ?? null;

// ── ROTEAMENTO ─────────────────────────────────
switch ($resource) {

    // ── AUTH ─────────────────────────────────
    case 'auth':
        $ctrl = new AuthController($body);
        match($method) {
            'POST' => ($id === 'refresh')
                        ? $ctrl->refresh()
                        : $ctrl->login(),
            default => Response::error('method_not_allowed', 'Método não permitido.', 405)
        };
        break;

    // ── EMPRESA / RÁDIO ──────────────────────
    case 'empresa':
        Auth::require();
        $ctrl = new EmpresaController($body, $id);
        match($method) {
            'GET'  => $ctrl->show(),
            'PUT'  => $ctrl->update(),
            default => Response::error('method_not_allowed', 'Método não permitido.', 405)
        };
        break;

    // ── NOTÍCIAS ────────────────────────────
    case 'noticias':
        $ctrl = new NoticiaController($body, $id);
        match($method) {
            'GET'    => $id ? $ctrl->show() : $ctrl->index(),
            'POST'   => (Auth::require()) ? $ctrl->store()  : null,
            'PUT'    => (Auth::require()) ? $ctrl->update() : null,
            'DELETE' => (Auth::require()) ? $ctrl->destroy(): null,
            default  => Response::error('method_not_allowed', 'Método não permitido.', 405)
        };
        break;

    // ── PROGRAMAS ────────────────────────────
    case 'programas':
        $ctrl = new ProgramaController($body, $id);
        match($method) {
            'GET'    => $id ? $ctrl->show() : $ctrl->index(),
            'POST'   => (Auth::require()) ? $ctrl->store()  : null,
            'PUT'    => (Auth::require()) ? $ctrl->update() : null,
            'DELETE' => (Auth::require()) ? $ctrl->destroy(): null,
            default  => Response::error('method_not_allowed', 'Método não permitido.', 405)
        };
        break;

    // ── SLIDES / CAROUSEL ────────────────────
    case 'slides':
        $ctrl = new SlideController($body, $id);
        match($method) {
            'GET'    => $ctrl->index(),
            'POST'   => (Auth::require()) ? $ctrl->store()  : null,
            'PUT'    => (Auth::require()) ? $ctrl->update() : null,
            'DELETE' => (Auth::require()) ? $ctrl->destroy(): null,
            default  => Response::error('method_not_allowed', 'Método não permitido.', 405)
        };
        break;

    // ── TOP MÚSICAS ──────────────────────────
    case 'top':
        $ctrl = new TopMusicaController($body, $id);
        match($method) {
            'GET'    => $ctrl->index(),
            'POST'   => (Auth::require()) ? $ctrl->store()  : null,
            'PUT'    => (Auth::require()) ? $ctrl->reorder(): null,
            'DELETE' => (Auth::require()) ? $ctrl->destroy(): null,
            default  => Response::error('method_not_allowed', 'Método não permitido.', 405)
        };
        break;

    // ── PODCAST / LIVE ───────────────────────
    case 'podcast':
        $ctrl = new PodcastController($body, $id);
        match($method) {
            'GET'  => $ctrl->index(),
            'POST' => (Auth::require()) ? ($id === 'live' ? $ctrl->setLive() : $ctrl->store()) : null,
            'DELETE' => (Auth::require()) ? $ctrl->destroy(): null,
            default  => Response::error('method_not_allowed', 'Método não permitido.', 405)
        };
        break;

    // ── ENQUETE ──────────────────────────────
    case 'enquete':
        $ctrl = new EnqueteController($body, $id);
        match($method) {
            'GET'  => $ctrl->show(),
            'POST' => $id === 'votar'
                        ? $ctrl->votar()
                        : ((Auth::require()) ? $ctrl->update() : null),
            default => Response::error('method_not_allowed', 'Método não permitido.', 405)
        };
        break;

    // ── PUBLICIDADE ──────────────────────────
    case 'publicidade':
        Auth::require();
        $ctrl = new PublicidadeController($body, $id);
        match($method) {
            'GET' => $ctrl->show(),
            'PUT' => $ctrl->update(),
            default => Response::error('method_not_allowed', 'Método não permitido.', 405)
        };
        break;

    // ── CONFIGURAÇÕES ────────────────────────
    case 'config':
        Auth::require();
        $ctrl = new ConfigController($body, $id);
        match($method) {
            'GET' => $ctrl->show(),
            'PUT' => $ctrl->update(),
            default => Response::error('method_not_allowed', 'Método não permitido.', 405)
        };
        break;

    // ── UPLOAD ───────────────────────────────
    case 'upload':
        Auth::require();
        $ctrl = new UploadController();
        match($method) {
            'POST' => $ctrl->upload(),
            default => Response::error('method_not_allowed', 'Método não permitido.', 405)
        };
        break;

    // ── HEALTH CHECK ─────────────────────────
    case 'health':
        Response::ok([
            'status'  => 'ok',
            'version' => APP_VERSION,
            'time'    => date('c'),
            'db'      => (bool) DB::get(),
        ], 'API funcionando.');
        break;

    // ── 404 ──────────────────────────────────
    default:
        Response::notFound("Endpoint /{$resource}");
}

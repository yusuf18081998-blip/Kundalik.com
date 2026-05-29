<?php
// ===== 45-MAKTAB PHP BACKEND API =====
// Ishlatish: php -S localhost:8000 -t ./

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

define('DB_PATH', __DIR__ . '/data/');
if (!is_dir(DB_PATH)) mkdir(DB_PATH, 0755, true);

function db_read($table) {
    $file = DB_PATH . $table . '.json';
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true) ?: [];
}
function db_write($table, $data) {
    file_put_contents(DB_PATH . $table . '.json', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}
function respond($code, $data) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
function get_token() {
    $h = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.+)/', $h, $m)) return $m[1];
    return null;
}
function auth_required() {
    $token = get_token();
    if (!$token) respond(410, ['error' => 'Token topilmadi']);
    $users = db_read('users');
    foreach ($users as $u) {
        if (($u['token'] ?? '') === $token) return $u;
    }
    respond(401, ['error' => 'Ruxsat berilmagan token']);
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$parts = explode('/', trim($uri, '/'));
// Agar localhost:8000/api.php deb chaqirilsa, resurs oxirgi qismdan olinadi
$resource = end($parts); 
$method = $_SERVER['REQUEST_METHOD'];
$body = json_decode(file_get_contents('php://input'), true) ?: [];

switch ($resource) {
    case 'login':
        if ($method !== 'POST') respond(405, ['error' => 'Metod xato']);
        $user = $body['username'] ?? '';
        $pass = $body['password'] ?? '';
        $users = db_read('users');
        
        // Agar baza yangi va bo'sh bo'lsa, birlamchi admin yaratiladi
        if (empty($users)) {
            $users = [
                [
                    'id' => 1,
                    'username' => 'admin',
                    'password' => hash('sha256', 'admin123'),
                    'role' => 'Admin',
                    'name' => 'Tizim Administratori',
                    'token' => 'admin_session_token_45'
                ]
            ];
            db_write('users', $users);
        }
        
        foreach ($users as &$u) {
            if ($u['username'] === $user && $u['password'] === hash('sha256', $pass)) {
                $u['token'] = bin2hex(random_bytes(16));
                db_write('users', $users);
                respond(200, ['token' => $u['token'], 'role' => $u['role'], 'name' => $u['name']]);
            }
        }
        respond(400, ['error' => 'Login yoki parol xato!']);
        break;

    case 'announcements':
        auth_required();
        $list = db_read('announcements');
        if ($method === 'GET')  respond(200, $list);
        if ($method === 'POST') {
            $ids   = array_column($list,'id');
            $newId = $ids ? max($ids)+1 : 1;
            $new   = ['id'=>$newId,'title'=>$body['title'],'type'=>$body['type']??'normal','date'=>date('Y-m-d'),'text'=>$body['text']??''];
            $list[]=$new; db_write('announcements',$list); respond(201,$new);
        }
        break;

    case 'students':
        auth_required();
        $list = db_read('students');
        if ($method === 'GET') respond(200, $list);
        if ($method === 'POST') {
            $ids = array_column($list,'id');
            $newId = $ids ? max($ids)+1 : 1;
            $body['id'] = $newId;
            $list[] = $body; db_write('students',$list); respond(201,$body);
        }
        break;

    case 'teachers':
        auth_required();
        $list = db_read('teachers');
        if ($method === 'GET') respond(200, $list);
        if ($method === 'POST') {
            $ids = array_column($list,'id');
            $newId = $ids ? max($ids)+1 : 1;
            $body['id'] = $newId;
            $list[] = $body; db_write('teachers',$list); respond(201,$body);
        }
        break;

    case 'grades':
        auth_required();
        $list = db_read('grades');
        if ($method === 'GET') respond(200, $list);
        if ($method === 'POST') {
            $ids = array_column($list,'id');
            $newId = $ids ? max($ids)+1 : 1;
            $body['id'] = $newId;
            $body['date'] = date('Y-m-d');
            $list[] = $body; db_write('grades',$list); respond(201,$body);
        }
        break;

    default:
        respond(404, ['error' => 'Resurs topilmadi']);
        break;
}

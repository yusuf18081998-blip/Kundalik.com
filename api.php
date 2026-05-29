<?php
// ===== 45-MAKTAB PHP BACKEND API =====
// Ishlatish: php -S localhost:8000 -t ./ php/api.php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

define('DB_PATH', __DIR__ . '/../data/');
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
    if (!$token) { respond(401, ['error' => 'Token kerak']); }
    $data = json_decode(base64_decode($token), true);
    if (!$data || !isset($data['id'])) { respond(401, ['error' => "Token notogri"]); }
    return $data;
}

// ---- DB INIT ----
if (!file_exists(DB_PATH . 'users.json')) {
    db_write('users', [
        ['id'=>1,'login'=>'admin',   'pass'=>password_hash('admin123',PASSWORD_DEFAULT),'role'=>'admin',  'name'=>'Administrator',      'email'=>'admin@45maktab.uz'],
        ['id'=>2,'login'=>'teach1',  'pass'=>password_hash('teach1',  PASSWORD_DEFAULT),'role'=>'teacher','name'=>'Karimova Nodira A.', 'email'=>'karimova@45maktab.uz'],
        ['id'=>3,'login'=>'student1','pass'=>password_hash('student1',PASSWORD_DEFAULT),'role'=>'student','name'=>'Toshmatov Jasur',    'email'=>'jasur@mail.uz'],
    ]);
}
if (!file_exists(DB_PATH . 'students.json')) {
    db_write('students', [
        ['id'=>1,'name'=>'Toshmatov Jasur',   'cls'=>'8-A','born'=>'2009-03-12','phone'=>'+998901111111','avg'=>4.7],
        ['id'=>2,'name'=>'Yusupova Dilnoza',  'cls'=>'8-A','born'=>'2009-07-22','phone'=>'+998902222222','avg'=>4.2],
        ['id'=>3,'name'=>'Nazarov Bekzod',    'cls'=>'8-B','born'=>'2008-11-05','phone'=>'+998903333333','avg'=>3.8],
        ['id'=>4,'name'=>'Xoliqova Malika',   'cls'=>'9-A','born'=>'2008-01-30','phone'=>'+998904444444','avg'=>5.0],
        ['id'=>5,'name'=>'Abdullayev Sherzod','cls'=>'9-A','born'=>'2008-06-17','phone'=>'+998905555555','avg'=>4.5],
        ['id'=>6,'name'=>'Raximova Zulfiya',  'cls'=>'7-A','born'=>'2010-09-03','phone'=>'+998906666666','avg'=>3.5],
    ]);
}
if (!file_exists(DB_PATH . 'teachers.json')) {
    db_write('teachers', [
        ['id'=>1,'name'=>'Karimova Nodira A.',  'subject'=>'Matematika', 'exp'=>12,'phone'=>'+998901234568','classes'=>'7-A, 8-A, 8-B'],
        ['id'=>2,'name'=>'Toshmatov Bahodir S.','subject'=>"O'zbek tili",'exp'=>8, 'phone'=>'+998902345679','classes'=>'7-A, 7-B, 9-A'],
        ['id'=>3,'name'=>'Xoliqov Ravshan M.',  'subject'=>'Fizika',     'exp'=>15,'phone'=>'+998903456780','classes'=>'9-A, 10-A, 11-A'],
        ['id'=>4,'name'=>'Yusupova Dildora F.', 'subject'=>'Ingliz tili','exp'=>6, 'phone'=>'+998904567891','classes'=>'8-A, 9-A, 9-B'],
        ['id'=>5,'name'=>'Nazarova Gulnora T.', 'subject'=>'Kimyo',      'exp'=>10,'phone'=>'+998905678902','classes'=>'9-A, 10-A, 11-A'],
    ]);
}
if (!file_exists(DB_PATH . 'grades.json')) {
    db_write('grades', [
        ['id'=>1,'student'=>'Toshmatov Jasur',   'subject'=>'Matematika', 'cls'=>'8-A','grade'=>5,'date'=>'2025-05-20','note'=>'Mustaqil ish'],
        ['id'=>2,'student'=>'Yusupova Dilnoza',  'subject'=>'Fizika',     'cls'=>'8-A','grade'=>4,'date'=>'2025-05-21','note'=>'Test'],
        ['id'=>3,'student'=>'Nazarov Bekzod',    'subject'=>'Ingliz tili','cls'=>'8-B','grade'=>3,'date'=>'2025-05-21','note'=>'Ogzaki javob'],
        ['id'=>4,'student'=>'Xoliqova Malika',   'subject'=>'Kimyo',      'cls'=>'9-A','grade'=>5,'date'=>'2025-05-22','note'=>'Laboratoriya'],
        ['id'=>5,'student'=>'Abdullayev Sherzod','subject'=>'Tarix',      'cls'=>'9-A','grade'=>4,'date'=>'2025-05-22','note'=>'Referat'],
    ]);
}

// ---- ROUTER ----
$method = $_SERVER['REQUEST_METHOD'];
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$parts  = explode('/', trim($uri, '/'));
$resource = $parts[0] ?? '';
$id       = isset($parts[1]) && $parts[1] !== '' ? $parts[1] : null;
$body     = json_decode(file_get_contents('php://input'), true) ?: [];

switch ($resource) {

    case 'login':
        if ($method !== 'POST') respond(405, ['error'=>'POST kerak']);
        $users = db_read('users');
        $user  = null;
        foreach ($users as $u) {
            if ($u['login'] === ($body['login']??'') && password_verify($body['pass']??'', $u['pass'])) {
                $user = $u; break;
            }
        }
        if (!$user) respond(401, ['error'=>"Login yoki parol notogri"]);
        $token = base64_encode(json_encode(['id'=>$user['id'],'role'=>$user['role'],'name'=>$user['name']]));
        unset($user['pass']);
        respond(200, ['token'=>$token, 'user'=>$user]);
        break;

    case 'me':
        $u = auth_required();
        respond(200, $u);
        break;

    case 'students':
        $user = auth_required();
        $list = db_read('students');
        if ($method === 'GET') {
            if ($_GET['cls'] ?? '') $list = array_values(array_filter($list, fn($s)=>$s['cls']===($_GET['cls'])));
            if ($_GET['q']   ?? '') { $q=strtolower($_GET['q']); $list=array_values(array_filter($list,fn($s)=>str_contains(strtolower($s['name']),$q))); }
            respond(200, $list);
        }
        if ($method === 'POST') {
            if ($user['role']==='student') respond(403,['error'=>'Ruxsat yoq']);
            $ids   = array_column($list,'id');
            $newId = $ids ? max($ids)+1 : 1;
            $new   = ['id'=>$newId,'name'=>$body['name'],'cls'=>$body['cls'],'born'=>$body['born']??'','phone'=>$body['phone']??'','avg'=>0];
            $list[]=$new; db_write('students',$list); respond(201,$new);
        }
        if ($method === 'PUT' && $id) {
            foreach ($list as &$s) { if ($s['id']==$id) { $s=array_merge($s,$body); break; } }
            db_write('students',$list); respond(200,['ok'=>true]);
        }
        if ($method === 'DELETE' && $id) {
            if ($user['role']!=='admin') respond(403,['error'=>'Faqat admin']);
            $list=array_values(array_filter($list,fn($s)=>$s['id']!=$id));
            db_write('students',$list); respond(200,['ok'=>true]);
        }
        respond(404,['error'=>'Topilmadi']);
        break;

    case 'teachers':
        $user = auth_required();
        $list = db_read('teachers');
        if ($method === 'GET') respond(200, $list);
        if ($method === 'POST') {
            $ids   = array_column($list,'id');
            $newId = $ids ? max($ids)+1 : 1;
            $new   = ['id'=>$newId,'name'=>$body['name'],'subject'=>$body['subject'],'exp'=>(int)($body['exp']??0),'phone'=>$body['phone']??'','classes'=>$body['classes']??''];
            $list[]=$new; db_write('teachers',$list); respond(201,$new);
        }
        if ($method === 'DELETE' && $id) {
            if ($user['role']!=='admin') respond(403,['error'=>'Faqat admin']);
            $list=array_values(array_filter($list,fn($t)=>$t['id']!=$id));
            db_write('teachers',$list); respond(200,['ok'=>true]);
        }
        respond(405,['error'=>'Method not allowed']);
        break;

    case 'grades':
        $user = auth_required();
        $list = db_read('grades');
        if ($method === 'GET') {
            if ($_GET['cls']     ?? '') $list=array_values(array_filter($list,fn($g)=>$g['cls']===($_GET['cls'])));
            if ($_GET['subject'] ?? '') $list=array_values(array_filter($list,fn($g)=>$g['subject']===($_GET['subject'])));
            if ($_GET['student'] ?? '') $list=array_values(array_filter($list,fn($g)=>$g['student']===($_GET['student'])));
            respond(200, $list);
        }
        if ($method === 'POST') {
            $ids   = array_column($list,'id');
            $newId = $ids ? max($ids)+1 : 1;
            $new   = ['id'=>$newId,'student'=>$body['student'],'subject'=>$body['subject'],'cls'=>$body['cls']??'','grade'=>(int)$body['grade'],'date'=>date('Y-m-d'),'note'=>$body['note']??''];
            $list[]=$new; db_write('grades',$list); respond(201,$new);
        }
        if ($method === 'DELETE' && $id) {
            $list=array_values(array_filter($list,fn($g)=>$g['id']!=$id));
            db_write('grades',$list); respond(200,['ok'=>true]);
        }
        respond(405,['error'=>'Method not allowed']);
        break;

    case 'stats':
        auth_required();
        $students = db_read('students');
        $teachers = db_read('teachers');
        $grades   = db_read('grades');
        $allG     = array_column($grades,'grade');
        respond(200,['students'=>count($students),'teachers'=>count($teachers),'grades'=>count($grades),'avg_grade'=>$allG ? round(array_sum($allG)/count($allG),2):0]);
        break;

    case 'announcements':
        auth_required();
        $list = db_read('announcements');
        if (empty($list)) {
            $list = [
                ['id'=>1,'title'=>"Yakuniy imtihon jadvali",'type'=>'urgent','date'=>'2025-05-25','text'=>"11-sinf o'quvchilari uchun yakuniy imtihon 10-iyunda."],
                ['id'=>2,'title'=>'Sport musobaqasi',        'type'=>'info',  'date'=>'2025-05-22','text'=>'30-may kuni maktablararo futbol musobaqasi.'],
                ['id'=>3,'title'=>"Ota-onalar yig'ilishi",   'type'=>'normal','date'=>'2025-05-20','text'=>"27-may kuni soat 17:00 da yig'ilish."],
            ];
            db_write('announcements',$list);
        }
        if ($method === 'GET')  respond(200, $list);
        if ($method === 'POST') {
            $ids   = array_column($list,'id');
            $newId = $ids ? max($ids)+1 : 1;
            $new   = ['id'=>$newId,'title'=>$body['title'],'type'=>$body['type']??'normal','date'=>date('Y-m-d'),'text'=>$body['text']??''];
            $list[]=$new; db_write('announcements',$list); respond(201,$new);
        }
        respond(405,['error'=>'Method not allowed']);
        break;

    default:
        respond(404, ['error'=>"Yol topilmadi: /$resource", 'routes'=>['POST /login','GET /students','POST /students','PUT /students/{id}','DELETE /students/{id}','GET /teachers','POST /teachers','GET /grades','POST /grades','DELETE /grades/{id}','GET /stats','GET /announcements','POST /announcements']]);
}

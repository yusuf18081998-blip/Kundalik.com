<?php
// 1. BRAUZER XAVFSIZLIK TIZIMI (CORS) UCHUN ENG TEPA REJIMDA RUXSAT BERISH
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Max-Age: 86400"); // 1 kunlik kesh brauzer uchun
}

// 2. OPTIONS (Preflight) so'rovi kelganda srazu ruxsat berib, kodni to'xtatish
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
    }
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    }
    http_response_code(200);
    exit(0);
}

// Qolgan standart sarlavhalar
header("Content-Type: application/json; charset=UTF-8");

// Ma'lumotlar papkasi sozlamalari
$dataDir = __DIR__ . '/data';
if (!file_exists($dataDir)) {
    mkdir($dataDir, 0777, true);
}

$studentsFile  = $dataDir . '/students.json';
$teachersFile  = $dataDir . '/teachers.json';
$usersFile     = $dataDir . '/users.json';
$gradesFile    = $dataDir . '/grades.json';
$quarterFile   = $dataDir . '/quarter_grades.json';
$timetableFile = $dataDir . '/timetable.json';

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = trim($path, '/');

function getData($file, $default = []) {
    if (!file_exists($file)) return $default;
    return json_decode(file_get_contents($file), true) ?? $default;
}

function saveData($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// --- MARSHRUTLAR ---

if ($path === 'login') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if ($input['login'] === 'admin' && $input['password'] === 'admin123') {
            echo json_encode(["user" => ["name" => "Tizim Administratori", "role" => "admin"]]);
            exit;
        }
        
        $users = getData($usersFile);
        foreach ($users as $u) {
            if ($u['login'] === $input['login'] && $u['password'] === $input['password']) {
                echo json_encode(["user" => ["name" => $u['name'], "role" => $u['role']]]);
                exit;
            }
        }
        
        http_response_code(401);
        echo json_encode(["error" => "Login yoki parol xato!"]);
    }
}
elseif ($path === 'students') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $students = getData($studentsFile);
        $input['id'] = count($students) + 1;
        $students[] = $input;
        saveData($studentsFile, $students);
        
        $users = getData($usersFile);
        $users[] = ["login" => $input['login'], "password" => $input['password'], "name" => $input['name'], "role" => "student"];
        saveData($usersFile, $users);
        
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(getData($studentsFile));
    }
}
elseif ($path === 'teachers') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $teachers = getData($teachersFile);
        $input['id'] = count($teachers) + 1;
        $teachers[] = $input;
        saveData($teachersFile, $teachers);
        
        $users = getData($usersFile);
        $users[] = ["login" => $input['login'], "password" => $input['password'], "name" => $input['name'], "role" => "teacher"];
        saveData($usersFile, $users);
        
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(getData($teachersFile));
    }
}
elseif ($path === 'grades') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $grades = getData($gradesFile);
        $grades = array_filter($grades, function($g) use ($input) {
            return !($g['studentId'] == $input['studentId'] && $g['day'] == $input['day'] && $g['subject'] == $input['subject']);
        });
        if (!empty($input['grade'])) {
            $grades[] = $input;
        }
        saveData($gradesFile, array_values($grades));
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(getData($gradesFile));
    }
}
elseif ($path === 'quarter_grades') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        saveData($quarterFile, $input);
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(getData($quarterFile));
    }
}
elseif ($path === 'timetable') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        saveData($timetableFile, $input);
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(getData($timetableFile, new stdClass()));
    }
}
else {
    echo json_encode(["status" => "online", "message" => "API is working perfectly."]);
}

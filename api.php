<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Render serverida ma'lumotlar o'chib ketmasligi uchun vaqtinchalik 'data' papkasi
$dataDir = __DIR__ . '/data';
if (!file_exists($dataDir)) {
    mkdir($dataDir, 0777, true);
}

$studentsFile = $dataDir . '/students.json';
$teachersFile = $dataDir . '/teachers.json';
$gradesFile = $dataDir . '/grades'; // papka yoki fayl sifatida
$quarterFile = $dataDir . '/quarter_grades.json';
$timetableFile = $dataDir . '/timetable.json';

$path = $_SERVER['REQUEST_URI'];
$path = parse_url($path, PHP_URL_PATH);
$path = trim($path, '/');

// Oddiygina marshrutizatsiya (Routing)
if ($path === 'students') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $current = file_exists($studentsFile) ? json_decode(file_get_contents($studentsFile), true) : [];
        $input['id'] = count($current) + 1;
        $current[] = $input;
        file_put_contents($studentsFile, json_encode($current, JSON_PRETTY_PRINT));
        
        // Akkauntini ham yaratish
        $usersFile = $dataDir . '/users.json';
        $users = file_exists($usersFile) ? json_decode(file_get_contents($usersFile), true) : [];
        $users[] = ["login" => $input['login'], "password" => $input['password'], "name" => $input['name'], "role" => "student"];
        file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
        
        echo json_encode(["status" => "success"]);
    } else {
        echo file_exists($studentsFile) ? file_get_contents($studentsFile) : "[]";
    }
} 
// Qolgan metodlar (teachers, grades va h.k.) ham shu zaylda davom etadi...
elseif ($path === 'teachers') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $current = file_exists($teachersFile) ? json_decode(file_get_contents($teachersFile), true) : [];
        $input['id'] = count($current) + 1;
        $current[] = $input;
        file_put_contents($teachersFile, json_encode($current, JSON_PRETTY_PRINT));
        
        $usersFile = $dataDir . '/users.json';
        $users = file_exists($usersFile) ? json_decode(file_get_contents($usersFile), true) : [];
        $users[] = ["login" => $input['login'], "password" => $input['password'], "name" => $input['name'], "role" => "teacher"];
        file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
        
        echo json_encode(["status" => "success"]);
    } else {
        echo file_exists($teachersFile) ? file_get_contents($teachersFile) : "[]";
    }
}
elseif ($path === 'grades') {
    $gradesJsonFile = $dataDir . '/grades.json';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $current = file_exists($gradesJsonFile) ? json_decode(file_get_contents($gradesJsonFile), true) : [];
        // Eskisini o'chirib yangisini yozish (baho yangilansa)
        $current = array_filter($current, function($g) use ($input) {
            return !($g['studentId'] == $input['studentId'] && $g['day'] == $input['day'] && $g['subject'] == $input['subject']);
        });
        if($input['grade'] !== '') {
            $current[] = $input;
        }
        file_put_contents($gradesJsonFile, json_encode(array_values($current), JSON_PRETTY_PRINT));
        echo json_encode(["status" => "success"]);
    } else {
        echo file_exists($gradesJsonFile) ? file_get_contents($gradesJsonFile) : "[]";
    }
}
elseif ($path === 'quarter_grades') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        file_put_contents($quarterFile, json_encode($input, JSON_PRETTY_PRINT));
        echo json_encode(["status" => "success"]);
    } else {
        echo file_exists($quarterFile) ? file_get_contents($quarterFile) : "[]";
    }
}
elseif ($path === 'timetable') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        file_put_contents($timetableFile, json_encode($input, JSON_PRETTY_PRINT));
        echo json_encode(["status" => "success"]);
    } else {
        echo file_exists($timetableFile) ? file_get_contents($timetableFile) : "{}";
    }
}
elseif ($path === 'login') {
    $input = json_decode(file_get_contents('php://input'), true);
    $usersFile = $dataDir . '/users.json';
    
    // Standart Admin akkaunti
    if($input['login'] === 'admin' && $input['password'] === 'admin123') {
        echo json_encode(["user" => ["name" => "Tizim Administratori", "role" => "admin"]]);
        exit;
    }
    
    $users = file_exists($usersFile) ? json_decode(file_get_contents($usersFile), true) : [];
    foreach($users as $u) {
        if($u['login'] === $input['login'] && $u['password'] === $input['password']) {
            echo json_encode(["user" => ["name" => $u['name'], "role" => $u['role']]]);
            exit;
        }
    }
    http_response_code(401);
    echo json_encode(["error" => "Login yoki parol xato!"]);
}
else {
    echo json_encode(["message" => "45-Maktab API Ekotizimi ishlamoqda."]);
}

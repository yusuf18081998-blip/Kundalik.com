<?php
// Brauzer xavfsizlik cheklovlarini (CORS) chetlab o'tish uchun sarlavhalar
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Content-Type: application/json; charset=UTF-8");

// Agar brauzer dastlabki tekshirish (Preflight OPTIONS) so'rovini yuborsa, srazu ruxsat berish
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Ma'lumotlar saqlanadigan papka yo'li
$dataDir = __DIR__ . '/data';
if (!file_exists($dataDir)) {
    mkdir($dataDir, 0777, true);
}

// JSON fayllar yo'llari
$studentsFile  = $dataDir . '/students.json';
$teachersFile  = $dataDir . '/teachers.json';
$usersFile     = $dataDir . '/users.json';
$gradesFile    = $dataDir . '/grades.json';
$quarterFile   = $dataDir . '/quarter_grades.json';
$timetableFile = $dataDir . '/timetable.json';

// Kelayotgan URL manzilini aniqlash (marshrutizatsiya uchun)
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = trim($path, '/');

// Yordamchi funksiya: fayldan ma'lumotni o'qish
function getData($file, $default = []) {
    if (!file_exists($file)) return $default;
    $content = file_get_contents($file);
    return json_decode($content, true) ?? $default;
}

// Yordamchi funksiya: faylga ma'lumotni yozish
function saveData($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// --- MARSHRUTLAR (ROUTING) ---

// 1. Tizimga kirish (Login)
if ($path === 'login') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Standart Administrator akkaunti
        if ($input['login'] === 'admin' && $input['password'] === 'admin123') {
            echo json_encode(["user" => ["name" => "Tizim Administratori", "role" => "admin"]]);
            exit;
        }
        
        // Boshqa foydalanuvchilarni tekshirish (O'qituvchi / O'quvchi)
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

// 2. O'quvchilar bilan ishlash
elseif ($path === 'students') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $students = getData($studentsFile);
        
        $input['id'] = count($students) + 1;
        $students[] = $input;
        saveData($studentsFile, $students);
        
        // O'quvchi uchun avomatik tizim akkauntini yaratish
        $users = getData($usersFile);
        $users[] = [
            "login" => $input['login'], 
            "password" => $input['password'], 
            "name" => $input['name'], 
            "role" => "student"
        ];
        saveData($usersFile, $users);
        
        echo json_encode(["status" => "success", "message" => "O'quvchi muvaffaqiyatli qo'shildi!"]);
    } else {
        echo json_encode(getData($studentsFile));
    }
}

// 3. O'qituvchilar bilan ishlash
elseif ($path === 'teachers') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $teachers = getData($teachersFile);
        
        $input['id'] = count($teachers) + 1;
        $teachers[] = $input;
        saveData($teachersFile, $teachers);
        
        // O'qituvchi uchun avomatik tizim akkauntini yaratish
        $users = getData($usersFile);
        $users[] = [
            "login" => $input['login'], 
            "password" => $input['password'], 
            "name" => $input['name'], 
            "role" => "teacher"
        ];
        saveData($usersFile, $users);
        
        echo json_encode(["status" => "success", "message" => "O'qituvchi muvaffaqiyatli qo'shildi!"]);
    } else {
        echo json_encode(getData($teachersFile));
    }
}

// 4. Kundalik baholar bilan ishlash
elseif ($path === 'grades') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $grades = getData($gradesFile);
        
        // Agar o'sha kuni o'sha fandan eski baho bo'lsa, o'chirib tashlash (yangilash uchun)
        $grades = array_filter($grades, function($g) use ($input) {
            return !($g['studentId'] == $input['studentId'] && $g['day'] == $input['day'] && $g['subject'] == $input['subject']);
        });
        
        // Agar yangi baho bo'sh bo'lmasa, ro'yxatga qo'shish
        if (!empty($input['grade'])) {
            $grades[] = $input;
        }
        
        saveData($gradesFile, array_values($grades));
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(getData($gradesFile));
    }
}

// 5. Choraklik baholar
elseif ($path === 'quarter_grades') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        saveData($quarterFile, $input);
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(getData($quarterFile));
    }
}

// 6. Dars jadvali
elseif ($path === 'timetable') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        saveData($timetableFile, $input);
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(getData($timetableFile, new stdClass()));
    }
}

// Asosiy sahifa (Server holatini tekshirish uchun)
else {
    echo json_encode([
        "status" => "online",
        "project" => "45-Maktab Elektron Kundalik API Ekotizimi",
        "author" => "Muhammad Yusuf Xo'jayev"
    ]);
}

<?php
session_start();

$db_host = 'team601-mysql.team601.mysql.database.azure.com';
$db_user = 'ijo';
$db_pass = 'It12345@';
$db_name = 'wordpress';

function getDB() {
    global $db_host, $db_user, $db_pass, $db_name;
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'DB 연결 실패']);
        exit;
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

// users 테이블 없으면 자동 생성
function initTable() {
    $conn = getDB();
    $conn->query("
        CREATE TABLE IF NOT EXISTS shop_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) NOT NULL UNIQUE,
            email VARCHAR(200) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $conn->close();
}

initTable();

$action = $_POST['action'] ?? '';
header('Content-Type: application/json');

// 회원가입
if ($action === 'register') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$email || !$password) {
        echo json_encode(['success' => false, 'message' => '모든 항목을 입력해주세요.']);
        exit;
    }
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => '비밀번호는 6자 이상이어야 합니다.']);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => '이메일 형식이 올바르지 않습니다.']);
        exit;
    }

    $conn = getDB();
    $hashed = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("INSERT INTO shop_users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param('sss', $username, $email, $hashed);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => '회원가입이 완료되었습니다.']);
    } else {
        if ($conn->errno === 1062) {
            echo json_encode(['success' => false, 'message' => '이미 사용 중인 아이디 또는 이메일입니다.']);
        } else {
            echo json_encode(['success' => false, 'message' => '회원가입 실패: ' . $conn->error]);
        }
    }
    $stmt->close();
    $conn->close();
    exit;
}

// 로그인
if ($action === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        echo json_encode(['success' => false, 'message' => '아이디와 비밀번호를 입력해주세요.']);
        exit;
    }

    $conn = getDB();
    $stmt = $conn->prepare("SELECT id, username, password FROM shop_users WHERE username = ? OR email = ?");
    $stmt->bind_param('ss', $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        echo json_encode(['success' => true, 'message' => '로그인 성공', 'username' => $user['username']]);
    } else {
        echo json_encode(['success' => false, 'message' => '아이디 또는 비밀번호가 올바르지 않습니다.']);
    }
    $stmt->close();
    $conn->close();
    exit;
}

// 계정 찾기 (이메일 존재 여부 확인)
if ($action === 'find') {
    $email = trim($_POST['email'] ?? '');

    if (!$email) {
        echo json_encode(['success' => false, 'message' => '이메일을 입력해주세요.']);
        exit;
    }

    $conn = getDB();
    $stmt = $conn->prepare("SELECT username FROM shop_users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        echo json_encode(['success' => true, 'message' => '가입된 계정이 확인되었습니다. 아이디: ' . $user['username']]);
    } else {
        echo json_encode(['success' => false, 'message' => '해당 이메일로 가입된 계정이 없습니다.']);
    }
    $stmt->close();
    $conn->close();
    exit;
}

// 로그아웃
if ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true, 'message' => '로그아웃 되었습니다.']);
    exit;
}

echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);

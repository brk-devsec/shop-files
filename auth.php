<?php
session_start();

$db_host = '192.168.3.6';
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

function initTable() {
    $conn = getDB();
    // 기본 회원 테이블
    $conn->query("
        CREATE TABLE IF NOT EXISTS shop_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) NOT NULL UNIQUE,
            email VARCHAR(200) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            name VARCHAR(100),
            phone VARCHAR(20),
            zipcode VARCHAR(10),
            address VARCHAR(300),
            address_detail VARCHAR(200),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    // 주문 테이블
    $conn->query("
        CREATE TABLE IF NOT EXISTS shop_orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            product_name VARCHAR(200) NOT NULL,
            price INT NOT NULL,
            status VARCHAR(50) DEFAULT '주문완료',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES shop_users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    // 찜 목록 테이블
    $conn->query("
        CREATE TABLE IF NOT EXISTS shop_wishlist (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            product_name VARCHAR(200) NOT NULL,
            price INT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES shop_users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $conn->close();
}

initTable();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
header('Content-Type: application/json; charset=utf-8');

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
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        echo json_encode(['success' => true, 'message' => '로그인 성공', 'username' => $user['username']]);
    } else {
        echo json_encode(['success' => false, 'message' => '아이디 또는 비밀번호가 올바르지 않습니다.']);
    }
    $stmt->close();
    $conn->close();
    exit;
}

// 로그인 상태 확인
if ($action === 'check') {
    if (isset($_SESSION['user_id'])) {
        echo json_encode(['success' => true, 'username' => $_SESSION['username'], 'user_id' => $_SESSION['user_id']]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

// 로그아웃
if ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true]);
    exit;
}

// 회원정보 조회
if ($action === 'get_profile') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
        exit;
    }
    $conn = getDB();
    $stmt = $conn->prepare("SELECT username, email, name, phone, zipcode, address, address_detail FROM shop_users WHERE id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    echo json_encode(['success' => true, 'data' => $user]);
    $stmt->close();
    $conn->close();
    exit;
}

// 회원정보 수정
if ($action === 'update_profile') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
        exit;
    }
    $name           = trim($_POST['name'] ?? '');
    $phone          = trim($_POST['phone'] ?? '');
    $zipcode        = trim($_POST['zipcode'] ?? '');
    $address        = trim($_POST['address'] ?? '');
    $address_detail = trim($_POST['address_detail'] ?? '');

    $conn = getDB();
    $stmt = $conn->prepare("UPDATE shop_users SET name=?, phone=?, zipcode=?, address=?, address_detail=? WHERE id=?");
    $stmt->bind_param('sssssi', $name, $phone, $zipcode, $address, $address_detail, $_SESSION['user_id']);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => '회원정보가 저장되었습니다.']);
    } else {
        echo json_encode(['success' => false, 'message' => '저장 실패: ' . $conn->error]);
    }
    $stmt->close();
    $conn->close();
    exit;
}

// 주문내역 조회
if ($action === 'get_orders') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
        exit;
    }
    $conn = getDB();
    $stmt = $conn->prepare("SELECT * FROM shop_orders WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $orders]);
    $stmt->close();
    $conn->close();
    exit;
}

// 찜 목록 조회
if ($action === 'get_wishlist') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
        exit;
    }
    $conn = getDB();
    $stmt = $conn->prepare("SELECT * FROM shop_wishlist WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $wishlist = [];
    while ($row = $result->fetch_assoc()) {
        $wishlist[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $wishlist]);
    $stmt->close();
    $conn->close();
    exit;
}

// 찜 추가
if ($action === 'add_wishlist') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
        exit;
    }
    $product_name = trim($_POST['product_name'] ?? '');
    $price        = intval($_POST['price'] ?? 0);

    $conn = getDB();
    $stmt = $conn->prepare("INSERT INTO shop_wishlist (user_id, product_name, price) VALUES (?, ?, ?)");
    $stmt->bind_param('isi', $_SESSION['user_id'], $product_name, $price);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => '찜 목록에 추가되었습니다.']);
    } else {
        echo json_encode(['success' => false, 'message' => '찜 추가 실패']);
    }
    $stmt->close();
    $conn->close();
    exit;
}

echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);

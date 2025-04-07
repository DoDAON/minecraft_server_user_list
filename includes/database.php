<?php
require_once 'config.php';

function getDbConnection() {
    $db = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$db) {
        throw new Exception('데이터베이스 연결 실패: ' . mysqli_connect_error());
    }
    return $db;
}

function initializeDatabase($db) {
    $createTableQuery = "CREATE TABLE IF NOT EXISTS player_list_cache (
        id INT AUTO_INCREMENT PRIMARY KEY,
        server_ip VARCHAR(255) NOT NULL,
        server_port INT NOT NULL,
        player_list TEXT NOT NULL,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY server_key (server_ip, server_port)
    )";
    mysqli_query($db, $createTableQuery);
}

function getCachedPlayerList($db, $server, $port) {
    $query = "SELECT player_list, TIMESTAMPDIFF(SECOND, last_updated, NOW()) as age 
              FROM player_list_cache 
              WHERE server_ip = ? AND server_port = ?";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "si", $server, $port);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        if ($row['age'] < LIST_CACHE_DURATION) {
            return json_decode($row['player_list'], true);
        }
    }
    return null;
}

function updatePlayerListCache($db, $server, $port, $playerList) {
    $query = "INSERT INTO player_list_cache (server_ip, server_port, player_list) 
              VALUES (?, ?, ?) 
              ON DUPLICATE KEY UPDATE 
              player_list = VALUES(player_list), 
              last_updated = CURRENT_TIMESTAMP";
    $stmt = mysqli_prepare($db, $query);
    $jsonList = json_encode($playerList);
    mysqli_stmt_bind_param($stmt, "sis", $server, $port, $jsonList);
    mysqli_stmt_execute($stmt);
}

function getHangulName($db, $uuid) {
    $query = "SELECT hangul FROM members WHERE uuid = ?";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "s", $uuid);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        return $row['hangul'];
    }
    return null;
}

function getPlayerUUID($username, $db) {
    logMessage("플레이어 UUID 조회 시작: $username");
    
    // 캐시에서 먼저 확인
    $query = "SELECT uuid, TIMESTAMPDIFF(MINUTE, last_updated, NOW()) as age 
              FROM player_cache 
              WHERE username = ?";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        if ($row['age'] < PLAYER_CACHE_DURATION) {
            logMessage("캐시된 UUID 사용: $username");
            return $row['uuid'];
        }
    }

    // Mojang API 호출
    $url = MOJANG_API_URL . urlencode($username);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($response && $httpCode == 200) {
        $data = json_decode($response, true);
        $uuid = $data['id'] ?? null;
        
        if ($uuid) {
            logMessage("Mojang API에서 UUID 조회 성공: $username -> $uuid");
            updatePlayerUUIDCache($db, $username, $uuid);
            return $uuid;
        }
    }
    
    if ($httpCode == 429) {
        logMessage("API 요청 제한 도달, 2초 후 재시도: $username");
        sleep(2);
        return getPlayerUUID($username, $db);
    }
    
    logMessage("UUID 조회 실패: $username");
    return null;
}

function updatePlayerUUIDCache($db, $username, $uuid) {
    $query = "INSERT INTO player_cache (username, uuid) VALUES (?, ?) 
              ON DUPLICATE KEY UPDATE uuid = ?, last_updated = CURRENT_TIMESTAMP";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "sss", $username, $uuid, $uuid);
    mysqli_stmt_execute($stmt);
} 
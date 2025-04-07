<?php
// 로깅 함수
function logMessage($message, $type = 'info') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents('server_status.log', $logMessage, FILE_APPEND);
    
    // 안전한 콘솔 로그 출력 (메시지 이스케이프 처리)
    $safeMessage = addslashes($message);
    echo "<script>safeLog('$type', '$safeMessage');</script>\n";
}

// 마인크래프트 서버 통신 함수들
function sendPacket($socket, $data) {
    $length = strlen($data);
    $packet = pack('c', $length) . $data;
    fwrite($socket, $packet);
}

function readVarInt($socket) {
    $value = 0;
    $position = 0;
    while (true) {
        $current = ord(fread($socket, 1));
        $value |= ($current & 0x7F) << $position;
        if (($current & 0x80) == 0) break;
        $position += 7;
        if ($position >= 32) throw new Exception('VarInt too big');
    }
    return $value;
}

// HTML 출력 함수들
function renderServerInfo($data) {
    echo '<div class="bg-white/10 p-6 rounded-lg border border-white/20">';
    echo '<div class="grid grid-cols-1 md:grid-cols-3 gap-4">';
    echo '<div class="text-white"><span class="font-semibold">서버 상태:</span> <span class="text-emerald-300">온라인</span></div>';
    echo '<div class="text-white"><span class="font-semibold">서버 버전:</span> <span class="text-white/90">' . htmlspecialchars($data['version']['name']) . '</span></div>';
    echo '<div class="text-white"><span class="font-semibold">온라인 플레이어:</span> <span class="text-white/90">' . $data['players']['online'] . ' / ' . $data['players']['max'] . '</span></div>';
    echo '</div>';
    echo '</div>';
}

function renderPlayerCard($card) {
    echo '<div class="aspect-square bg-white/10 rounded-lg p-4 border border-white/20 hover:border-white/40 transition duration-300 transform hover:scale-105">';
    echo '<div class="flex flex-col items-center justify-center h-full">';
    
    $uuid = $card['uuid'] === 'steve' ? DEFAULT_STEVE_UUID : $card['uuid'];
    echo '<img class="w-16 h-16 md:w-20 md:h-20 rounded-lg mb-3" src="https://crafatar.com/renders/head/' . $uuid . '?overlay" alt="' . $card['username'] . '">';
    
    if ($card['hangul']) {
        echo '<div class="text-white text-center">';
        echo '<div class="font-semibold text-sm md:text-base">' . htmlspecialchars($card['hangul']) . '</div>';
        echo '<div class="text-white/70 text-xs md:text-sm">' . htmlspecialchars($card['username']) . '</div>';
        echo '</div>';
    } else {
        echo '<div class="text-white font-semibold text-sm md:text-base text-center">' . htmlspecialchars($card['username']) . '</div>';
    }
    
    echo '</div>';
    echo '</div>';
}

// 데이터 가공 함수
function prepareFinalData($data, $players, $successCount, $failCount) {
    return [
        'server_status' => [
            'version' => $data['version']['name'],
            'online_players' => $data['players']['online'],
            'max_players' => $data['players']['max']
        ],
        'player_stats' => [
            'total_players' => count($players),
            'success_count' => $successCount,
            'fail_count' => $failCount
        ],
        'players' => array_map(function($card) {
            return [
                'username' => $card['username'],
                'has_uuid' => $card['uuid'] !== 'steve',
                'has_hangul' => !empty($card['hangul']),
                'hangul_name' => $card['hangul'] ?: null
            ];
        }, $players)
    ];
} 
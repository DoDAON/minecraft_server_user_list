<?php
require_once 'config.php';
require_once 'includes/utils.php';
require_once 'includes/database.php';

function fetchPlayerList($server, $port, $handshake, $statusRequest) {
    $players = [];
    $socket = @fsockopen($server, $port, $errno, $errstr, 5);
    
    if ($socket) {
        sendPacket($socket, $handshake);
        sendPacket($socket, $statusRequest);
        
        $length = readVarInt($socket);
        $packetId = readVarInt($socket);
        $jsonLength = readVarInt($socket);
        $json = fread($socket, $jsonLength);
        
        fclose($socket);
        
        $data = json_decode($json, true);
        if (isset($data['players']['sample'])) {
            foreach ($data['players']['sample'] as $player) {
                $players[$player['name']] = $player['name'];
            }
        }
    }
    
    return $players;
}

function collectPlayers($server, $port, $handshake, $statusRequest, $targetCount) {
    $collectionStartTime = microtime(true);
    $players = [];
    $totalAttempts = 0;
    $consecutiveSameCount = 0;
    $lastCount = 0;
    
    while (count($players) < $targetCount) {
        if (microtime(true) - $collectionStartTime > MAX_COLLECTION_TIME) {
            logMessage("경고: 최대 수집 시간 초과", "warn");
            break;
        }
        
        $batchAttempts = 0;
        $retryDelay = INITIAL_RETRY_DELAY;
        
        while ($batchAttempts < MAX_BATCH_ATTEMPTS) {
            $newPlayers = fetchPlayerList($server, $port, $handshake, $statusRequest);
            $previousCount = count($players);
            
            foreach ($newPlayers as $player) {
                $players[$player] = $player;
            }
            
            $currentCount = count($players);
            logMessage("수집 시도 " . ($totalAttempts + 1) . " (배치 " . ($batchAttempts + 1) . "): " . 
                      "$previousCount -> $currentCount (목표: $targetCount)");
            
            if ($currentCount === $lastCount) {
                $consecutiveSameCount++;
                if ($consecutiveSameCount >= 5) {
                    logMessage("5번 연속 동일한 결과, 대기 시간 증가");
                    sleep(2);
                    $consecutiveSameCount = 0;
                }
            } else {
                $consecutiveSameCount = 0;
            }
            
            $lastCount = $currentCount;
            
            if ($currentCount >= $targetCount) {
                logMessage("목표 플레이어 수 달성!");
                return array_values($players);
            }
            
            $batchAttempts++;
            $totalAttempts++;
            
            if ($batchAttempts < MAX_BATCH_ATTEMPTS) {
                usleep($retryDelay);
                $retryDelay = min($retryDelay * 1.5, MAX_RETRY_DELAY);
            }
        }
        
        sleep(1);
    }
    
    return array_values($players);
}

try {
    $db = getDbConnection();
    initializeDatabase($db);
    
    $socket = @fsockopen(MC_SERVER, MC_PORT, $errno, $errstr, 5);
    if (!$socket) {
        throw new Exception("서버에 연결할 수 없습니다: $errstr ($errno)");
    }
    
    $handshake = pack('c', 0x00) . 
                 pack('c', 0x00) . 
                 pack('c', strlen(MC_SERVER)) . MC_SERVER . 
                 pack('n', MC_PORT) . 
                 pack('c', 0x01);
    
    sendPacket($socket, $handshake);
    sendPacket($socket, pack('c', 0x00));
    
    $length = readVarInt($socket);
    $packetId = readVarInt($socket);
    $jsonLength = readVarInt($socket);
    $json = fread($socket, $jsonLength);
    
    fclose($socket);
    
    $data = json_decode($json, true);
    
    if ($data) {
        logMessage("서버 상태 조회 시작 - 온라인 플레이어 수: " . $data['players']['online']);
        
        $cachedPlayers = getCachedPlayerList($db, MC_SERVER, MC_PORT);
        
        if ($cachedPlayers && count($cachedPlayers) >= $data['players']['online']) {
            logMessage("캐시된 플레이어 목록 사용 - 캐시된 플레이어 수: " . count($cachedPlayers));
            $players = $cachedPlayers;
        } else {
            logMessage("새로운 플레이어 목록 수집 시작");
            $players = collectPlayers(MC_SERVER, MC_PORT, $handshake, pack('c', 0x00), $data['players']['online']);
            
            if (count($players) >= $data['players']['online']) {
                updatePlayerListCache($db, MC_SERVER, MC_PORT, $players);
            }
        }
        
        echo '<div class="space-y-6">';
        renderServerInfo($data);
        
        echo '<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">';
        
        if ($data['players']['online'] > 0) {
            logMessage("플레이어 카드 생성 시작 - 총 " . count($players) . "명");
            
            usort($players, 'strcasecmp');
            
            $playerCards = [];
            $successCount = $failCount = 0;
            
            foreach ($players as $username) {
                $uuid = getPlayerUUID($username, $db);
                $hangul = $uuid ? getHangulName($db, $uuid) : null;
                
                if ($uuid) {
                    $successCount++;
                    logMessage("플레이어 카드 생성 성공: $username");
                } else {
                    $failCount++;
                    logMessage("플레이어 카드 생성 실패: $username");
                }
                
                $playerCards[] = [
                    'username' => $username,
                    'uuid' => $uuid ?: 'steve',
                    'hangul' => $hangul
                ];
                
                usleep(100000);
            }
            
            $finalData = prepareFinalData($data, $players, $successCount, $failCount);
            echo "<script>console.log('최종 가공된 데이터:', " . 
                 json_encode($finalData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . ");</script>";
            
            foreach ($playerCards as $card) {
                renderPlayerCard($card);
            }
        } else {
            echo '<div class="col-span-full text-center p-6 bg-[#2a5f9a]/20 border border-[#E63E3E]/30 rounded-lg">';
            echo '<p class="text-[#b5d0ff]">현재 접속 중인 플레이어가 없습니다.</p>';
            echo '</div>';
        }
        
        echo '</div></div>';
    } else {
        throw new Exception("서버에서 정보를 가져올 수 없습니다.");
    }
} catch (Exception $e) {
    echo '<div class="text-center p-6 bg-[#2a5f9a]/20 border border-[#E63E3E]/30 rounded-lg">';
    echo '<p class="text-[#b5d0ff]">오류 발생: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
} finally {
    if (isset($db)) {
        mysqli_close($db);
    }
}
?> 
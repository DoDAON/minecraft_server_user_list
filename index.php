<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta property="og:type" content="website"/>
    <meta property="og:locale" content="ko"/>
    <meta property="og:site_name" content="마인크래프트 서버"/>
    <title>마인크래프트 서버 플레이어 목록</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .neon-glow {
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.5),
                         0 0 20px rgba(255, 255, 255, 0.3),
                         0 0 30px rgba(255, 255, 255, 0.2);
        }
        .loading-spinner {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid #E63E3E;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-[#242952] via-[#2a5f9a] to-[#E63E3E]/40 min-h-screen">
    <div class="loading-overlay fixed inset-0 bg-[#242952]/90 backdrop-blur-lg flex flex-col items-center justify-center z-50" id="loadingOverlay">
        <div class="loading-spinner mb-6"></div>
        <div class="text-[#b5d0ff] text-xl neon-glow">서버 유저 목록을 불러오는 중입니다...</div>
    </div>

    <div id="content" class="container mx-auto px-4 py-16 hidden">
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-12">
                <h1 class="text-4xl md:text-6xl font-bold text-white neon-glow mb-6">
                    서버 온라인 플레이어 목록
                </h1>
            </div>
            <div class="bg-white/20 backdrop-blur-lg rounded-lg p-8 shadow-2xl border border-[#E63E3E]/30">
                <div id="serverContent"></div>
            </div>
        </div>
    </div>

    <script>
    // 안전한 로깅 함수
    function safeLog(type, ...args) {
        try {
            if (typeof console !== 'undefined' && console[type]) {
                console[type](...args);
            }
        } catch(e) {}
    }

    document.addEventListener('DOMContentLoaded', function() {
        safeLog('log', '서버 상태 페이지 로딩 시작');
        fetchData();
    });

    function fetchData() {
        safeLog('log', '서버 데이터 요청 시작');
        fetch('get_server_data.php')
            .then(response => {
                if (!response.ok) {
                    safeLog('error', '서버 응답 오류:', response.status, response.statusText);
                    throw new Error('서버 응답이 올바르지 않습니다.');
                }
                safeLog('log', '서버 응답 수신 완료');
                return response.text();
            })
            .then(html => {
                safeLog('log', '데이터 처리 시작');
                document.getElementById('serverContent').innerHTML = html;
                document.getElementById('loadingOverlay').style.display = 'none';
                document.getElementById('content').classList.remove('hidden');
                safeLog('log', '데이터 표시 완료');
            })
            .catch(error => {
                safeLog('error', '데이터 로딩 중 오류:', error);
                setTimeout(fetchData, 3000);
            });
    }
    </script>
</body>
</html>

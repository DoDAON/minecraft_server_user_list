네트워크 통신이랑 DB, 캐시 정책 등 CS지식이 많이 부족해서 코드짜는 시간보다 공부하는 시간이 더 많았네... 아자아자 화이팅 더 공부하자

# 마인크래프트 서버 상태 조회 시스템 문서

## 1. 시스템 개요

이 시스템은 마인크래프트 서버의 상태를 실시간으로 조회하고, 온라인 플레이어 목록을 표시하는 웹 애플리케이션입니다. PHP를 사용하여 구현되었으며, 마인크래프트 서버 프로토콜을 직접 구현하여 서버와 통신합니다.

## 2. 시스템 구성

### 2.1 파일 구조
```
project/
├── index.php              # 메인 프론트엔드 페이지
├── get_server_data.php    # 서버 상태 및 플레이어 정보를 조회하는 백엔드 API
├── config.php             # 설정 파일
├── .env                   # 환경 변수 파일 (민감한 정보 저장)
├── .env.example          # 환경 변수 예시 파일
├── composer.json         # Composer 의존성 정의
├── composer.lock         # Composer 의존성 버전 고정
└── includes/
    ├── database.php      # 데이터베이스 관련 함수
    └── utils.php         # 유틸리티 함수
```

### 2.2 주요 기능

- 서버 상태 실시간 조회
- 온라인 플레이어 목록 표시
- 플레이어 UUID와 한글 이름 매핑
- 데이터 캐싱을 통한 성능 최적화
- 반응형 UI 구현
- 자동 새로고침 기능

## 3. 설치 및 설정

### 3.1 요구사항
- PHP 7.4 이상
- MySQL 5.7 이상
- Composer
- Apache/Nginx 웹 서버

### 3.2 설치 방법

1. 저장소 클론:
```bash
git clone [repository-url]
cd [project-directory]
```

2. Composer 의존성 설치:
```bash
composer install
```

3. 환경 변수 설정:
```bash
cp .env.example .env
```
`.env` 파일을 열어 실제 값으로 수정:
```env
# 서버 설정
MC_SERVER=your.minecraft.server
MC_PORT=25565

# 데이터베이스 설정
DB_HOST=localhost
DB_USER=your_db_user
DB_PASS=your_db_password
DB_NAME=your_db_name

# 캐시 설정
PLAYER_CACHE_DURATION=1440
LIST_CACHE_DURATION=30

# 수집 설정
MAX_COLLECTION_TIME=30
MAX_BATCH_ATTEMPTS=5
INITIAL_RETRY_DELAY=300000
MAX_RETRY_DELAY=1000000

# API 설정
MOJANG_API_URL=https://api.mojang.com/users/profiles/minecraft/
DEFAULT_STEVE_UUID=8667ba71-b85a-4004-af54-457a9734eed7
```

## 4. 시스템 구조

### 4.1 프론트엔드 (index.php)

#### 4.1.1 UI 구성
```html
- 로딩 오버레이
  - 스피너 애니메이션
  - 로딩 메시지
- 메인 컨텐츠 영역
  - 서버 정보 섹션
  - 플레이어 목록 그리드
```

#### 4.1.2 스타일링
```css
- Tailwind CSS 사용
- 커스텀 애니메이션
  - 네온 글로우 효과
  - 로딩 스피너
  - 호버 효과
```

#### 4.1.3 JavaScript 기능
```javascript
- 페이지 로드 시 자동 데이터 조회
- 에러 발생 시 자동 재시도
- 3초 간격으로 데이터 갱신
```

### 4.2 백엔드 (get_server_data.php)

#### 4.2.1 데이터베이스 구조
```sql
-- 플레이어 목록 캐시 테이블
CREATE TABLE player_list_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    server_ip VARCHAR(255) NOT NULL,
    server_port INT NOT NULL,
    player_list TEXT NOT NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY server_key (server_ip, server_port)
);

-- 플레이어 캐시 테이블
CREATE TABLE player_cache (
    username VARCHAR(255) PRIMARY KEY,
    uuid VARCHAR(36) NOT NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 회원 정보 테이블
CREATE TABLE members (
    uuid VARCHAR(36) PRIMARY KEY,
    hangul VARCHAR(255) NOT NULL
);
```

#### 4.2.2 핵심 함수

##### 4.2.2.1 패킷 처리 함수
```php
function sendPacket($socket, $data) {
    // 소켓에 데이터를 전송하는 함수
    // 패킷 길이와 데이터를 함께 전송
}

function readVarInt($socket) {
    // 가변 길이 정수(VarInt)를 읽는 함수
    // 마인크래프트 프로토콜에서 사용되는 데이터 형식
}
```

##### 4.2.2.2 플레이어 정보 처리 함수
```php
function getHangulName($db_connect, $uuid) {
    // UUID를 기반으로 한글 이름을 조회하는 함수
}

function getPlayerUUID($username, $db_connect) {
    // 플레이어 이름으로 UUID를 조회하는 함수
    // 캐시 테이블에서 먼저 확인 후 없으면 Mojang API 호출
}
```

##### 4.2.2.3 캐시 관리 함수
```php
function getCachedPlayerList($db_connect, $server, $port) {
    // 캐시된 플레이어 목록을 가져오는 함수
}

function updatePlayerListCache($db_connect, $server, $port, $playerList) {
    // 새로운 플레이어 목록을 캐시에 저장하는 함수
}
```

## 5. 동작 흐름

### 5.1 전체 시스템 흐름

1. 사용자 브라우저에서 `index.php` 로드
2. 로딩 오버레이 표시
3. `get_server_data.php` API 호출
4. 서버 상태 및 플레이어 정보 조회
5. 데이터 표시 및 로딩 오버레이 숨김
6. 3초마다 자동 갱신

### 5.2 서버 상태 조회 프로세스

1. 서버 소켓 연결
2. 핸드셰이크 패킷 전송
3. 상태 요청 패킷 전송
4. 응답 데이터 읽기
5. JSON 데이터 파싱

### 5.3 플레이어 정보 처리 프로세스

1. 캐시된 플레이어 목록 확인
2. 캐시가 유효하지 않은 경우 서버에 직접 연결
3. 플레이어 목록 수집 및 정렬
4. 각 플레이어의 UUID와 한글 이름 조회
5. 플레이어 카드 생성 및 표시

## 6. 성능 최적화

### 6.1 캐싱 전략

- 플레이어 목록 캐시: 30초 유효 기간
- 플레이어 UUID 캐시: 24시간 유효 기간
- 캐시 미스 시 서버에 직접 요청

### 6.2 에러 처리

- 서버 연결 실패 시 적절한 에러 메시지 표시
- API 호출 실패 시 재시도 로직 구현
- 데이터베이스 연결 실패 처리
- 프론트엔드 에러 표시 및 자동 재시도

## 7. 보안 고려사항

- SQL 인젝션 방지를 위한 prepared statements 사용
- HTML 특수문자 이스케이프 처리
- 환경 변수를 통한 민감한 정보 관리
- `.env` 파일을 통한 설정 정보 보호
- `.gitignore`를 통한 민감한 파일 제외
  ```
  .env
  /vendor/
  .DS_Store
  *.log
  ```

## 8. UI 구성

### 8.1 서버 정보 표시
- 서버 상태 (온라인/오프라인)
- 서버 버전
- 온라인 플레이어 수

### 8.2 플레이어 목록 표시
- 플레이어 아바타
- 한글 이름 (있는 경우)
- 마인크래프트 이름
- 반응형 그리드 레이아웃

### 8.3 로딩 상태
- 스피너 애니메이션
- 로딩 메시지
- 오버레이 효과

## 9. 향후 개선 사항

1. 플레이어 목록 캐시 유효 시간 조정 가능성
2. 서버 연결 타임아웃 설정 최적화
3. 에러 처리 로직 강화
4. UI 디자인 개선
5. 성능 모니터링 도구 추가
6. 자동 새로고침 간격 설정 가능
7. 다크 모드 지원

## 10. 참고 사항

- 마인크래프트 서버 프로토콜 버전: 1.7 이상
- PHP 버전: 7.4 이상 권장
- 데이터베이스: MySQL 5.7 이상
- 웹 서버: Apache/Nginx
- 프론트엔드: Tailwind CSS
- 패키지 관리: Composer
- 환경 변수: PHP dotenv

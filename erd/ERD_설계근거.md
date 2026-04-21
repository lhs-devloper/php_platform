# 통합 앱 DB ERD 설계 근거

## 1. 현행 DB 분석

### 1.1 기존 테이블 현황 (dnisolution.sql)

총 약 80개 테이블 중 실 서비스 관련 테이블:

| 기존 테이블 | 역할 | 서비스 |
|---|---|---|
| `members` | 회원 정보 | 공통 |
| `members_ai_report` | AI자세분석 리포트 (키포인트 + 분석결과 + 이미지) | AI자세분석 |
| `members_footprint` | 족부분석 회원 촬영 기본정보 (키/몸무게/BMI) | AIoT족부분석 |
| `record_footprint` | 족부분석 상세 결과 (족압, 발형태, 오소틱 등) | AIoT족부분석 |
| `members_immigration` | 입관원서 (문진표) | AI자세분석 |
| `member_admin` | 관리자 계정 | 공통 |
| `b_classcode` | 수강반 코드 | 공통 |
| `smshis` / `smscode` / `smsmgt` | SMS 발송 이력 | 공통 |
| `push_allim` | 푸시 알림 | 공통 |

### 1.2 현행 DB 문제점

1. **정규화 미흡**: `members` 테이블에 회원정보 + 가족정보 + 주소 + 태권도 급/단 + 로그인정보가 하나의 테이블에 혼재 (72개 컬럼)
2. **FK 부재**: 모든 테이블이 FK 제약조건 없이 운영 → 데이터 정합성 미보장
3. **엔진 불일치**: MyISAM과 InnoDB 혼용 → 트랜잭션 일관성 없음
4. **캐릭터셋 불일치**: euckr, utf8, utf8mb4 혼재
5. **백업 테이블 난립**: `members_20230216`, `members_ai_report_230811` 등 날짜 기반 백업 테이블 다수
6. **불필요한 레거시**: 태권도 관련 필드(belt, 급/단/품, 심사정보 등)가 회원 테이블에 존재
7. **가맹점 정보 부재**: B2B 서비스임에도 가맹점(업체) 테이블이 별도로 없음
8. **강사 테이블 부재**: `members.comment`나 문자열에 강사명만 저장
9. **AI리포트 테이블 비대**: `members_ai_report`에 키포인트(좌표) + 분석결과 + 이미지URL + 기울기 방향 등 130개 이상 컬럼

---

## 2. 통합 DB 설계 원칙

### 2.1 핵심 원칙

1. **B2B 멀티테넌시**: 가맹점별 독립 DB 복사 배포 → 가맹점 정보 테이블 필수
2. **서비스 통합**: AI자세분석 + AIoT족부분석을 하나의 앱에서 제공
3. **정규화**: 3NF 수준으로 정규화하되, 조회 성능을 고려한 적절한 반정규화 허용
4. **InnoDB 통일**: 트랜잭션, FK 제약조건 지원
5. **utf8mb4 통일**: 이모지 포함 전체 유니코드 지원
6. **실패 기록 보존**: 분석 실패 시에도 기록을 남김 (status 컬럼으로 관리)
7. **이관 호환성**: 기존 데이터를 통합 DB로 매핑 가능하도록 설계

### 2.2 엔티티 도출

기존 DB + 서비스 요구사항에서 도출한 엔티티:

| 엔티티 | 근거 |
|---|---|
| `franchise` (가맹점) | B2B 서비스 운영의 기본 단위. 기존에 없었으나 필수 |
| `admin` (관리자) | 기존 `member_admin` 개선. 가맹점 연결 추가 |
| `instructor` (강사) | AI자세분석.md의 강사 속성. 기존에 테이블 없었음 |
| `class_code` (수강반) | 기존 `b_classcode` 개선 |
| `member` (회원) | 기존 `members` 정리. 불필요 필드 제거, 강사/수강반 FK 추가 |
| `posture_session` (자세분석 세션) | 기존 `members_ai_report`에서 촬영 세션 정보 분리 |
| `posture_keypoint` (자세분석 키포인트) | 기존 `members_ai_report`의 키포인트 좌표 분리 |
| `posture_report` (자세분석 리포트) | 기존 `members_ai_report`의 분석 결과 + 이미지 |
| `foot_session` (족부분석 세션) | 기존 `members_footprint` + `record_footprint`의 촬영 세션 |
| `foot_report` (족부분석 리포트) | 기존 `record_footprint`의 분석 결과 |
| `notification_config` (알림톡 설정) | AI자세분석.md + AIoT족부분석.md의 알림톡 서비스 정보 |
| `notification_log` (알림 발송 이력) | 기존 `smshis` + `push_allim` 통합 |
| `consultation` (상담 소견) | 가맹점 요청: 이전 촬영 대비 현재 개선 정도를 상담자가 소견 작성. 필요한 회원에게만 선택적 제공 (`member.consultation_enabled`) |

---

## 3. ERD 관계 설명

```
franchise (가맹점)
  ├── 1:N → admin (관리자)
  ├── 1:N → instructor (강사)
  ├── 1:N → class_code (수강반)
  ├── 1:1 → notification_config (알림톡 설정)
  └── 1:N → member (회원)  [consultation_enabled: 상담소견 제공 여부]
                ├── N:1 → instructor (담당강사)
                ├── N:1 → class_code (수강반)
                ├── 1:N → posture_session (자세분석 세션)
                │           ├── 1:N → posture_keypoint (키포인트)
                │           └── 1:1 → posture_report (리포트)
                ├── 1:N → foot_session (족부분석 세션)
                │           └── 1:1 → foot_report (리포트)
                ├── 1:N → notification_log (알림 이력)
                └── 1:N → consultation (상담 소견)  [consultation_enabled=1 회원만]
                            ├── N:1 → posture_session (현재 세션)
                            ├── N:1 → posture_session (비교 세션, 선택)
                            ├── N:1 → foot_session (현재 세션)
                            └── N:1 → foot_session (비교 세션, 선택)
```

### 3.1 주요 관계 근거

**franchise → member (1:N)**
- B2B 서비스로 가맹점별 독립 DB를 복사 배포하지만, 데이터 내에서도 가맹점 소속을 명시하여 중앙 관리 시에도 활용 가능

**member → posture_session (1:N)**
- 한 회원이 여러 번 자세분석 촬영 가능 (전/후 비교 리포트 제공)
- 기존 `members_ai_report`의 `num_members` FK 관계 유지

**posture_session → posture_keypoint (1:N)**
- 기존에 하나의 행에 정면/측면(좌)/측면(우)/뒷면 키포인트가 모두 들어있던 것을 촬영방향별로 분리
- view_type: FRONT, SIDE_LEFT, SIDE_RIGHT, BACK

**posture_session → posture_report (1:1)**
- 세션당 하나의 분석 리포트
- 기존 `members_ai_report`의 분석 결과 컬럼들

**member → foot_session (1:N)**
- 한 회원이 여러 번 족부 촬영 가능
- 기존 `members_footprint` + `record_footprint`의 `members_num` FK

**foot_session → foot_report (1:1)**
- 세션당 하나의 족부 분석 리포트
- 기존 `record_footprint`의 분석 결과 컬럼들

**member → consultation (1:N)** [consultation_enabled=1인 회원만]
- 가맹점 요청에 의해 추가된 기능: 이전 촬영 결과와 현재를 비교하여 상담자 소견 작성
- `current_*_session_id`: 이번 분석 세션 참조
- `previous_*_session_id`: 이전 분석 세션 참조 (비교 대상, NULL 가능 = 단독 소견)
- `service_type`: POSTURE(자세분석) 또는 FOOT(족부분석) 구분
- 소견 내용을 구조화: 종합소견, 개선된 점, 우려사항, 권장사항, 전/후 비교 요약

---

## 4. 기존 DB → 통합 DB 매핑

### 4.1 AI자세분석 이관 매핑

| 기존 테이블.컬럼 | → 통합 테이블.컬럼 |
|---|---|
| `members.num` | → `member.id` |
| `members.uname` | → `member.name` |
| `members.sphone` | → `member.phone` |
| `members.sex` | → `member.gender` |
| `members.uyear/umonth/uday` | → `member.birth_date` |
| `members.bigo` | → `member.status` (입관→ACTIVE, 퇴관→WITHDRAWN, 휴관→PAUSED, 명예→HONORARY) |
| `members.uclass` | → `member.class_code_id` (FK) |
| `members.comment` (강사명) | → `member.instructor_id` (FK, 강사 테이블 생성 후 매핑) |
| `members_ai_report.num` | → `posture_session.id` |
| `members_ai_report.num_members` | → `posture_session.member_id` |
| `members_ai_report.dre_height/weight` | → `posture_session.height/weight` |
| `members_ai_report.kp_front_*` | → `posture_keypoint` (view_type=FRONT) |
| `members_ai_report.kp_side_*` | → `posture_keypoint` (view_type=SIDE_RIGHT) |
| `members_ai_report.kp_other_side_*` | → `posture_keypoint` (view_type=SIDE_LEFT) |
| `members_ai_report.horizontal_*_angle` | → `posture_report.horizontal_*_angle` |
| `members_ai_report.pcmt/lossing_height` | → `posture_report.pcmt/height_loss` |
| `members_ai_report.*_img` | → `posture_report.*_img` |

### 4.2 AIoT족부분석 이관 매핑

| 기존 테이블.컬럼 | → 통합 테이블.컬럼 |
|---|---|
| `members.num` | → `member.id` |
| `members_footprint.footprint_num` | → `foot_session.id` |
| `members_footprint.members_num` | → `foot_session.member_id` |
| `members_footprint.uheight/uweight/bmi` | → `foot_session.height/weight/bmi` |
| `record_footprint.num` | → `foot_report.id` |
| `record_footprint.members_num` | → `foot_report.foot_session_id` (세션 경유) |
| `record_footprint.left_foot_length/width` | → `foot_report.left_foot_length/width` |
| `record_footprint.left_staheli/chippaux/clarke` | → `foot_report.left_staheli/chippaux/clarke` |
| `record_footprint.left_footprint_result` | → `foot_report.left_foot_type` |
| `record_footprint.moozi_left/right` | → `foot_report.hallux_valgus_left/right_angle` |
| `record_footprint.osotic_*` | → `foot_report.orthotic_*` |
| `record_footprint.ready` | → `foot_session.status` (WAIT/OK/ERROR) |

---

## 5. 추가 설계 사항

### 5.1 실패 기록 보존
- `posture_session.status`: WAIT(대기), PROCESSING(처리중), COMPLETED(완료), FAILED(실패)
- `foot_session.status`: WAIT(대기), OK(완료), ERROR(실패)
- 실패 시에도 세션 레코드는 보존하며, 리포트만 생성되지 않음

### 5.2 B2B 복사 배포 고려
- `franchise` 테이블에 자체 가맹점 정보를 보관하여, 중앙서버 연동 시 식별자로 활용
- `franchise_id` FK를 주요 테이블에 포함하여 데이터 무결성 보장
- AUTO_INCREMENT PK 사용으로 가맹점별 독립 시퀀스
- DB 복사 배포 시 `franchise` 정보만 수정하면 즉시 운영 가능

### 5.3 엑셀 데이터 추출 지원
- 회원 + 리포트 데이터를 JOIN으로 추출 가능하도록 FK 관계 명확화
- 날짜 필드를 DATETIME 타입으로 통일 (기존 int(10) unix timestamp 제거)

### 5.4 상담 소견 선택적 제공
- `member.consultation_enabled` 플래그로 회원 단위 ON/OFF
- 가맹점 관리자가 필요한 대상자에게만 기능을 활성화
- 상담 소견은 이전 세션과 현재 세션을 비교하여 개선 정도 기술
- 단독 소견(비교 없이)도 작성 가능 (`previous_*_session_id = NULL`)

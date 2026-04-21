# 통합 앱 DB ERD 다이어그램

## 테이블 관계도 (Mermaid)

```mermaid
erDiagram
    franchise ||--o{ admin : "has"
    franchise ||--o{ instructor : "has"
    franchise ||--o{ class_code : "has"
    franchise ||--o| notification_config : "has"
    franchise ||--o{ member : "has"

    member }o--o| class_code : "belongs to"
    member }o--o| instructor : "assigned to"
    member ||--o{ posture_session : "takes"
    member ||--o{ foot_session : "takes"
    member ||--o{ notification_log : "receives"
    member ||--o{ consultation : "receives"

    posture_session ||--o{ posture_keypoint : "contains"
    posture_session ||--o| posture_report : "produces"

    foot_session ||--o| foot_report : "produces"

    consultation }o--o| posture_session : "current"
    consultation }o--o| posture_session : "previous"
    consultation }o--o| foot_session : "current"
    consultation }o--o| foot_session : "previous"

    franchise {
        int id PK
        varchar name "가맹점명"
        varchar ceo_name "대표자"
        varchar phone "전화번호"
        varchar address "주소"
        varchar email "이메일"
        varchar instagram_url "인스타"
        varchar kakao_url "카카오"
        varchar naver_url "네이버"
        datetime created_at
        datetime updated_at
    }

    admin {
        int id PK
        int franchise_id FK
        varchar login_id "로그인ID"
        varchar password "비밀번호"
        enum role "SUPER/ADMIN/STAFF"
        tinyint is_active "활성여부"
        datetime created_at
        datetime updated_at
    }

    instructor {
        int id PK
        int franchise_id FK
        varchar name "이름"
        varchar phone "휴대폰"
        enum status "ACTIVE/RESIGNED"
        datetime created_at
        datetime updated_at
    }

    class_code {
        int id PK
        int franchise_id FK
        varchar code "클래스코드"
        varchar name "수업이름"
        tinyint is_active "활성여부"
        datetime created_at
    }

    member {
        int id PK
        int franchise_id FK
        int class_code_id FK
        int instructor_id FK
        varchar name "이름"
        varchar phone "휴대폰"
        enum gender "M/F"
        date birth_date "생년월일"
        decimal height "키cm"
        decimal weight "몸무게kg"
        text memo "회원특징"
        tinyint consultation_enabled "상담소견제공여부"
        enum status "ACTIVE/PAUSED/HONORARY/WITHDRAWN"
        datetime created_at
        datetime updated_at
    }

    posture_session {
        int id PK
        int member_id FK
        decimal height "촬영시키"
        decimal weight "촬영시몸무게"
        int img_x_size "이미지너비"
        int img_y_size "이미지높이"
        enum status "WAIT/PROCESSING/COMPLETED/FAILED"
        varchar fail_reason "실패사유"
        datetime captured_at "촬영일시"
        datetime created_at
    }

    posture_keypoint {
        int id PK
        int session_id FK
        enum view_type "FRONT/SIDE_LEFT/SIDE_RIGHT/BACK"
        varchar kp_nose "코"
        varchar kp_left_eye "왼눈"
        varchar kp_right_eye "오른눈"
        varchar kp_left_ear "왼귀"
        varchar kp_right_ear "오른귀"
        varchar kp_left_shoulder "왼어깨"
        varchar kp_right_shoulder "오른어깨"
        varchar kp_left_elbow "왼팔꿈치"
        varchar kp_right_elbow "오른팔꿈치"
        varchar kp_left_wrist "왼팔목"
        varchar kp_right_wrist "오른팔목"
        varchar kp_left_hip "왼엉덩이"
        varchar kp_right_hip "오른엉덩이"
        varchar kp_left_knee "왼무릎"
        varchar kp_right_knee "오른무릎"
        varchar kp_left_ankle "왼발목"
        varchar kp_right_ankle "오른발목"
    }

    posture_report {
        int id PK
        int session_id FK
        float pcmt "목하중kg"
        float height_loss "키손실cm"
        float horizontal_eye_angle "눈기울기각도"
        float horizontal_shoulder_angle "어깨기울기각도"
        float horizontal_hip_angle "골반기울기각도"
        float horizontal_leg_angle "무릎기울기각도"
        float left_genu_varus_angle "좌OX다리각도"
        float right_genu_varus_angle "우OX다리각도"
        float left_back_knee "좌백니각도"
        float right_back_knee "우백니각도"
        varchar front_user_img "정면원본"
        varchar side_left_user_img "좌측면원본"
        varchar side_right_user_img "우측면원본"
        varchar back_user_img "뒷면원본"
        datetime created_at
    }

    foot_session {
        int id PK
        int member_id FK
        decimal height "촬영시키"
        decimal weight "촬영시몸무게"
        decimal bmi "BMI"
        varchar age "만나이"
        enum status "WAIT/OK/ERROR"
        varchar fail_reason "실패사유"
        varchar img_url_1 "원본이미지1"
        varchar img_url_2 "원본이미지2"
        varchar img_url_3 "원본이미지3"
        varchar img_url_4 "원본이미지4"
        varchar select_img_url "선택이미지"
        varchar cmpx "cmPx비율"
        datetime captured_at "촬영일시"
        datetime created_at
    }

    foot_report {
        int id PK
        int session_id FK
        double left_foot_length "좌발길이"
        double right_foot_length "우발길이"
        double left_foot_width "좌발너비"
        double right_foot_width "우발너비"
        double left_forefoot "좌전족부족압"
        double right_forefoot "우전족부족압"
        double left_arch "좌중족부족압"
        double right_arch "우중족부족압"
        double left_heel "좌후족부족압"
        double right_heel "우후족부족압"
        varchar left_foot_type "좌발형태"
        varchar right_foot_type "우발형태"
        varchar hallux_valgus_left "좌무지외반각도"
        varchar hallux_valgus_right "우무지외반각도"
        varchar orthotic_left_length "좌오소틱길이"
        varchar orthotic_right_length "우오소틱길이"
        datetime created_at
    }

    notification_config {
        int id PK
        int franchise_id FK
        varchar app_key "AppKey"
        varchar secret_key "SecretKey"
        varchar sender_key "SenderKey"
        tinyint is_active "활성여부"
        datetime updated_at
    }

    notification_log {
        int id PK
        int member_id FK
        enum type "SMS/ALIMTALK/PUSH"
        varchar subject "제목"
        text content "내용"
        enum send_status "PENDING/SENT/FAILED"
        varchar recipient_phone "수신번호"
        datetime sent_at "발송일시"
        datetime created_at
    }

    consultation {
        int id PK
        int member_id FK
        enum writer_type "ADMIN/INSTRUCTOR"
        int writer_id "작성자PK"
        varchar writer_name "작성자명"
        enum service_type "POSTURE/FOOT"
        int current_posture_session_id FK
        int current_foot_session_id FK
        int previous_posture_session_id FK
        int previous_foot_session_id FK
        text overall_assessment "종합소견"
        text improvement_note "개선된점"
        text concern_note "우려사항"
        text recommendation "권장사항"
        text comparison_summary "전후비교요약"
        datetime consulted_at "상담일시"
        datetime created_at
    }
```

## 테이블 요약

| # | 테이블 | 설명 | 예상 데이터량 |
|---|---|---|---|
| 1 | `franchise` | 가맹점(업체) 정보 | 1건 (DB 복사 배포) |
| 2 | `admin` | 관리자 계정 | 소수 |
| 3 | `instructor` | 강사 정보 | 소수 |
| 4 | `class_code` | 수강반 코드 | ~16건 |
| 5 | `member` | 회원 정보 | 가맹점별 상이 |
| 6 | `posture_session` | AI자세분석 촬영 세션 | 회원 x 촬영횟수 |
| 7 | `posture_keypoint` | 자세분석 키포인트 좌표 | 세션 x 4방향 |
| 8 | `posture_report` | 자세분석 리포트 결과 | 세션당 1건 |
| 9 | `foot_session` | AIoT족부분석 촬영 세션 | 회원 x 촬영횟수 |
| 10 | `foot_report` | 족부분석 리포트 결과 | 세션당 1건 |
| 11 | `notification_config` | 알림톡 설정 | 1건 |
| 12 | `notification_log` | 알림 발송 이력 | 누적 |
| 13 | `consultation` | 상담 소견 (전/후 비교) | 선택적 회원 x 상담횟수 |

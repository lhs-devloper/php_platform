# 중앙 관리자 DB (CentralAdmin) 설계 근거

## 1. 목적

B2B SaaS 모델에서 **가맹점별 독립 DB를 복사 배포하여 운영**하는 구조의 중앙 관리 시스템.

- 가맹점 라이프사이클 관리 (가입 → 배포 → 운영 → 해지)
- 서비스 구독/요금제 관리
- DB 인스턴스 프로비저닝 추적
- 사용량 기반 과금 데이터 수집
- 중앙 관리자 운영

## 2. 업계 표준 참고

B2B SaaS 멀티테넌트 관리에서 일반적으로 사용하는 패턴:

| 패턴 | 적용 |
|---|---|
| **Tenant Isolation (DB-per-tenant)** | 가맹점별 독립 DB 복사 배포 (이미 결정됨) |
| **Subscription Billing** | 요금제 + 구독 기간 + 결제 이력 |
| **Provisioning Pipeline** | DB 생성/배포/마이그레이션 이력 추적 |
| **Usage Metering** | 회원 수, 분석 횟수 등 사용량 집계 |
| **RBAC (Role-Based Access Control)** | 중앙 관리자 역할 기반 권한 |
| **Audit Trail** | 주요 변경 이력 감사 로그 |

## 3. 엔티티 도출

### 3.1 테넌트 관리 영역

| 엔티티 | 역할 | 근거 |
|---|---|---|
| `tenant` | 가맹점(업체) 마스터 정보 | B2B 핵심 단위. 계약/과금/배포의 기준 |
| `tenant_contact` | 가맹점 담당자 연락처 | 대표자 외 실무 담당자 관리 (업계 표준: 복수 연락처) |
| `tenant_database` | 가맹점별 DB 인스턴스 정보 | DB 호스트/이름/접속정보 관리. 프로비저닝 추적 |

### 3.2 서비스/구독 영역

| 엔티티 | 역할 | 근거 |
|---|---|---|
| `service` | 제공 서비스 마스터 | AI자세분석, AIoT족부분석 각각 등록 |
| `plan` | 요금제 | 서비스별 Free/Basic/Pro 등 요금제 정의 |
| `subscription` | 가맹점 구독 정보 | 어떤 가맹점이 어떤 요금제를 언제부터 언제까지 |
| `payment` | 결제 이력 | 구독에 대한 결제 기록 (업계 필수: 세금계산서/정산) |

### 3.3 프로비저닝/배포 영역

| 엔티티 | 역할 | 근거 |
|---|---|---|
| `provision_log` | DB 프로비저닝 이력 | DB 복사/생성/마이그레이션/삭제 이력 추적 |

### 3.4 사용량 추적

| 엔티티 | 역할 | 근거 |
|---|---|---|
| `usage_daily` | 일별 사용량 집계 | 회원 수, 자세분석 횟수, 족부분석 횟수 등 일별 스냅샷 |

### 3.5 운영/관리 영역

| 엔티티 | 역할 | 근거 |
|---|---|---|
| `central_admin` | 중앙 관리자 계정 | 본사 직원 계정. RBAC 적용 |
| `audit_log` | 감사 로그 | 관리자 주요 행위 추적 (생성/수정/삭제/배포 등) |
| `notice` | 공지사항 | 가맹점 전체/개별 공지 관리 |
| `inquiry` | CS 문의 | 가맹점에서 올라오는 문의/요청 관리 |

### 3.6 협력업체 영역

| 엔티티 | 역할 | 근거 |
|---|---|---|
| `partner` | 협력업체 마스터 | 외부 협력업체 정보 및 계약 관리 |
| `partner_admin` | 협력업체 관리자 계정 | 협력업체 직원이 자사 서비스를 관리할 수 있는 계정 (RBAC) |
| `partner_tenant` | 협력업체-가맹점 연결 | 협력업체가 직접 관리하는 가맹점 매핑 (자기 소속만 관리 가능) |
| `partner_access_request` | 타 가맹점 열람 요청 | 소속 외 가맹점 데이터 열람 요청. 중앙관리자 승인 필수 |
| `partner_access_log` | 열람 이력 | 승인된 열람에 대한 실제 접근 행위 감사 추적 |

## 4. ERD 관계 설명

```
central_admin (중앙관리자)
  └── 1:N → audit_log (감사로그)

service (서비스)
  └── 1:N → plan (요금제)
                └── 1:N → subscription (구독)

tenant (가맹점)
  ├── 1:N → tenant_contact (담당자)
  ├── 1:1 → tenant_database (DB인스턴스)
  ├── 1:N → subscription (구독)
  │           └── 1:N → payment (결제)
  ├── 1:N → provision_log (배포이력)
  ├── 1:N → usage_daily (사용량)
  ├── 1:N → notice (수신공지)
  ├── 1:N → inquiry (문의)
  └── N:M → partner (협력업체)  [partner_tenant 경유]

partner (협력업체)
  ├── 1:N → partner_admin (협력업체 관리자)
  ├── 1:N → partner_tenant (소속 가맹점 연결)
  └── 1:N → partner_access_request (타 가맹점 열람 요청)
              └── 1:N → partner_access_log (접근 이력)
```

## 5. 설계 결정 사항

### 5.1 테넌트 상태 관리
```
PENDING(가입신청) → ACTIVE(운영중) → SUSPENDED(일시정지) → TERMINATED(해지)
                                    ↑         ↓
                                    └─────────┘ (재활성화)
```
- **PENDING**: 계약 진행 중, DB 미배포
- **ACTIVE**: 정상 운영 중
- **SUSPENDED**: 미납/요청에 의한 일시정지 (데이터 보존)
- **TERMINATED**: 해지 완료 (데이터 보관 정책에 따라 일정 기간 후 삭제)

### 5.2 구독 상태 관리
```
TRIAL(체험) → ACTIVE(활성) → EXPIRED(만료)
                            → CANCELLED(취소)
```

### 5.3 DB 프로비저닝 상태
```
REQUESTED(요청) → PROVISIONING(진행중) → COMPLETED(완료)
                                       → FAILED(실패)
```

### 5.4 사용량 집계 방식
- `usage_daily` 테이블에 일별 스냅샷 저장
- 배치 프로세스가 각 가맹점 DB에 접속하여 COUNT 집계 후 저장
- 월별 과금 시 `usage_daily`를 기간 합산하여 산출

### 5.5 협력업체 관리 체계

**협력업체 역할 구분:**
- `PARTNER_ADMIN`: 협력업체 내 최고 관리자 (소속 직원 관리 가능)
- `PARTNER_STAFF`: 협력업체 일반 직원 (데이터 열람/관리)
- `PARTNER_VIEWER`: 조회 전용

**접근 권한 체계:**
1. **기본 권한**: `partner_tenant`에 연결된 소속 가맹점만 관리/열람 가능
2. **확장 권한**: 타 가맹점 열람은 `partner_access_request`를 통해 요청 → 중앙관리자 승인 필수

**타 가맹점 열람 승인 흐름:**
```
협력업체 관리자가 요청 (사유 + 열람범위 지정)
  ↓
partner_access_request.status = PENDING
  ↓
중앙관리자가 검토 후 승인/거절
  ├── 승인: status=APPROVED, 기간(access_start~access_end) + 범위(access_scope) 설정
  │     ├── FULL: 전체 데이터
  │     ├── REPORT_ONLY: 분석 리포트만
  │     └── STATS_ONLY: 통계만
  └── 거절: status=REJECTED, reject_reason 기록
  ↓
승인된 열람의 모든 접근 행위 → partner_access_log에 기록 (감사 추적)
  ↓
기간 만료 → status=EXPIRED (자동)
중앙관리자 철회 → status=REVOKED (수동)
```

<script lang="ts">
	import { onMount } from 'svelte';
	import {
		Bell,
		BookOpenText,
		BriefcaseBusiness,
		CalendarDays,
		ChartNoAxesColumnIncreasing,
		ClipboardCheck,
		FileClock,
		GitBranch,
		History,
		Info,
		LayoutDashboard,
		Link2,
		ListPlus,
		Menu,
		UserRound
	} from '@lucide/svelte';
	import { api, ApiError } from '#lib/api.js';
	import { Badge } from '#lib/components/ui/badge/index.js';
	import { Button } from '#lib/components/ui/button/index.js';
	import * as Card from '#lib/components/ui/card/index.js';
	import ThemeToggle from '#lib/components/theme-toggle.svelte';
	import WorkspaceSidebar, { type WorkspaceNavigationSection } from '#lib/components/workspace-sidebar.svelte';
	import './page.css';
	import './workspace.css';
	let isDark = $state(false);
	let sidebarCollapsed = $state(false);
	let mobileSidebarOpen = $state(false);
	onMount(() => {
		const savedTheme = localStorage.getItem('kpi-theme');
		isDark = savedTheme ? savedTheme === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
		document.documentElement.classList.toggle('dark', isDark);
		sidebarCollapsed = localStorage.getItem('kpi-sidebar-collapsed') === 'true';
	});
	function toggleTheme(): void {
		isDark = !isDark;
		document.documentElement.classList.toggle('dark', isDark);
		localStorage.setItem('kpi-theme', isDark ? 'dark' : 'light');
	}
	function toggleSidebarCollapsed(): void {
		sidebarCollapsed = !sidebarCollapsed;
		localStorage.setItem('kpi-sidebar-collapsed', String(sidebarCollapsed));
	}
	function closeMobileSidebar(): void {
		mobileSidebarOpen = false;
	}
	function handleWindowKeydown(event: KeyboardEvent): void {
		if (event.key === 'Escape' && mobileSidebarOpen) {
			closeMobileSidebar();
		}
	}

	type DemoIdentity = {
		id: string;
		display_name: string;
		access_role: string;
		access_roles: string[];
		org_unit_name: string;
		self_assignment_available: boolean;
	};
	type SsoIdentityMapping = {
		id: string;
		external_employee_id: string;
		employee_id: string;
		employee_name: string | null;
		position_name: string | null;
		access_role: string | null;
		access_roles: string[];
		org_unit_name: string | null;
		active: boolean;
		updated_at: string | null;
	};
	type SsoMappingCandidate = {
		employee_id: string;
		display_name: string;
		position_name: string;
		access_role: string;
		access_roles: string[];
		org_unit_name: string;
	};
	type SsoMappingPayload = {
		issuer: string;
		mappings: SsoIdentityMapping[];
		candidates: SsoMappingCandidate[];
	};
	type DemoIdentityGroup = {
		key: string;
		label: string;
		description: string;
		ids: string[];
		identities: DemoIdentity[];
	};
	type CurrentUser = {
		id: string;
		employee_id: string;
		display_name: string;
		access_role: string;
		access_roles: string[];
		position_name: string;
		org_unit_id: string;
		org_unit_name: string;
		scope_org_unit_ids: string[];
		auth_source?: 'DEMO' | 'SSO';
	};
	type Cycle = {
		id: string;
		name: string;
		cadence: string;
		starts_at: string;
		ends_at: string;
		status: string;
		demo_only: boolean;
		formula_version?: string;
	};
	type PeriodStatus = 'DRAFT' | 'OPEN' | 'CLOSED';
	type StaffTrackKind = 'BUSINESS' | 'NON_BUSINESS';
	type StaffTrack = StaffTrackKind | null;
	type StaffWorkspaceTab = 'KPI' | 'BEHAVIOR' | 'APPROVAL' | 'HISTORY';
	type SelfAssessmentStep = 1 | 2 | 3;
	type BehaviorKey = 'integrity' | 'service' | 'teamwork' | 'compliance' | 'responsibility';
	type BehaviorEvaluation = { ratings: Record<BehaviorKey, number> } | null;
	type DemoPeriod = {
		id: string;
		name: string;
		cadenceMonths: number;
		startsAt: string;
		endsAt: string;
		status: PeriodStatus;
	};
	type HistoryItem = {
		code: string;
		name: string;
		target: string;
		actual: string;
		score: number | null;
		unit: string;
		evidence?: string;
		note?: string;
	};
	type AssessmentHistoryEntry = {
		id: string;
		track: StaffTrackKind;
		periodName: string;
		cadence: string;
		startsAt: string;
		endsAt: string;
		status: 'DRAFT' | 'SUBMITTED' | 'REVISION' | 'VERIFIED' | 'FINAL';
		score: number | null;
		reviewer: string | null;
		finalDate: string | null;
		submittedDate: string | null;
		note: string;
		items: HistoryItem[];
		review_workflow: PerformanceContract['review_workflow'];
		review_history: PerformanceContract['review_history'];
		reviewerName?: string | null;
		submittedAt?: string | null;
	};
	type ReviewTrackFilter = 'ALL' | StaffTrackKind;
	type ReviewStatusFilter = 'ALL' | 'SUBMITTED' | 'REVISION';
	type ReviewAgeFilter = 'ALL' | '0_3' | '4_7' | '8_PLUS';
	type MonitoringStatus = 'DRAFT' | 'SUBMITTED' | 'REVISION' | 'VERIFIED' | 'FINAL';
	type MonitoringStatusFilter = 'ALL' | MonitoringStatus;
	type AuditTrailStatus = 'VERIFIED' | 'FINAL';
	type AuditTrailStatusFilter = 'ALL' | AuditTrailStatus;
	type CatalogTrackFilter = 'ALL' | StaffTrackKind;
	type CatalogState = 'DRAFT' | 'ACTIVE';
	type CatalogStateFilter = 'ALL' | CatalogState;
	type AssignmentSaveState = 'DRAFT' | 'SAVED';
	type StaffAssignmentStatus = 'NONE' | 'DRAFT' | 'REVISION' | 'SUBMITTED' | 'VERIFIED' | 'FINAL';
	type DemoStaffAssignment = {
		status: Exclude<StaffAssignmentStatus, 'NONE'>;
		selectedIds: string[];
		savedAt: string;
		submittedAt: string | null;
		track: StaffTrackKind;
		employeeName: string;
		positionName: string;
		periodName: string;
		items: HistoryItem[];
	};
	type DemoReviewTask = {
		id: string;
		track: StaffTrackKind;
		employeeName: string;
		positionName: string;
		periodName: string;
		status: 'SUBMITTED' | 'REVISION';
		ageDays: number;
		submittedAt: string;
		dueAt: string;
		score: number | null;
		lastAction: string;
		items: HistoryItem[];
	};
	type DemoReviewDecision = {
		decision: 'REVISION' | 'VERIFIED';
		note: string;
		savedAt: string;
		actorName: string;
		actorRole: string;
	};
	type DemoStaffFinalization = {
		note: string;
		finalizedAt: string;
		actorName: string;
		actorRole: string;
	};
	type DemoMonitoringRecord = {
		id: string;
		track: StaffTrackKind;
		employeeName: string;
		positionName: string;
		periodName: string;
		status: MonitoringStatus;
		submittedAt: string | null;
		dueAt: string;
		ageDays: number;
		score: number | null;
		attention: string;
		overdue: boolean;
	};
	type DemoAuditTrailEntry = {
		id: string;
		track: StaffTrackKind;
		status: AuditTrailStatus;
		actorName: string;
		actorRole: string;
		actedAt: string;
		employeeName: string;
		positionName: string;
		periodName: string;
		note: string;
		items: HistoryItem[];
		score?: number | null;
		reviewerName?: string | null;
		submittedAt?: string | null;
		finalDate?: string | null;
	};
	type AuditTrailFocus = {
		employeeName: string;
		track: StaffTrackKind;
		periodName: string;
		status: MonitoringStatus;
		origin: 'MONITORING' | 'COMPARISON_DRILLDOWN';
		score: number | null;
		submittedAt: string | null;
		finalDate: string | null;
	};
	type AuditContractSummary = {
		employeeName: string;
		positionName: string;
		track: StaffTrackKind;
		periodName: string;
		status: MonitoringStatus;
		score: number | null;
		reviewerName: string | null;
		submittedAt: string | null;
		finalDate: string | null;
	};
	type MonitoringDrilldownFocus = {
		periodId: string;
		track: ReviewTrackFilter;
		status: MonitoringStatus;
	};
	type DemoPeriodComparison = {
		periodId: string;
		totalContracts: number;
		statusCounts: Record<MonitoringStatus, number>;
		finalByTrack: Record<StaffTrackKind, number>;
	};
	type DemoCatalogItem = {
		id: string;
		code: string;
		name: string;
		track: StaffTrackKind;
		group: string;
		type: 'Q' | 'N' | 'Z' | 'L';
		unit: string;
		target: number;
		weight: number;
		dataSource: string;
		state: CatalogState;
		demoOnly: boolean;
	};
	type DemoAssignmentMapping = {
		selectedIds: string[];
		targets: Record<string, string>;
		weights: Record<string, string>;
		savedAt: string | null;
	};
	type DemoPositionTrack = StaffTrackKind | 'BOTH';
	type DemoPositionStatus = 'ACTIVE' | 'INACTIVE';
	type DemoPositionMapping = {
		id: string;
		name: string;
		level: string;
		orgUnitName: string;
		scopeLabel: string;
		track: DemoPositionTrack;
		status: DemoPositionStatus;
		mappingItemIds: string[];
		targets: Record<string, string>;
		weights: Record<string, string>;
		note: string;
	};
	type KpiDefinition = {
		id: string;
		code: string;
		name: string;
		group: string;
		type: string;
		definition: string;
		unit: string;
		default_weight: number;
		sample_target: number;
		target_note: string;
		data_source: string;
		quality_flag: boolean;
		draft: boolean;
		demo_only: boolean;
	};
	type PositionKpiVersionItem = {
		definition_id: string;
		code: string;
		name: string;
		score_type: 'A' | 'Q' | 'L' | 'Z';
		definition: string;
		unit: string;
		target: number;
		weight_percent: number;
		source_note: string;
		quality_flag: boolean;
		application_note: string;
	};
	type PositionKpiVersion = {
		id: string;
		position_id: string;
		position_name: string;
		version: number;
		status: 'ACTIVE' | 'ARCHIVED';
		created_at: string | null;
		items: PositionKpiVersionItem[];
	};
	type PositionKpiSetting = {
		id: string;
		name: string;
		group_name: string;
		access_role: string;
		active_version_id: string | null;
		versions: PositionKpiVersion[];
	};
	type PositionKpiDraftItem = PositionKpiVersionItem & { client_id: string };
	type Dashboard = {
		cycle: Cycle;
		counts: { total_contracts: number; ready_contracts: number; submitted_contracts: number };
		average_final_score: number | null;
		contracts_needing_action: number;
	};
	type Evidence = { label: string; reference: string };
	type ContractItem = {
		id: string;
		kpi_definition_id: string;
		code: string;
		name: string;
		score_type: string;
		unit: string;
		target: number;
		weight: number;
		actual: number | null;
		note: string;
		evidence: Evidence[];
		score: number | null;
		state: 'PENDING' | 'VALID' | 'NOT_APPLICABLE';
		reason: string | null;
	};
	type ContractComment = {
		id: string;
		actor_employee_id: string;
		actor_name: string;
		item_id: string | null;
		message: string;
		created_at: string;
	};
	type AuditEvent = {
		id: string;
		actor_id: string;
		actor_name: string;
		action: string;
		object_type: string;
		object_id: string;
		reason: string | null;
		occurred_at: string | null;
		before: Record<string, unknown> | null;
		after: Record<string, unknown> | null;
	};
	type AdminAuditLogEvent = AuditEvent & {
		actor_position_name: string | null;
		actor_org_unit_name: string | null;
	};
	type AdminAuditLogPage = {
		data: AdminAuditLogEvent[];
		meta: { page: number; per_page: number; total: number; last_page: number };
	};
	type WorkspaceView = 'overview' | 'mine' | 'reviews' | 'assignment' | 'periods' | 'history' | 'monitoring' | 'catalog' | 'mapping' | 'positionKpis' | 'positions' | 'ssoMappings' | 'audit' | 'comparison' | 'auditLog';
	type AuditMenuKey = WorkspaceView | 'mine-kpi' | 'mine-behavior' | 'mine-approval' | 'mine-history';
	type ScoreSummary = {
		kpi_score: number | null;
		behavior_score: number | null;
		final_score: number | null;
		rating: string | null;
		valid_weight: number;
		formula_version: string;
		demo_only: boolean;
		message: string | null;
	};
	type PerformanceContract = {
		id: string;
		cycle_id: string;
		created_at?: string | null;
		subject_type: string;
		subject_id: string;
		employee_name: string | null;
		position_name: string | null;
		position_kpi_version?: number | null;
		org_unit_name: string;
		status: 'DRAFT' | 'SUBMITTED' | 'REVISION' | 'VERIFIED' | 'FINAL';
		reviewer_employee_id: string | null;
		behavior_evaluation: BehaviorEvaluation;
		review_workflow?: {
			reviewer_slots: Record<'P1' | 'P2', { employee_id: string | null; name: string | null; position_name: string | null; state: 'READY' | 'SKIPPED' }>;
			active_slots: Array<'P1' | 'P2'>;
			current_slot: 'P1' | 'P2' | null;
		};
		review_history?: Array<{ reviewer_employee_id: string; reviewer_name: string; slot: 'P1' | 'P2' | null; decision: string; comment: string; created_at: string | null }>;
		items: ContractItem[];
		comments: ContractComment[];
		score: ScoreSummary | null;
	};
	type NumericDraft = string | number | null | undefined;
	type EditItem = {
		actual: NumericDraft;
		evidence: string;
		note: string;
		target: NumericDraft;
		weightPercent: NumericDraft;
		reason: string;
	};
	type Envelope<T> = { data: T };
	const behaviorCriteria: Array<{ key: BehaviorKey; label: string }> = [
		{ key: 'integrity', label: 'Integritas' },
		{ key: 'service', label: 'Orientasi layanan' },
		{ key: 'teamwork', label: 'Kerja sama' },
		{ key: 'compliance', label: 'Kepatuhan kerja' },
		{ key: 'responsibility', label: 'Tanggung jawab' }
	];

	function demoReviewItems(track: StaffTrackKind, variant: 'first' | 'second'): HistoryItem[] {
		const business = track === 'BUSINESS';
		const suffix = variant === 'first' ? '001' : '002';
		return business
			? [
					{ code: 'DEMO-P-01', name: 'Akuisisi dana demo', target: '100', actual: variant === 'first' ? '105' : '92', score: variant === 'first' ? 86 : 78, unit: 'rekening', evidence: `DEMO-EVIDENCE-P-${suffix}-01`, note: variant === 'first' ? 'Rekap pembukaan rekening periode berjalan.' : 'Realisasi turun karena dua agenda akuisisi bergeser.' },
					{ code: 'DEMO-P-02', name: 'Realisasi kredit demo', target: '50', actual: variant === 'first' ? '48' : '41', score: variant === 'first' ? 82 : 74, unit: 'juta rupiah', evidence: `DEMO-EVIDENCE-P-${suffix}-02`, note: variant === 'first' ? 'Pipeline kredit sudah dicatat pada laporan unit.' : 'Sebagian berkas masih menunggu kelengkapan dokumen.' },
					{ code: 'DEMO-P-03', name: 'Kualitas portofolio demo', target: '95', actual: variant === 'first' ? '96' : '91', score: variant === 'first' ? 90 : 79, unit: '%', evidence: `DEMO-EVIDENCE-P-${suffix}-03`, note: variant === 'first' ? 'Monitoring kualitas portofolio diperbarui setiap minggu.' : 'Reviewer perlu melihat penjelasan atas akun yang masuk watchlist.' }
				]
			: [
					{ code: 'DEMO-O-01', name: 'SLA layanan operasional demo', target: '30', actual: variant === 'first' ? '28' : '34', score: variant === 'first' ? 92 : 76, unit: 'menit', evidence: `DEMO-EVIDENCE-O-${suffix}-01`, note: variant === 'first' ? 'Rekap waktu layanan diambil dari log operasional demo.' : 'Ada antrean layanan pada dua hari dengan volume tinggi.' },
					{ code: 'DEMO-O-02', name: 'Akurasi dokumen demo', target: '98', actual: variant === 'first' ? '99' : '96', score: variant === 'first' ? 94 : 81, unit: '%', evidence: `DEMO-EVIDENCE-O-${suffix}-02`, note: variant === 'first' ? 'Sampel dokumen sudah dicocokkan dengan daftar kontrol.' : 'Empat dokumen perlu koreksi metadata sebelum ditutup.' },
					{ code: 'DEMO-O-03', name: 'Penyelesaian transaksi demo', target: '120', actual: variant === 'first' ? '126' : '110', score: variant === 'first' ? 88 : 80, unit: 'transaksi', evidence: `DEMO-EVIDENCE-O-${suffix}-03`, note: variant === 'first' ? 'Rekap transaksi harian sudah disatukan pada laporan unit.' : 'Sebagian transaksi tertunda karena proses rekonsiliasi.' }
				];
	}

	function demoReviewTasksForTrack(track: StaffTrackKind): DemoReviewTask[] {
		const business = track === 'BUSINESS';
		const name = business ? 'Pemasaran Demo 001' : 'Operasional Demo 001';
		const position = business ? 'Staf Pemasaran' : 'Staf Operasional';
		const prefix = business ? 'P' : 'O';
		return [
			{
				id: `DEMO-REVIEW-${prefix}-001`,
				track,
				employeeName: name,
				positionName: position,
				periodName: 'Triwulan IV 2026',
				status: 'SUBMITTED',
				ageDays: business ? 2 : 4,
				submittedAt: business ? '2026-11-11' : '2026-11-09',
				dueAt: '2026-11-15',
				score: business ? 86.1 : 91.3,
				lastAction: 'Self-assessment diajukan',
				items: demoReviewItems(track, 'first')
			},
			{
				id: `DEMO-REVIEW-${prefix}-002`,
				track,
				employeeName: name,
				positionName: position,
				periodName: 'Triwulan III 2026',
				status: 'REVISION',
				ageDays: business ? 6 : 9,
				submittedAt: business ? '2026-11-07' : '2026-11-04',
				dueAt: '2026-11-13',
				score: business ? 77.4 : 81.2,
				lastAction: 'Dikembalikan untuk perbaikan',
				items: demoReviewItems(track, 'second')
			}
		];
	}

	let currentUser = $state<CurrentUser | null>(null);
	let identities = $state<DemoIdentity[]>([]);
	let identitiesLoading = $state(true);
	let identitiesError = $state('');
	let selectedIdentityId = $state('');
	let loginMode = $state<'SSO' | 'DEMO'>('SSO');
	let ssoEmployeeId = $state('');
	let ssoPassword = $state('');
	let dashboard = $state<Dashboard | null>(null);
	let cycles = $state<Cycle[]>([]);
	let kpiDefinitions = $state<KpiDefinition[]>([]);
	let selectedDefinitionIds = $state<string[]>([]);
	let targetDrafts = $state<Record<string, string>>({});
	let catalogLoading = $state(false);
	let catalogLoaded = $state(false);
	let contracts = $state<PerformanceContract[]>([]);
	let selectedContract = $state<PerformanceContract | null>(null);
	let staffWorkspaceTab = $state<StaffWorkspaceTab>('KPI');
	let assessmentStep = $state<SelfAssessmentStep>(1);
	let behaviorDraft = $state<Partial<Record<BehaviorKey, string>>>({});
	let behaviorRevisionComment = $state('');
	let auditEvents = $state<AuditEvent[]>([]);
	let auditLoadFailed = $state(false);
	let adminAuditEvents = $state<AdminAuditLogEvent[]>([]);
	let adminAuditMeta = $state<AdminAuditLogPage['meta']>({ page: 1, per_page: 50, total: 0, last_page: 1 });
	let adminAuditLoading = $state(false);
	let adminAuditFailed = $state(false);
	let adminAuditSearch = $state('');
	let adminAuditAction = $state('');
	let adminAuditFrom = $state('');
	let adminAuditTo = $state('');
	let edits = $state<Record<string, EditItem>>({});
	let loading = $state(true);
	let busy = $state(false);
	let errorMessage = $state('');
	let successMessage = $state('');
	let view = $state<WorkspaceView>('overview');
	let selectedHistoryId = $state('');
	let reviewTrackFilter = $state<ReviewTrackFilter>('ALL');
	let reviewStatusFilter = $state<ReviewStatusFilter>('ALL');
	let reviewAgeFilter = $state<ReviewAgeFilter>('ALL');
	let selectedReviewTaskId = $state('');
	let reviewDecisionNote = $state('');
	let demoReviewDecisions = $state<Record<string, DemoReviewDecision>>({});
	let demoStaffFinalizations = $state<Record<string, DemoStaffFinalization>>({});
	let monitoringTrackFilter = $state<ReviewTrackFilter>('ALL');
	let monitoringStatusFilter = $state<MonitoringStatusFilter>('ALL');
	let monitoringDrilldownFocus = $state<MonitoringDrilldownFocus | null>(null);
	let adminPeriodFilter = $state('DEMO-PERIOD-002');
	let auditTrackFilter = $state<ReviewTrackFilter>('ALL');
	let auditStatusFilter = $state<AuditTrailStatusFilter>('ALL');
	let selectedAuditTrailId = $state('');
	let auditTrailFocus = $state<AuditTrailFocus | null>(null);
	let comparisonPeriodA = $state('DEMO-PERIOD-001');
	let comparisonPeriodB = $state('DEMO-PERIOD-002');
	let catalogTrackFilter = $state<CatalogTrackFilter>('ALL');
	let catalogStateFilter = $state<CatalogStateFilter>('ALL');
	let catalogFormTrack = $state<StaffTrackKind>('BUSINESS');
	let catalogFormCode = $state('');
	let catalogFormName = $state('');
	let catalogFormGroup = $state('');
	let catalogFormType = $state<DemoCatalogItem['type']>('Q');
	let catalogFormUnit = $state('');
	let catalogFormTarget = $state('');
	let catalogFormWeight = $state('');
	let catalogFormSource = $state('');
	let catalogFormState = $state<CatalogState>('DRAFT');
	let assignmentTrack = $state<StaffTrackKind>('BUSINESS');
	let assignmentSelectedIds = $state<string[]>([]);
	let assignmentTargetDrafts = $state<Record<string, string>>({});
	let assignmentWeightDrafts = $state<Record<string, string>>({});
	let assignmentSaveState = $state<AssignmentSaveState>('DRAFT');
	let positionKpiSettings = $state<PositionKpiSetting[]>([]);
	let positionKpiSettingsLoading = $state(false);
	let positionKpiSettingsBusy = $state(false);
	let selectedPositionKpiId = $state('');
	let positionKpiDraftItems = $state<PositionKpiDraftItem[]>([]);
	let selectedPositionId = $state('POS-STAFF-BUSINESS');
	let positionEditorOpen = $state(false);
	let positionEditorMode = $state<'CREATE' | 'EDIT'>('CREATE');
	let positionEditorId = $state('');
	let positionFormName = $state('');
	let positionFormLevel = $state('');
	let positionFormOrgUnit = $state('');
	let positionFormScope = $state('');
	let positionFormTrack = $state<DemoPositionTrack>('BUSINESS');
	let positionFormStatus = $state<DemoPositionStatus>('ACTIVE');
	let positionFormSelectedIds = $state<string[]>([]);
	let positionFormTargets = $state<Record<string, string>>({});
	let positionFormWeights = $state<Record<string, string>>({});
	let positionDeactivateId = $state('');
	let ssoMappings = $state<SsoIdentityMapping[]>([]);
	let ssoMappingCandidates = $state<SsoMappingCandidate[]>([]);
	let ssoMappingIssuer = $state('');
	let ssoMappingLoading = $state(false);
	let ssoMappingBusy = $state(false);
	let ssoMappingExternalId = $state('');
	let ssoMappingTargetId = $state('');
	let ssoMappingConfirmed = $state(false);
	let ssoMappingActionId = $state('');
	let staffAssignmentStatus = $state<StaffAssignmentStatus>('NONE');
	let staffAssignmentRecords = $state<Record<string, DemoStaffAssignment>>({});
	let commentDraft = $state('');
	let reviewComment = $state('');
	let periodName = $state('Periode KPI Triwulan');
	let periodCadenceMonths = $state(3);
	let periodStartDate = $state('2026-11-01');
	let periodStatus = $state<PeriodStatus>('DRAFT');
	let configuredPeriods = $state<DemoPeriod[]>([]);
	let cycleStatusDrafts = $state<Record<string, PeriodStatus>>({});
	let demoPeriods = $state<DemoPeriod[]>([
		{
			id: 'DEMO-PERIOD-001',
			name: 'Triwulan III 2026',
			cadenceMonths: 3,
			startsAt: '2026-07-01',
			endsAt: '2026-09-30',
			status: 'CLOSED'
		},
		{
			id: 'DEMO-PERIOD-002',
			name: 'Triwulan IV 2026',
			cadenceMonths: 3,
			startsAt: '2026-10-01',
			endsAt: '2026-12-31',
			status: 'OPEN'
		},
		{
			id: 'DEMO-PERIOD-003',
			name: 'Rencana Semester I 2027',
			cadenceMonths: 6,
			startsAt: '2027-01-01',
			endsAt: '2027-06-30',
			status: 'DRAFT'
		}
	]);
	const demoReviewTasks: DemoReviewTask[] = [
		...demoReviewTasksForTrack('BUSINESS'),
		...demoReviewTasksForTrack('NON_BUSINESS')
	];
	const demoMonitoringRecords: DemoMonitoringRecord[] = [
		{
			id: 'DEMO-MONITOR-P-001',
			track: 'BUSINESS',
			employeeName: 'Pemasaran Demo 001',
			positionName: 'Staf Pemasaran',
			periodName: 'Triwulan IV 2026',
			status: 'SUBMITTED',
			submittedAt: '2026-11-11',
			dueAt: '2026-11-15',
			ageDays: 2,
			score: 86.1,
			attention: 'Menunggu review Kepala Cabang',
			overdue: false
		},
		{
			id: 'DEMO-MONITOR-P-002',
			track: 'BUSINESS',
			employeeName: 'Pemasaran Demo 002',
			positionName: 'Staf Pemasaran',
			periodName: 'Triwulan IV 2026',
			status: 'REVISION',
			submittedAt: '2026-11-07',
			dueAt: '2026-11-13',
			ageDays: 6,
			score: 77.4,
			attention: 'Revisi belum dikirim ulang',
			overdue: true
		},
		{
			id: 'DEMO-MONITOR-P-003',
			track: 'BUSINESS',
			employeeName: 'Pemasaran Demo 003',
			positionName: 'Staf Pemasaran',
			periodName: 'Triwulan IV 2026',
			status: 'VERIFIED',
			submittedAt: '2026-11-05',
			dueAt: '2026-11-12',
			ageDays: 8,
			score: 89.2,
			attention: 'Siap masuk finalisasi Admin',
			overdue: true
		},
		{
			id: 'DEMO-MONITOR-O-001',
			track: 'NON_BUSINESS',
			employeeName: 'Operasional Demo 001',
			positionName: 'Staf Operasional',
			periodName: 'Triwulan IV 2026',
			status: 'SUBMITTED',
			submittedAt: '2026-11-09',
			dueAt: '2026-11-15',
			ageDays: 4,
			score: 91.3,
			attention: 'Menunggu review Kepala Cabang',
			overdue: false
		},
		{
			id: 'DEMO-MONITOR-O-002',
			track: 'NON_BUSINESS',
			employeeName: 'Operasional Demo 002',
			positionName: 'Staf Operasional',
			periodName: 'Triwulan IV 2026',
			status: 'DRAFT',
			submittedAt: null,
			dueAt: '2026-11-15',
			ageDays: 1,
			score: null,
			attention: 'Self-assessment belum diajukan',
			overdue: false
		},
		{
			id: 'DEMO-MONITOR-P-004',
			track: 'BUSINESS',
			employeeName: 'Pemasaran Demo 004',
			positionName: 'Staf Pemasaran',
			periodName: 'Triwulan IV 2026',
			status: 'FINAL',
			submittedAt: '2026-11-04',
			dueAt: '2026-11-12',
			ageDays: 10,
			score: 87.6,
			attention: 'Selesai dan terkunci',
			overdue: false
		},
		{
			id: 'DEMO-MONITOR-O-003',
			track: 'NON_BUSINESS',
			employeeName: 'Operasional Demo 003',
			positionName: 'Staf Operasional',
			periodName: 'Triwulan III 2026',
			status: 'FINAL',
			submittedAt: '2026-10-08',
			dueAt: '2026-10-15',
			ageDays: 29,
			score: 90.4,
			attention: 'Selesai dan terkunci',
			overdue: false
		}
	];
	const demoAuditTrailEntries: DemoAuditTrailEntry[] = [
		{
			id: 'DEMO-AUDIT-P-001',
			track: 'BUSINESS',
			status: 'VERIFIED',
			actorName: 'Kepala Cabang',
			actorRole: 'Reviewer / Kepala Cabang',
			actedAt: '2026-11-12T09:15:00+07:00',
			employeeName: 'Pemasaran Demo 001',
			positionName: 'Staf Pemasaran',
			periodName: 'Triwulan IV 2026',
			note: 'Target, realisasi, dan bukti demo sudah diperiksa. Snapshot siap masuk proses finalisasi Admin.',
			items: demoReviewItems('BUSINESS', 'first'),
			score: 86.1,
			reviewerName: 'Kepala Cabang',
			submittedAt: '2026-11-11',
			finalDate: null
		},
		{
			id: 'DEMO-AUDIT-P-003',
			track: 'BUSINESS',
			status: 'VERIFIED',
			actorName: 'Kepala Cabang',
			actorRole: 'Reviewer / Kepala Cabang',
			actedAt: '2026-11-12T14:20:00+07:00',
			employeeName: 'Pemasaran Demo 003',
			positionName: 'Staf Pemasaran',
			periodName: 'Triwulan IV 2026',
			note: 'Penilaian diverifikasi setelah target dan bukti capaian melewati pemeriksaan reviewer.',
			items: demoReviewItems('BUSINESS', 'first'),
			score: 89.2,
			reviewerName: 'Kepala Cabang',
			submittedAt: '2026-11-05',
			finalDate: null
		},
		{
			id: 'DEMO-AUDIT-P-002',
			track: 'BUSINESS',
			status: 'FINAL',
			actorName: 'Admin KPI',
			actorRole: 'Admin KPI / SDM',
			actedAt: '2026-11-13T15:20:00+07:00',
			employeeName: 'Pemasaran Demo 002',
			positionName: 'Staf Pemasaran',
			periodName: 'Triwulan IV 2026',
			note: 'Snapshot dikunci setelah verifikasi reviewer. Tidak ada perubahan lanjutan pada periode ini.',
			items: demoReviewItems('BUSINESS', 'second'),
			score: 86.1,
			reviewerName: 'Kepala Cabang',
			submittedAt: '2026-11-08',
			finalDate: '2026-11-13'
		},
		{
			id: 'DEMO-AUDIT-O-001',
			track: 'NON_BUSINESS',
			status: 'VERIFIED',
			actorName: 'Kepala Cabang',
			actorRole: 'Reviewer / Kepala Cabang',
			actedAt: '2026-11-11T13:40:00+07:00',
			employeeName: 'Operasional Demo 001',
			positionName: 'Staf Operasional',
			periodName: 'Triwulan IV 2026',
			note: 'Bukti layanan dan rekonsiliasi demo sesuai dengan catatan proses operasional.',
			items: demoReviewItems('NON_BUSINESS', 'first'),
			score: 91.3,
			reviewerName: 'Kepala Cabang',
			submittedAt: '2026-11-09',
			finalDate: null
		},
		{
			id: 'DEMO-AUDIT-O-002',
			track: 'NON_BUSINESS',
			status: 'FINAL',
			actorName: 'Admin KPI',
			actorRole: 'Admin KPI / SDM',
			actedAt: '2026-11-12T16:05:00+07:00',
			employeeName: 'Operasional Demo 002',
			positionName: 'Staf Operasional',
			periodName: 'Triwulan IV 2026',
			note: 'Finalisasi demo selesai. Snapshot menjadi referensi read-only untuk histori penilaian.',
			items: demoReviewItems('NON_BUSINESS', 'second'),
			score: 90.4,
			reviewerName: 'Kepala Cabang',
			submittedAt: '2026-11-08',
			finalDate: '2026-11-12'
		}
	];
	const demoPeriodComparisons: DemoPeriodComparison[] = [
		{
			periodId: 'DEMO-PERIOD-001',
			totalContracts: 1,
			statusCounts: { DRAFT: 0, SUBMITTED: 0, REVISION: 0, VERIFIED: 0, FINAL: 1 },
			finalByTrack: { BUSINESS: 0, NON_BUSINESS: 1 }
		},
		{
			periodId: 'DEMO-PERIOD-002',
			totalContracts: 6,
			statusCounts: { DRAFT: 1, SUBMITTED: 2, REVISION: 1, VERIFIED: 1, FINAL: 1 },
			finalByTrack: { BUSINESS: 1, NON_BUSINESS: 0 }
		}
	];
	let demoCatalogItems = $state<DemoCatalogItem[]>([
		{ id: 'DEMO-CATALOG-P-001', code: 'DEMO-P-01', name: 'Akuisisi dana demo', track: 'BUSINESS', group: 'Dana', type: 'Q', unit: 'rekening', target: 100, weight: 0.2, dataSource: 'Rekap sistem operasional', state: 'ACTIVE', demoOnly: true },
		{ id: 'DEMO-CATALOG-P-002', code: 'DEMO-P-02', name: 'Realisasi kredit demo', track: 'BUSINESS', group: 'Kredit', type: 'Q', unit: 'juta rupiah', target: 50, weight: 0.2, dataSource: 'Laporan kredit demo', state: 'ACTIVE', demoOnly: true },
		{ id: 'DEMO-CATALOG-P-003', code: 'DEMO-P-03', name: 'Kualitas portofolio demo', track: 'BUSINESS', group: 'Kualitas', type: 'Q', unit: '%', target: 95, weight: 0.2, dataSource: 'Monitoring kualitas demo', state: 'ACTIVE', demoOnly: true },
		{ id: 'DEMO-CATALOG-P-004', code: 'DEMO-P-04', name: 'Penyelesaian remedial demo', track: 'BUSINESS', group: 'Remedial', type: 'Q', unit: 'akun', target: 30, weight: 0.15, dataSource: 'Log remedial demo', state: 'DRAFT', demoOnly: true },
		{ id: 'DEMO-CATALOG-O-001', code: 'DEMO-O-01', name: 'SLA layanan operasional demo', track: 'NON_BUSINESS', group: 'Layanan', type: 'N', unit: 'menit', target: 30, weight: 0.25, dataSource: 'Log layanan demo', state: 'ACTIVE', demoOnly: true },
		{ id: 'DEMO-CATALOG-O-002', code: 'DEMO-O-02', name: 'Akurasi dokumen demo', track: 'NON_BUSINESS', group: 'Dokumen', type: 'Q', unit: '%', target: 98, weight: 0.25, dataSource: 'Daftar kontrol demo', state: 'ACTIVE', demoOnly: true },
		{ id: 'DEMO-CATALOG-O-003', code: 'DEMO-O-03', name: 'Penyelesaian transaksi demo', track: 'NON_BUSINESS', group: 'Transaksi', type: 'Q', unit: 'transaksi', target: 120, weight: 0.2, dataSource: 'Rekap transaksi demo', state: 'ACTIVE', demoOnly: true },
		{ id: 'DEMO-CATALOG-O-004', code: 'DEMO-O-04', name: 'Kesiapan rekonsiliasi demo', track: 'NON_BUSINESS', group: 'Rekonsiliasi', type: 'L', unit: '%', target: 100, weight: 0.15, dataSource: 'Checklist rekonsiliasi demo', state: 'DRAFT', demoOnly: true }
	]);
	let demoAssignmentMappings = $state<Record<StaffTrackKind, DemoAssignmentMapping>>({
		BUSINESS: {
			selectedIds: ['DEMO-CATALOG-P-001', 'DEMO-CATALOG-P-002', 'DEMO-CATALOG-P-003'],
			targets: {
				'DEMO-CATALOG-P-001': '100',
				'DEMO-CATALOG-P-002': '50',
				'DEMO-CATALOG-P-003': '95'
			},
			weights: {
				'DEMO-CATALOG-P-001': '33.33',
				'DEMO-CATALOG-P-002': '33.33',
				'DEMO-CATALOG-P-003': '33.34'
			},
			savedAt: '2026-11-14T08:00:00+07:00'
		},
		NON_BUSINESS: {
			selectedIds: [],
			targets: {},
			weights: {},
			savedAt: null
		}
	});
	let demoPositionMappings = $state<DemoPositionMapping[]>([
		{
			id: 'POS-STAFF-BUSINESS',
			name: 'Staf Pemasaran',
			level: 'Staf',
			orgUnitName: 'KCU Utama',
			scopeLabel: 'Jalur dana, kredit, dan remedial',
			track: 'BUSINESS',
			status: 'ACTIVE',
			mappingItemIds: ['DEMO-CATALOG-P-001', 'DEMO-CATALOG-P-002', 'DEMO-CATALOG-P-003'],
			targets: { 'DEMO-CATALOG-P-001': '100', 'DEMO-CATALOG-P-002': '50', 'DEMO-CATALOG-P-003': '95' },
			weights: { 'DEMO-CATALOG-P-001': '33.33', 'DEMO-CATALOG-P-002': '33.33', 'DEMO-CATALOG-P-003': '33.34' },
			note: 'Mapping demo mengikuti paket Pemasaran/Bisnis yang sudah tersedia untuk self-assignment.'
		},
		{
			id: 'POS-STAFF-NON-BUSINESS',
			name: 'Staf Operasional',
			level: 'Staf',
			orgUnitName: 'KCU Utama',
			scopeLabel: 'Jalur layanan dan backoffice',
			track: 'NON_BUSINESS',
			status: 'ACTIVE',
			mappingItemIds: [],
			targets: {},
			weights: {},
			note: 'Belum ada mapping aktif untuk jalur Operasional/Non-bisnis.'
		},
		{
			id: 'POS-STAFF-PUSAT-OPS',
			name: 'Staf Sistem dan Jaringan TI',
			level: 'Staf',
			orgUnitName: 'Divisi Operasional',
			scopeLabel: 'Sistem, jaringan TI, dan proses operasional pusat',
			track: 'NON_BUSINESS',
			status: 'ACTIVE',
			mappingItemIds: ['DEMO-CATALOG-O-001', 'DEMO-CATALOG-O-002', 'DEMO-CATALOG-O-003'],
			targets: { 'DEMO-CATALOG-O-001': '30', 'DEMO-CATALOG-O-002': '98', 'DEMO-CATALOG-O-003': '120' },
			weights: { 'DEMO-CATALOG-O-001': '33.33', 'DEMO-CATALOG-O-002': '33.33', 'DEMO-CATALOG-O-003': '33.34' },
			note: 'Mapping demo untuk Staf Sistem dan Jaringan TI di Divisi Operasional.'
		},
		{
			id: 'POS-KABID-BUSINESS',
			name: 'Kepala Bidang Pemasaran',
			level: 'Supervisor 1',
			orgUnitName: 'Divisi Bisnis Demo',
			scopeLabel: 'Pengawasan target Pemasaran/Bisnis',
			track: 'BUSINESS',
			status: 'ACTIVE',
			mappingItemIds: ['DEMO-CATALOG-P-001', 'DEMO-CATALOG-P-002'],
			targets: { 'DEMO-CATALOG-P-001': '100', 'DEMO-CATALOG-P-002': '50' },
			weights: { 'DEMO-CATALOG-P-001': '50', 'DEMO-CATALOG-P-002': '50' },
			note: 'Mapping demo untuk reviewer bidang Pemasaran.'
		},
		{
			id: 'POS-KABID-NON-BUSINESS',
			name: 'Kepala Bidang Operasional',
			level: 'Supervisor 1',
			orgUnitName: 'Divisi Operasional Demo',
			scopeLabel: 'Pengawasan target Operasional/Non-bisnis',
			track: 'NON_BUSINESS',
			status: 'ACTIVE',
			mappingItemIds: ['DEMO-CATALOG-O-001', 'DEMO-CATALOG-O-002'],
			targets: { 'DEMO-CATALOG-O-001': '30', 'DEMO-CATALOG-O-002': '98' },
			weights: { 'DEMO-CATALOG-O-001': '50', 'DEMO-CATALOG-O-002': '50' },
			note: 'Mapping demo untuk reviewer bidang Operasional.'
		},
		{
			id: 'POS-KEPALA-CABANG',
			name: 'Kepala Cabang',
			level: 'Pimpinan unit',
			orgUnitName: 'KCU Utama',
			scopeLabel: 'Ringkasan kinerja cabang lintas jalur',
			track: 'BOTH',
			status: 'ACTIVE',
			mappingItemIds: ['DEMO-CATALOG-P-001', 'DEMO-CATALOG-O-001'],
			targets: { 'DEMO-CATALOG-P-001': '100', 'DEMO-CATALOG-O-001': '30' },
			weights: { 'DEMO-CATALOG-P-001': '50', 'DEMO-CATALOG-O-001': '50' },
			note: 'Mapping demo lintas jalur untuk melihat ringkasan kinerja cabang.'
		}
	]);

	const roleNames: Record<string, string> = {
		ADMIN_KPI: 'Admin KPI',
		SDM: 'Admin KPI / SDM',
		DIREKSI: 'Direksi',
		KEPALA_DIVISI: 'Kepala Divisi',
		KEPALA_BIDANG: 'Kepala Bidang',
		KEPALA_SUB_BIDANG: 'Kepala Sub Bidang',
		KORWIL: 'Korwil',
		KEPALA_CABANG: 'Kepala Cabang',
		PEGAWAI: 'Staf'
	};
	const statusNames: Record<string, string> = {
		DRAFT: 'Draf',
		SUBMITTED: 'Diajukan',
		REVISION: 'Revisi',
		VERIFIED: 'Terverifikasi',
		FINAL: 'Final'
	};
	const auditActionNames: Record<string, string> = {
		CONTRACT_SUBMITTED: 'Kontrak diajukan',
		CONTRACT_VERIFIED: 'Kontrak diverifikasi',
		CONTRACT_REVISION: 'Kontrak dikembalikan untuk revisi',
		CONTRACT_FINAL: 'Kontrak difinalisasi',
		CONTRACT_ITEM_UPDATED: 'Item kontrak diperbarui',
		CONTRACT_ITEM_REVISED: 'Nilai KPI dikoreksi penilai',
		BEHAVIOR_EVALUATION_UPDATED: 'Nilai perilaku diperbarui',
		BEHAVIOR_EVALUATION_REVISED: 'Nilai perilaku dikoreksi penilai'
	};
	const periodStatusNames: Record<PeriodStatus, string> = {
		DRAFT: 'Draft',
		OPEN: 'Open',
		CLOSED: 'Closed'
	};
	const periodStatusOptions: PeriodStatus[] = ['DRAFT', 'OPEN', 'CLOSED'];
	const periodCadenceNames: Record<number, string> = {
		1: '1 bulan',
		3: '3 bulan (triwulan)',
		6: '6 bulan (semester)',
		12: '12 bulan (tahunan)'
	};

	function periodEndDateInput(startValue = periodStartDate, cadence = periodCadenceMonths): string {
		if (!startValue || !cadence) return '';
		const [year, month, day] = startValue.split('-').map(Number);
		if (![year, month, day].every(Number.isFinite)) return '';
		const nextMonthIndex = month - 1 + cadence;
		const nextYear = year + Math.floor(nextMonthIndex / 12);
		const nextMonth = nextMonthIndex % 12;
		const nextDay = Math.min(day, new Date(nextYear, nextMonth + 1, 0).getDate());
		const end = new Date(nextYear, nextMonth, nextDay);
		end.setDate(end.getDate() - 1);
		return [end.getFullYear(), String(end.getMonth() + 1).padStart(2, '0'), String(end.getDate()).padStart(2, '0')].join('-');
	}

	function periodDateLabel(value: string): string {
		if (!value) return 'Belum dipilih';
		return new Intl.DateTimeFormat('id-ID', {
			day: 'numeric',
			month: 'long',
			year: 'numeric'
		}).format(new Date(value + 'T00:00:00'));
	}

	function shiftIsoDateByMonths(value: string, monthOffset: number): string {
		const [year, month, day] = value.split('-').map(Number);
		if (![year, month, day].every(Number.isFinite)) return '';
		const monthIndex = year * 12 + month - 1 + monthOffset;
		const targetYear = Math.floor(monthIndex / 12);
		const targetMonth = ((monthIndex % 12) + 12) % 12;
		const targetDay = Math.min(day, new Date(Date.UTC(targetYear, targetMonth + 1, 0)).getUTCDate());
		return `${targetYear}-${String(targetMonth + 1).padStart(2, '0')}-${String(targetDay).padStart(2, '0')}`;
	}

	function shiftIsoDateByDays(value: string, dayOffset: number): string {
		const date = new Date(`${value}T00:00:00Z`);
		if (Number.isNaN(date.getTime())) return '';
		date.setUTCDate(date.getUTCDate() + dayOffset);
		return date.toISOString().slice(0, 10);
	}

	function measurementPeriodRange(startDate: string, cadenceMonths: number): string {
		if (!startDate || !cadenceMonths) return '';
		const measurementStart = shiftIsoDateByMonths(startDate, -cadenceMonths);
		const measurementEnd = shiftIsoDateByDays(startDate, -1);
		return measurementStart && measurementEnd
			? `${periodDateLabel(measurementStart)} s.d. ${periodDateLabel(measurementEnd)}`
			: '';
	}

	function cycleCadenceMonths(cycle: Cycle | null): number {
		if (!cycle) return 0;
		return cycle.cadence === 'QUARTERLY' ? 3 : cycle.cadence === 'SEMIANNUAL' ? 6 : cycle.cadence === 'ANNUAL' ? 12 : 1;
	}

	function cycleMeasurementRange(cycle: Cycle | null): string {
		if (!cycle) return '';
		return measurementPeriodRange(cycle.starts_at, cycleCadenceMonths(cycle));
	}

	function cycleMeasurementDates(cycle: Cycle | null): { startsAt: string; endsAt: string } {
		if (!cycle) return { startsAt: '', endsAt: '' };
		const cadenceMonths = cycleCadenceMonths(cycle);
		return {
			startsAt: shiftIsoDateByMonths(cycle.starts_at, -cadenceMonths),
			endsAt: shiftIsoDateByDays(cycle.starts_at, -1)
		};
	}

	function cycleMeasurementLabel(cycle: Cycle | null): string {
		if (!cycle) return '';
		const cadenceMonths = cycleCadenceMonths(cycle);
		const { startsAt, endsAt } = cycleMeasurementDates(cycle);
		const monthYearLabel = (value: string) => new Intl.DateTimeFormat('id-ID', {
			month: 'long',
			year: 'numeric'
		}).format(new Date(`${value}T00:00:00`));
		if (!startsAt || !endsAt) return '';
		return cadenceMonths === 1 ? monthYearLabel(startsAt) : `${monthYearLabel(startsAt)} – ${monthYearLabel(endsAt)}`;
	}

	function contractMeasurementRange(contract: PerformanceContract): string {
		return cycleMeasurementRange(cycles.find((cycle) => cycle.id === contract.cycle_id) ?? null);
	}

	function periodCadenceLabel(months: number): string {
		return periodCadenceNames[months] ?? `${months} bulan`;
	}

	function cycleToPeriod(cycle: Cycle): DemoPeriod {
		const cadenceMonths = cycle.cadence === 'QUARTERLY' ? 3 : cycle.cadence === 'SEMIANNUAL' ? 6 : cycle.cadence === 'ANNUAL' ? 12 : 1;
		return {
			id: cycle.id,
			name: cycle.name,
			cadenceMonths,
			startsAt: cycle.starts_at,
			endsAt: cycle.ends_at,
			status: cycle.status as PeriodStatus
		};
	}

	function selectedAdminPeriod(): DemoPeriod | null {
		return adminPeriodFilter === 'ALL' ? null : demoPeriods.find((period) => period.id === adminPeriodFilter) ?? null;
	}

	function selectedAdminPeriodLabel(): string {
		return selectedAdminPeriod()?.name ?? (adminPeriodFilter === 'ALL' ? 'Semua periode' : 'Periode aktif');
	}

	function selectedAdminPeriodDateRange(): string {
		const period = selectedAdminPeriod();
		return period ? `${periodDateLabel(period.startsAt)} - ${periodDateLabel(period.endsAt)}` : 'Seluruh periode';
	}

	function periodMatchesAdminFilter(periodName: string): boolean {
		return adminPeriodFilter === 'ALL' || periodName === selectedAdminPeriod()?.name;
	}

	function periodStatusDescription(status: PeriodStatus): string {
		if (status === 'OPEN') return 'Pengisian dan penilaian dapat berjalan sesuai workflow.';
		if (status === 'CLOSED') return 'Pengisian ditutup; proses review dapat dilanjutkan.';
		return 'Konfigurasi masih dapat diperiksa sebelum periode dibuka.';
	}

	function setCycleStatusDraft(cycleId: string, status: PeriodStatus): void {
		cycleStatusDrafts = { ...cycleStatusDrafts, [cycleId]: status };
	}

	async function saveCycleStatus(period: DemoPeriod): Promise<void> {
		const status = cycleStatusDrafts[period.id] ?? period.status;
		if (status === period.status) return;

		errorMessage = '';
		successMessage = '';
		busy = true;
		try {
			await api<Envelope<Cycle>>(`/api/v1/cycles/${encodeURIComponent(period.id)}`, {
				method: 'PATCH',
				body: JSON.stringify({ status })
			});
			await refreshWorkspace();
			successMessage = `${period.name} diubah menjadi ${periodStatusNames[status]}.`;
		} catch (error) {
			errorMessage = error instanceof Error ? error.message : 'Status periode belum diperbarui.';
		} finally {
			busy = false;
		}
	}

	async function saveDemoPeriod(): Promise<void> {
		errorMessage = '';
		successMessage = '';
		if (!periodName.trim()) {
			errorMessage = 'Nama periode wajib diisi.';
			return;
		}
		if (!periodStartDate || !periodEndDateInput()) {
			errorMessage = 'Tanggal mulai belum valid.';
			return;
		}

		busy = true;
		try {
			const response = await api<Envelope<Cycle>>('/api/v1/cycles', {
				method: 'POST',
				body: JSON.stringify({
					name: periodName.trim(),
					cadence_months: periodCadenceMonths,
					starts_at: periodStartDate,
					status: periodStatus
				})
			});
			const savedPeriod = cycleToPeriod(response.data);
			await refreshWorkspace();
			successMessage = `${savedPeriod.name} tersimpan untuk ${periodCadenceLabel(savedPeriod.cadenceMonths)}.`;
		} catch (error) {
			errorMessage = error instanceof Error ? error.message : 'Periode belum tersimpan.';
		} finally {
			busy = false;
		}
	}

	const demoIdentityGroups: Omit<DemoIdentityGroup, 'identities'>[] = [
		{
			key: 'admin',
			label: 'Administrasi',
			description: 'Pengelolaan periode penilaian.',
			ids: ['DEMO-SDM-001']
		},
		{
			key: 'pimpinan',
			label: 'KCU Utama · Pimpinan',
			description: 'Kepala Cabang dan Kepala Bidang.',
			ids: ['DEMO-KACAB-001', 'DEMO-KABID-OPS-001', 'DEMO-KABID-001']
		},
		{
			key: 'kredit',
			label: 'KCU Utama · Kredit',
			description: 'Kepala Sub Bidang Kredit dan Account Officer Kredit.',
			ids: ['DEMO-KASUBBID-001', 'DEMO-AO-KREDIT-001']
		},
		{
			key: 'operasional',
			label: 'KCU Utama · Operasional',
			description: 'Bidang Operasional dan Teller.',
			ids: ['DEMO-OPERASIONAL-001']
		}
	];

	function availableIdentityGroups(): DemoIdentityGroup[] {
		return demoIdentityGroups
			.map((group) => ({
				...group,
				identities: group.ids
					.map((id) => identities.find((identity) => identity.id === id))
					.filter((identity): identity is DemoIdentity => Boolean(identity))
			}))
			.filter((group) => group.identities.length > 0);
	}

	function selectedIdentity(): DemoIdentity | null {
		return identities.find((identity) => identity.id === selectedIdentityId) ?? null;
	}

	async function loadDemoIdentities(): Promise<void> {
		identitiesLoading = true;
		identitiesError = '';
		try {
			const response = await api<Envelope<DemoIdentity[]>>('/api/v1/demo/identities');
			identities = response.data;
			if (!identities.some((identity) => identity.id === selectedIdentityId)) selectedIdentityId = '';
		} catch (error) {
			identities = [];
		identitiesError = error instanceof Error ? error.message : 'Daftar profil pegawai belum dapat dimuat.';
		} finally {
			identitiesLoading = false;
		}
	}

	function visibleContracts(): PerformanceContract[] {
		if (view === 'assignment') return [];
		if (view === 'mine') {
			return contracts.filter((contract) => contract.subject_id === currentUser?.employee_id);
		}
		if (view === 'reviews') {
			if (hasAccessRole('SDM')) {
				return contracts.filter((contract) => contract.status === 'VERIFIED');
			}
			return contracts.filter(
				(contract) =>
					contract.reviewer_employee_id === currentUser?.employee_id &&
					contract.status === 'SUBMITTED'
			);
		}
		return contracts;
	}

	function roleName(role: string): string {
		return roleNames[role] ?? role;
	}

	function hasAccessRole(accessRole: string): boolean {
		if (!currentUser) return false;
		return currentUser.access_roles?.includes(accessRole) ?? currentUser.access_role === accessRole;
	}

	function hasAdminAccess(): boolean {
		return hasAccessRole('ADMIN_KPI') || hasAccessRole('SDM');
	}

	function roleListLabel(primaryRole: string, accessRoles?: string[]): string {
		const resolvedRoles = accessRoles?.length ? accessRoles : [primaryRole];
		return [...new Set(resolvedRoles)].map(roleName).join(' + ');
	}

	function identityRoleLabel(identity: DemoIdentity | null | undefined): string {
		return identity ? roleListLabel(identity.access_role, identity.access_roles) : '';
	}

	function canSelfAssignRole(): boolean {
		return ['PEGAWAI', 'KEPALA_SUB_BIDANG', 'KEPALA_BIDANG'].some(hasAccessRole);
	}

	function canReviewAssignedContracts(): boolean {
		return ['KEPALA_SUB_BIDANG', 'KEPALA_BIDANG', 'KEPALA_CABANG'].some(hasAccessRole);
	}

	function staffTrack(): StaffTrack {
		const context = `${currentUser?.org_unit_name ?? ''} ${currentUser?.position_name ?? ''}`.toLocaleLowerCase('id-ID');
		if (context.includes('pemasaran') || context.includes('kredit') || context.includes('account officer')) return 'BUSINESS';
		if (context.includes('operasional') || context.includes('backoffice')) return 'NON_BUSINESS';
		return null;
	}

	function staffTrackLabel(): string {
		if (staffTrack() === 'BUSINESS') return 'Pemasaran / Bisnis';
		if (staffTrack() === 'NON_BUSINESS') return 'Operasional / Non-bisnis';
		return 'Katalog sesuai jabatan';
	}

	function staffTrackDescription(): string {
		if (staffTrack() === 'BUSINESS') return 'KPI mengikuti jabatan aktif dan indikator yang ditetapkan Admin.';
		if (staffTrack() === 'NON_BUSINESS') return 'KPI mengikuti jabatan aktif dan indikator yang ditetapkan Admin.';
		return 'Isi realisasi dan lampirkan bukti untuk setiap indikator jabatan.';
	}

	function staffAssignmentKey(): string {
		const track = staffTrack();
		if (!currentUser || !track) return '';
		return `${currentUser.employee_id}:${track}`;
	}

	function staffReviewTaskId(key = staffAssignmentKey()): string {
		return key ? `DEMO-SUBMISSION-${key.replace(/[^A-Z0-9]+/gi, '-')}` : '';
	}

	function staffAssignmentSnapshotItems(): HistoryItem[] {
		return kpiDefinitions
			.filter((definition) => selectedDefinitionIds.includes(definition.id))
			.map((definition) => ({
				code: definition.code,
				name: definition.name,
				target: String(targetDrafts[definition.id] ?? definition.sample_target),
				actual: 'Belum diisi',
				score: null,
				unit: definition.unit,
				evidence: 'Belum ada bukti',
				note: 'Self-assessment baru diajukan dari mapping Admin.'
			}));
	}

	function saveStaffAssignmentDraft(): void {
		const key = staffAssignmentKey();
		const track = staffTrack();
		if (!key || !currentUser || !track) return;
		const savedAt = new Date().toISOString();
		staffAssignmentRecords = {
			...staffAssignmentRecords,
			[key]: {
				status: 'DRAFT',
				selectedIds: [...selectedDefinitionIds],
				savedAt,
				submittedAt: null,
				track,
				employeeName: currentUser.display_name,
				positionName: currentUser.position_name,
				periodName: activeMonthlyCycle()?.name ?? 'Periode aktif',
				items: staffAssignmentSnapshotItems()
			}
		};
		staffAssignmentStatus = 'DRAFT';
	}

	function reopenStaffAssignment(): void {
		if (staffAssignmentStatus !== 'REVISION') return;
		staffAssignmentStatus = 'DRAFT';
		errorMessage = '';
		successMessage = 'Draf dibuka kembali. Perbarui pilihan KPI bila perlu, lalu simpan sebelum mengajukan ulang.';
	}

	function submitSelfAssessment(): void {
		errorMessage = '';
		successMessage = '';
		if (staffAssignmentStatus !== 'DRAFT') {
			errorMessage = 'Simpan draf terlebih dahulu sebelum mengajukan self-assessment.';
			return;
		}
		if (selectedDefinitionIds.length === 0) {
			errorMessage = 'Pilih minimal satu indikator KPI sebelum mengajukan self-assessment.';
			return;
		}
		const key = staffAssignmentKey();
		if (!key) {
			errorMessage = 'Identitas pegawai belum tersedia untuk pengajuan.';
			return;
		}
		const previous = staffAssignmentRecords[key];
		staffAssignmentRecords = {
			...staffAssignmentRecords,
			[key]: {
				status: 'SUBMITTED',
				selectedIds: [...selectedDefinitionIds],
				savedAt: previous?.savedAt ?? new Date().toISOString(),
				submittedAt: new Date().toISOString(),
				track: previous?.track ?? staffTrack() ?? 'BUSINESS',
				employeeName: previous?.employeeName ?? currentUser?.display_name ?? 'Pegawai',
				positionName: previous?.positionName ?? currentUser?.position_name ?? 'Staf cabang',
				periodName: previous?.periodName ?? activeMonthlyCycle()?.name ?? 'Periode aktif',
				items: staffAssignmentSnapshotItems()
			}
		};
		const taskId = staffReviewTaskId(key);
		if (taskId && demoReviewDecisions[taskId]) {
			const nextDecisions = { ...demoReviewDecisions };
			delete nextDecisions[taskId];
			demoReviewDecisions = nextDecisions;
		}
		staffAssignmentStatus = 'SUBMITTED';
		successMessage = 'Penilaian diajukan. Pilihan KPI terkunci sampai reviewer memberi keputusan.';
	}

	function canViewStaffHistory(): boolean {
		return canSelfAssignRole();
	}

	function visibleHistoryEntries(): AssessmentHistoryEntry[] {
		if (!currentUser) return [];
		return contracts
			.filter((contract) => contract.subject_id === currentUser?.employee_id)
			.map((contract) => {
				const cycle = cycles.find((item) => item.id === contract.cycle_id);
				const measurementPeriod = cycleMeasurementDates(cycle ?? null);
				return {
					id: contract.id,
					track: staffTrack() ?? 'NON_BUSINESS',
					periodName: cycleMeasurementLabel(cycle ?? null) || cycle?.name || 'Periode tidak tersedia',
					cadence: cycle ? periodCadenceLabel(cycleCadenceMonths(cycle)) : 'Periode',
					startsAt: measurementPeriod.startsAt || cycle?.starts_at || '',
					endsAt: measurementPeriod.endsAt || cycle?.ends_at || '',
					status: contract.status,
					score: contract.score?.final_score ?? null,
					reviewer: contract.review_workflow?.current_slot
						? contract.review_workflow.reviewer_slots[contract.review_workflow.current_slot]?.name ?? null
						: contract.review_history?.at(-1)?.reviewer_name ?? null,
					finalDate: null,
					submittedDate: contract.created_at?.slice(0, 10) ?? null,
					note: contract.status === 'DRAFT'
						? 'Penilaian tersimpan sebagai draf.'
						: contract.status === 'SUBMITTED'
							? `Menunggu Penilai ${contract.review_workflow?.current_slot?.slice(1) ?? 'berikutnya'}.`
							: contract.status === 'REVISION'
								? 'Penilaian dikembalikan untuk diperbaiki.'
								: 'Riwayat penilaian periode Anda.',
					review_workflow: contract.review_workflow,
					review_history: contract.review_history,
					items: contract.items.map((item) => ({
						code: item.code,
						name: item.name,
						target: String(item.target),
						actual: item.actual === null ? 'Belum diisi' : String(item.actual),
						score: item.score,
						unit: item.unit,
						note: item.note
					}))
				};
			})
			.sort((a, b) => b.startsAt.localeCompare(a.startsAt));
	}

	function selectedHistory(): AssessmentHistoryEntry | null {
		return visibleHistoryEntries().find((entry) => entry.id === selectedHistoryId) ?? null;
	}

	function openHistory(): void {
		const entries = visibleHistoryEntries();
		selectedHistoryId = entries[0]?.id ?? '';
		setView('history');
	}

	function canReviewInbox(): boolean {
		return false;
	}

	function submittedStaffReviewTasks(): DemoReviewTask[] {
		return Object.entries(staffAssignmentRecords).flatMap(([key, record]) => {
			if (record.status !== 'SUBMITTED') return [];
			const taskId = `DEMO-SUBMISSION-${key.replace(/[^A-Z0-9]+/gi, '-')}`;
			const decision = demoReviewDecisions[taskId];
			const submittedAt = record.submittedAt ?? record.savedAt;
			return [{
				id: taskId,
				track: record.track,
				employeeName: record.employeeName,
				positionName: record.positionName,
				periodName: record.periodName,
				status: decision?.decision === 'REVISION' ? 'REVISION' : 'SUBMITTED',
				ageDays: 0,
				submittedAt: submittedAt.slice(0, 10),
				dueAt: activeMonthlyCycle()?.ends_at ?? '2026-11-30',
				score: null,
				lastAction: decision?.decision === 'REVISION' ? 'Dikembalikan untuk perbaikan' : decision?.decision === 'VERIFIED' ? 'Diverifikasi reviewer' : 'Self-assessment baru dari staf cabang',
				items: record.items
			} satisfies DemoReviewTask];
		});
	}

	function visibleReviewTasks(): DemoReviewTask[] {
		if (!canReviewInbox()) return [];
		return [...submittedStaffReviewTasks(), ...demoReviewTasks].filter((task) => {
			if (reviewTrackFilter !== 'ALL' && task.track !== reviewTrackFilter) return false;
			if (reviewStatusFilter !== 'ALL' && task.status !== reviewStatusFilter) return false;
			if (reviewAgeFilter === '0_3' && task.ageDays > 3) return false;
			if (reviewAgeFilter === '4_7' && (task.ageDays < 4 || task.ageDays > 7)) return false;
			if (reviewAgeFilter === '8_PLUS' && task.ageDays < 8) return false;
			return true;
		});
	}

	function selectedReviewTask(): DemoReviewTask | null {
		const visible = visibleReviewTasks();
		return visible.find((task) => task.id === selectedReviewTaskId) ?? visible[0] ?? null;
	}

	function selectedReviewDecision(): DemoReviewDecision | null {
		const task = selectedReviewTask();
		return task ? demoReviewDecisions[task.id] ?? null : null;
	}

	function selectedReviewFinalization(): DemoStaffFinalization | null {
		const task = selectedReviewTask();
		return task ? demoStaffFinalizations[task.id] ?? null : null;
	}

	function selectReviewTask(taskId: string): void {
		selectedReviewTaskId = taskId;
		reviewDecisionNote = demoReviewDecisions[taskId]?.note ?? '';
		errorMessage = '';
		successMessage = '';
	}

	function openReviews(): void {
		setView('reviews');
		const first = visibleReviewTasks()[0];
		selectReviewTask(first?.id ?? '');
	}

	function saveDemoReviewDecision(decision: DemoReviewDecision['decision']): void {
		const task = selectedReviewTask();
		const note = reviewDecisionNote.trim();
		if (!task) return;
		if (selectedReviewFinalization()) {
			errorMessage = 'Penilaian sudah final dan terkunci. Keputusan reviewer tidak dapat diubah.';
			successMessage = '';
			return;
		}
		if (!note) {
			errorMessage = 'Catatan keputusan reviewer wajib diisi.';
			successMessage = '';
			return;
		}
		demoReviewDecisions = {
			...demoReviewDecisions,
			[task.id]: {
				decision,
				note,
				savedAt: new Date().toISOString(),
				actorName: currentUser?.display_name ?? 'Reviewer',
				actorRole: currentUser ? roleNames[currentUser.access_role] ?? currentUser.access_role : 'Reviewer / Kepala Cabang'
			}
		};
		errorMessage = '';
		successMessage = decision === 'VERIFIED' ? 'Penilaian ditandai terverifikasi.' : 'Penilaian dikembalikan untuk revisi.';
	}

	function saveDemoFinalization(): void {
		const task = selectedReviewTask();
		const decision = selectedReviewDecision();
		const note = reviewDecisionNote.trim();
		if (!task || decision?.decision !== 'VERIFIED') {
			errorMessage = 'Verifikasi reviewer diperlukan sebelum finalisasi.';
			successMessage = '';
			return;
		}
		if (selectedReviewFinalization()) {
			errorMessage = 'Penilaian ini sudah final dan terkunci.';
			successMessage = '';
			return;
		}
		if (!note) {
			errorMessage = 'Catatan finalisasi wajib diisi.';
			successMessage = '';
			return;
		}
		demoStaffFinalizations = {
			...demoStaffFinalizations,
			[task.id]: {
				note,
				finalizedAt: new Date().toISOString(),
				actorName: currentUser?.display_name ?? 'Admin KPI',
				actorRole: currentUser ? roleNames[currentUser.access_role] ?? currentUser.access_role : 'Admin KPI / SDM'
			}
		};
		errorMessage = '';
		successMessage = 'Penilaian difinalisasi dan snapshot dikunci.';
	}

	function reviewTrackLabel(track: StaffTrackKind): string {
		return track === 'BUSINESS' ? 'Pemasaran / Bisnis' : 'Operasional / Non-bisnis';
	}

	function reviewAgeLabel(days: number): string {
		return days === 1 ? '1 hari' : `${days} hari`;
	}

	function reviewAgeTone(days: number): string {
		if (days >= 8) return 'review-age-overdue';
		if (days >= 4) return 'review-age-watch';
		return 'review-age-fresh';
	}

	function canViewMonitoring(): boolean {
		return hasAdminAccess();
	}

	function visibleMonitoringRecords(): DemoMonitoringRecord[] {
		if (!canViewMonitoring()) return [];
		return demoMonitoringRecords.filter((record) => {
			if (record.status === 'FINAL' && monitoringStatusFilter !== 'FINAL') return false;
			if (!periodMatchesAdminFilter(record.periodName)) return false;
			if (monitoringTrackFilter !== 'ALL' && record.track !== monitoringTrackFilter) return false;
			if (monitoringStatusFilter !== 'ALL' && record.status !== monitoringStatusFilter) return false;
			return true;
		});
	}

	function monitoringRecordsForPeriod(): DemoMonitoringRecord[] {
		return demoMonitoringRecords.filter((record) => periodMatchesAdminFilter(record.periodName));
	}

	function monitoringStatusCount(status: MonitoringStatus): number {
		return monitoringRecordsForPeriod().filter((record) => record.status === status).length;
	}

	function monitoringTrackCount(track: StaffTrackKind): number {
		return monitoringRecordsForPeriod().filter((record) => record.track === track).length;
	}

	function monitoringOverdueCount(): number {
		return monitoringRecordsForPeriod().filter((record) => record.overdue).length;
	}

	function monitoringAgeTone(record: DemoMonitoringRecord): string {
		if (record.overdue) return 'monitoring-age-overdue';
		if (record.ageDays >= 4) return 'monitoring-age-watch';
		return 'monitoring-age-fresh';
	}

	function monitoringTrackLabel(track: StaffTrackKind): string {
		return track === 'BUSINESS' ? 'Pemasaran / Bisnis' : 'Operasional / Non-bisnis';
	}

	function openMonitoring(): void {
		monitoringDrilldownFocus = null;
		monitoringTrackFilter = 'ALL';
		monitoringStatusFilter = 'ALL';
		adminPeriodFilter = 'DEMO-PERIOD-002';
		setView('monitoring');
	}

	function openMonitoringDrilldown(periodId: string, status: MonitoringStatus, track: ReviewTrackFilter = 'ALL'): void {
		monitoringDrilldownFocus = { periodId, status, track };
		adminPeriodFilter = periodId;
		monitoringStatusFilter = status;
		monitoringTrackFilter = track;
		setView('monitoring');
	}

	function canViewAuditTrail(): boolean {
		return hasAdminAccess();
	}

	function dynamicAuditTrailEntries(): DemoAuditTrailEntry[] {
		return Object.entries(staffAssignmentRecords).flatMap(([key, record]) => {
			if (record.status !== 'SUBMITTED') return [];
			const taskId = staffReviewTaskId(key);
			const decision = demoReviewDecisions[taskId];
			const finalization = demoStaffFinalizations[taskId];
			const entries: DemoAuditTrailEntry[] = [];
			if (decision?.decision === 'VERIFIED') {
				entries.push({
					id: `LIVE-AUDIT-${taskId}-VERIFIED`,
					track: record.track,
					status: 'VERIFIED',
					actorName: decision.actorName,
					actorRole: decision.actorRole,
					actedAt: decision.savedAt,
					employeeName: record.employeeName,
					positionName: record.positionName,
					periodName: record.periodName,
					note: decision.note,
					items: record.items,
					score: record.items.some((item) => item.score !== null) ? record.items.filter((item) => item.score !== null).reduce((sum, item) => sum + (item.score ?? 0), 0) / record.items.filter((item) => item.score !== null).length : null,
					reviewerName: decision.actorName,
					submittedAt: record.submittedAt,
					finalDate: null
				});
			}
			if (finalization) {
				entries.push({
					id: `LIVE-AUDIT-${taskId}-FINAL`,
					track: record.track,
					status: 'FINAL',
					actorName: finalization.actorName,
					actorRole: finalization.actorRole,
					actedAt: finalization.finalizedAt,
					employeeName: record.employeeName,
					positionName: record.positionName,
					periodName: record.periodName,
					note: finalization.note,
					items: record.items,
					score: record.items.some((item) => item.score !== null) ? record.items.filter((item) => item.score !== null).reduce((sum, item) => sum + (item.score ?? 0), 0) / record.items.filter((item) => item.score !== null).length : null,
					reviewerName: decision?.actorName ?? null,
					submittedAt: record.submittedAt,
					finalDate: finalization.finalizedAt
				});
			}
			return entries;
		});
	}

	function visibleAuditTrailEntries(): DemoAuditTrailEntry[] {
		if (!canViewAuditTrail()) return [];
		return [...dynamicAuditTrailEntries(), ...demoAuditTrailEntries].filter((entry) => {
			if (!periodMatchesAdminFilter(entry.periodName)) return false;
			if (auditTrackFilter !== 'ALL' && entry.track !== auditTrackFilter) return false;
			if (auditStatusFilter !== 'ALL' && entry.status !== auditStatusFilter) return false;
			if (auditTrailFocus && entry.employeeName !== auditTrailFocus.employeeName) return false;
			if (auditTrailFocus && entry.periodName !== auditTrailFocus.periodName) return false;
			return true;
		});
	}

	function selectedAuditTrail(): DemoAuditTrailEntry | null {
		const entries = visibleAuditTrailEntries();
		return entries.find((entry) => entry.id === selectedAuditTrailId) ?? entries[0] ?? null;
	}

	function auditSnapshotScore(items: HistoryItem[]): number | null {
		const scoredItems = items.filter((item) => item.score !== null);
		if (scoredItems.length === 0) return null;
		return scoredItems.reduce((sum, item) => sum + (item.score ?? 0), 0) / scoredItems.length;
	}

	function auditContractSummary(): AuditContractSummary | null {
		const entry = selectedAuditTrail();
		if (entry) {
			return {
				employeeName: entry.employeeName,
				positionName: entry.positionName,
				track: entry.track,
				periodName: entry.periodName,
				status: entry.status,
				score: entry.score ?? auditSnapshotScore(entry.items),
				reviewerName: entry.reviewerName ?? (entry.status === 'FINAL' ? 'Kepala Cabang' : entry.actorName),
				submittedAt: entry.submittedAt ?? (entry.status === 'VERIFIED' ? entry.actedAt : null),
				finalDate: entry.finalDate ?? (entry.status === 'FINAL' ? entry.actedAt : null)
			};
		}
		if (!auditTrailFocus) return null;
		return {
			employeeName: auditTrailFocus.employeeName,
			positionName: 'Kontrak KPI demo',
			track: auditTrailFocus.track,
			periodName: auditTrailFocus.periodName,
			status: auditTrailFocus.status,
			score: auditTrailFocus.score,
			reviewerName: null,
			submittedAt: auditTrailFocus.submittedAt,
			finalDate: auditTrailFocus.finalDate
		};
	}

	function openAuditTrail(): void {
		auditTrailFocus = null;
		setView('audit');
		selectedAuditTrailId = visibleAuditTrailEntries()[0]?.id ?? '';
	}

	function openAuditForMonitoring(record: DemoMonitoringRecord): void {
		auditTrailFocus = {
			employeeName: record.employeeName,
			track: record.track,
			periodName: record.periodName,
			status: record.status,
			origin: monitoringDrilldownFocus ? 'COMPARISON_DRILLDOWN' : 'MONITORING',
			score: record.score,
			submittedAt: record.submittedAt,
			finalDate: record.status === 'FINAL' ? record.submittedAt : null
		};
		adminPeriodFilter = demoPeriods.find((period) => period.name === record.periodName)?.id ?? 'ALL';
		auditTrackFilter = record.track;
		auditStatusFilter = record.status === 'VERIFIED' || record.status === 'FINAL' ? record.status : 'ALL';
		setView('audit');
		const matchingEntry = visibleAuditTrailEntries()[0];
		selectedAuditTrailId = matchingEntry?.id ?? '';
	}

	function canViewPeriodComparison(): boolean {
		return hasAdminAccess();
	}

	function comparisonPeriodFor(id: string): DemoPeriod | null {
		return demoPeriods.find((period) => period.id === id) ?? null;
	}

	function comparisonSummaryFor(id: string): DemoPeriodComparison | null {
		return demoPeriodComparisons.find((summary) => summary.periodId === id) ?? null;
	}

	function comparisonStatusCount(id: string, status: MonitoringStatus): number {
		return comparisonSummaryFor(id)?.statusCounts[status] ?? 0;
	}

	function comparisonFinalCount(id: string, track: StaffTrackKind): number {
		return comparisonSummaryFor(id)?.finalByTrack[track] ?? 0;
	}

	function comparisonDelta(a: string, b: string, field: 'total' | 'final' | 'businessFinal' | 'nonBusinessFinal'): string {
		const first = comparisonSummaryFor(a);
		const second = comparisonSummaryFor(b);
		if (!first || !second) return 'Belum tersedia';
		const firstValue = field === 'total' ? first.totalContracts : field === 'final' ? first.statusCounts.FINAL : field === 'businessFinal' ? first.finalByTrack.BUSINESS : first.finalByTrack.NON_BUSINESS;
		const secondValue = field === 'total' ? second.totalContracts : field === 'final' ? second.statusCounts.FINAL : field === 'businessFinal' ? second.finalByTrack.BUSINESS : second.finalByTrack.NON_BUSINESS;
		const delta = secondValue - firstValue;
		return delta > 0 ? `+${delta}` : String(delta);
	}

	function openPeriodComparison(): void {
		setView('comparison');
	}

	function canManageCatalog(): boolean {
		return hasAdminAccess();
	}

	function visibleCatalogItems(): DemoCatalogItem[] {
		if (!canManageCatalog()) return [];
		return demoCatalogItems.filter((item) => {
			if (catalogTrackFilter !== 'ALL' && item.track !== catalogTrackFilter) return false;
			if (catalogStateFilter !== 'ALL' && item.state !== catalogStateFilter) return false;
			return true;
		});
	}

	function catalogTrackLabel(track: StaffTrackKind): string {
		return track === 'BUSINESS' ? 'Pemasaran / Bisnis' : 'Operasional / Non-bisnis';
	}

	function catalogStateLabel(state: CatalogState): string {
		return state === 'ACTIVE' ? 'Aktif' : 'Draft';
	}

	function catalogTypeLabel(type: DemoCatalogItem['type']): string {
		if (type === 'Q') return 'Kuantitatif';
		if (type === 'N') return 'Numerik durasi';
		if (type === 'L') return 'Batas maksimum';
		return 'Zero tolerance';
	}

	function openCatalog(): void {
		setView('catalog');
	}

	function resetCatalogForm(): void {
		catalogFormCode = '';
		catalogFormName = '';
		catalogFormGroup = '';
		catalogFormType = 'Q';
		catalogFormUnit = '';
		catalogFormTarget = '';
		catalogFormWeight = '';
		catalogFormSource = '';
		catalogFormState = 'DRAFT';
	}

	function saveDemoCatalogItem(): void {
		errorMessage = '';
		successMessage = '';
		const name = catalogFormName.trim();
		const group = catalogFormGroup.trim();
		const unit = catalogFormUnit.trim();
		const source = catalogFormSource.trim();
		const target = Number(catalogFormTarget);
		const weightPercent = Number(catalogFormWeight);
		if (!name || !group || !unit || !source) {
			errorMessage = 'Nama, kelompok, unit, dan sumber data wajib diisi.';
			return;
		}
		if (!Number.isFinite(target) || (catalogFormType === 'Z' ? target !== 0 : target <= 0)) {
			errorMessage = 'Target belum valid untuk tipe indikator ini.';
			return;
		}
		if (['Q', 'L'].includes(catalogFormType) && target > 100) {
			errorMessage = 'Target tipe Kuantitatif dan Batas maksimum tidak boleh lebih dari 100.';
			return;
		}
		if (!Number.isFinite(weightPercent) || weightPercent <= 0 || weightPercent > 100) {
			errorMessage = 'Bobot harus berada di antara 0,01% dan 100%.';
			return;
		}
		const prefix = catalogFormTrack === 'BUSINESS' ? 'P' : 'O';
		const nextNumber = demoCatalogItems.filter((item) => item.track === catalogFormTrack).length + 1;
		const code = (catalogFormCode.trim() || `DEMO-${prefix}-${String(nextNumber).padStart(2, '0')}`).toUpperCase();
		if (demoCatalogItems.some((item) => item.code === code)) {
			errorMessage = 'Kode indikator sudah digunakan pada katalog demo.';
			return;
		}
		demoCatalogItems = [
			...demoCatalogItems,
			{
				id: `DEMO-CATALOG-${prefix}-${String(nextNumber).padStart(3, '0')}`,
				code,
				name,
				track: catalogFormTrack,
				group,
				type: catalogFormType,
				unit,
				target,
				weight: weightPercent / 100,
				dataSource: source,
				state: catalogFormState,
				demoOnly: true
			}
		];
		successMessage = `${name} ditambahkan sebagai contoh katalog.`;
		resetCatalogForm();
	}

	function activeCatalogItemsForTrack(track: StaffTrackKind): DemoCatalogItem[] {
		return demoCatalogItems.filter((item) => item.track === track && item.state === 'ACTIVE');
	}

	function mappingForTrack(track: StaffTrackKind): DemoAssignmentMapping {
		return demoAssignmentMappings[track] ?? { selectedIds: [], targets: {}, weights: {}, savedAt: null };
	}

	function mappedCatalogItemsForTrack(track: StaffTrackKind): DemoCatalogItem[] {
		const mapping = mappingForTrack(track);
		return activeCatalogItemsForTrack(track).filter((item) => mapping.selectedIds.includes(item.id));
	}

	function demoDefinitionFromCatalogItem(item: DemoCatalogItem, mapping: DemoAssignmentMapping): KpiDefinition {
		return {
			id: item.id,
			code: item.code,
			name: item.name,
			group: item.group,
			type: item.type,
			definition: `Penetapan Admin untuk jalur ${catalogTrackLabel(item.track)}.`,
			unit: item.unit,
			default_weight: Number(mapping.weights[item.id] ?? Math.round(item.weight * 10000) / 100) / 100,
			sample_target: Number(mapping.targets[item.id] ?? item.target),
			target_note: 'Target inherited dari penetapan Admin.',
			data_source: item.dataSource,
			quality_flag: false,
			draft: false,
			demo_only: true
		};
	}

	function seedAssignmentForTrack(track: StaffTrackKind): void {
		assignmentTrack = track;
		const activeItems = activeCatalogItemsForTrack(track);
		const mapping = mappingForTrack(track);
		const hasSavedMapping = Boolean(mapping.savedAt);
		const mappedItems = hasSavedMapping
			? activeItems.filter((item) => mapping.selectedIds.includes(item.id))
			: activeItems;
		assignmentSelectedIds = mappedItems.map((item) => item.id);
		assignmentTargetDrafts = Object.fromEntries(
			mappedItems.map((item) => [item.id, mapping.targets[item.id] ?? String(item.target)])
		);
		assignmentWeightDrafts = Object.fromEntries(
			mappedItems.map((item) => [item.id, mapping.weights[item.id] ?? String(Math.round(item.weight * 10000) / 100)])
		);
		assignmentSaveState = hasSavedMapping ? 'SAVED' : 'DRAFT';
	}

	function selectedAssignmentItems(): DemoCatalogItem[] {
		return activeCatalogItemsForTrack(assignmentTrack).filter((item) => assignmentSelectedIds.includes(item.id));
	}

	function assignmentWeightTotal(): number {
		return selectedAssignmentItems().reduce((total, item) => total + (Number(assignmentWeightDrafts[item.id]) || 0), 0);
	}

	function assignmentWeightStatus(): string {
		const total = assignmentWeightTotal();
		if (selectedAssignmentItems().length === 0) return 'Belum ada indikator';
		if (Math.abs(total - 100) < 0.01) return 'Siap dipakai';
		return 'Perlu disesuaikan ke 100%';
	}

	function toggleAssignmentItem(id: string, checked: boolean): void {
		assignmentSelectedIds = checked
			? [...new Set([...assignmentSelectedIds, id])]
			: assignmentSelectedIds.filter((selectedId) => selectedId !== id);
		assignmentSaveState = 'DRAFT';
	}

	function updateAssignmentTarget(id: string, value: string): void {
		assignmentTargetDrafts = { ...assignmentTargetDrafts, [id]: value };
		assignmentSaveState = 'DRAFT';
	}

	function updateAssignmentWeight(id: string, value: string): void {
		assignmentWeightDrafts = { ...assignmentWeightDrafts, [id]: value };
		assignmentSaveState = 'DRAFT';
	}

	function normalizeAssignmentWeights(): void {
		const selected = selectedAssignmentItems();
		if (selected.length === 0) return;
		const evenWeight = String(Math.round((100 / selected.length) * 100) / 100);
		assignmentWeightDrafts = Object.fromEntries(selected.map((item) => [item.id, evenWeight]));
		assignmentSaveState = 'DRAFT';
		successMessage = 'Bobot contoh dibagi rata untuk preview penetapan.';
		errorMessage = '';
	}

	function saveDemoAssignmentMapping(): void {
		errorMessage = '';
		successMessage = '';
		const selected = selectedAssignmentItems();
		if (selected.length === 0) {
			errorMessage = 'Pilih minimal satu indikator Aktif.';
			return;
		}
		if (Math.abs(assignmentWeightTotal() - 100) >= 0.01) {
			errorMessage = 'Total bobot harus tepat 100% sebelum penetapan disimpan.';
			return;
		}
		for (const item of selected) {
			const target = Number(assignmentTargetDrafts[item.id]);
			const weight = Number(assignmentWeightDrafts[item.id]);
			if (!Number.isFinite(target) || target <= 0 || !Number.isFinite(weight) || weight <= 0) {
				errorMessage = 'Target dan bobot setiap indikator harus lebih besar dari 0.';
				return;
			}
		}
		assignmentSaveState = 'SAVED';
		demoAssignmentMappings = {
			...demoAssignmentMappings,
			[assignmentTrack]: {
				selectedIds: [...assignmentSelectedIds],
				targets: { ...assignmentTargetDrafts },
				weights: { ...assignmentWeightDrafts },
				savedAt: new Date().toISOString()
			}
		};
		successMessage = `Penetapan simulasi jalur ${catalogTrackLabel(assignmentTrack)} tersimpan untuk preview self-assignment.`;
	}

	function canManageAssignment(): boolean {
		return hasAdminAccess();
	}

	function openAssignmentMapping(): void {
		setView('positionKpis');
		void loadPositionKpiSettings();
	}

	function selectedPositionKpiSetting(): PositionKpiSetting | null {
		return positionKpiSettings.find((position) => position.id === selectedPositionKpiId) ?? null;
	}

	function activePositionKpiVersion(position: PositionKpiSetting | null): PositionKpiVersion | null {
		if (!position?.active_version_id) return null;
		return position.versions.find((version) => version.id === position.active_version_id) ?? null;
	}

	function seedPositionKpiDraft(position: PositionKpiSetting | null): void {
		const activeVersion = activePositionKpiVersion(position);
		positionKpiDraftItems = (activeVersion?.items ?? []).map((item) => ({
			...item,
			client_id: item.definition_id
		}));
	}

	async function loadPositionKpiSettings(): Promise<void> {
		positionKpiSettingsLoading = true;
		errorMessage = '';
		try {
			const response = await api<Envelope<PositionKpiSetting[]>>('/api/v1/position-kpi-settings');
			positionKpiSettings = response.data;
			const selected = response.data.find((position) => position.id === selectedPositionKpiId)
				?? response.data.find((position) => position.access_role === 'PEGAWAI')
				?? response.data[0]
				?? null;
			selectedPositionKpiId = selected?.id ?? '';
			seedPositionKpiDraft(selected);
		} catch (error) {
			errorMessage = error instanceof Error ? error.message : 'Paket KPI jabatan belum dapat dimuat.';
		} finally {
			positionKpiSettingsLoading = false;
		}
	}

	function selectPositionKpiSetting(positionId: string): void {
		selectedPositionKpiId = positionId;
		seedPositionKpiDraft(selectedPositionKpiSetting());
		positionKpiDraftItems = [...positionKpiDraftItems];
	}

	function addPositionKpiDraftItem(): void {
		const position = selectedPositionKpiSetting();
		if (!position) return;
		const nextNumber = positionKpiDraftItems.length + 1;
		positionKpiDraftItems = [...positionKpiDraftItems, {
			client_id: `new-${crypto.randomUUID()}`,
			definition_id: '',
			code: `KPI-${String(nextNumber).padStart(2, '0')}`,
			name: '',
			score_type: 'A',
			definition: '',
			unit: '',
			target: 1,
			weight_percent: 0,
			source_note: '',
			quality_flag: false,
			application_note: ''
		}];
	}

	function updatePositionKpiDraftItem(
		clientId: string,
		field: 'code' | 'name' | 'score_type' | 'definition' | 'unit' | 'target' | 'weight_percent' | 'source_note',
		value: string,
	): void {
		positionKpiDraftItems = positionKpiDraftItems.map((item) => {
			if (item.client_id !== clientId) return item;
			if (field === 'score_type') {
				const scoreType = value as PositionKpiVersionItem['score_type'];
				return { ...item, score_type: scoreType, quality_flag: scoreType === 'Q', target: scoreType === 'Z' ? 0 : item.target };
			}
			if (field === 'target' || field === 'weight_percent') {
				return { ...item, [field]: Number(value) };
			}
			return { ...item, [field]: value };
		});
	}

	function removePositionKpiDraftItem(clientId: string): void {
		positionKpiDraftItems = positionKpiDraftItems.filter((item) => item.client_id !== clientId);
	}

	function positionKpiWeightTotal(): number {
		return positionKpiDraftItems.reduce((total, item) => total + (Number(item.weight_percent) || 0), 0);
	}

	function positionKpiSaveBlockedReason(): string | null {
		if (positionKpiDraftItems.length === 0) return 'Tambahkan minimal satu indikator untuk jabatan ini.';
		if (positionKpiDraftItems.some((item) => !item.code.trim() || !item.name.trim() || !item.definition.trim() || !item.unit.trim())) {
			return 'Kode, nama, keterangan, dan satuan wajib diisi.';
		}
		if (new Set(positionKpiDraftItems.map((item) => item.code.trim().toLowerCase())).size !== positionKpiDraftItems.length) {
			return 'Kode indikator tidak boleh duplikat dalam satu paket.';
		}
		if (positionKpiDraftItems.some((item) => !Number.isFinite(item.target) || (item.score_type === 'Z' ? item.target !== 0 : item.target <= 0) || (['Q', 'L'].includes(item.score_type) && item.target > 100))) {
			return 'Target indikator belum sesuai dengan jenis pengukurannya.';
		}
		if (positionKpiDraftItems.some((item) => !Number.isFinite(item.weight_percent) || item.weight_percent <= 0)) {
			return 'Bobot setiap indikator harus lebih besar dari 0%.';
		}
		if (Math.abs(positionKpiWeightTotal() - 100) >= 0.011) return 'Total bobot seluruh indikator harus 100%.';
		return null;
	}

	async function savePositionKpiSettings(): Promise<void> {
		const position = selectedPositionKpiSetting();
		const blockedReason = positionKpiSaveBlockedReason();
		if (!position || blockedReason) {
			errorMessage = blockedReason ?? 'Pilih jabatan yang akan diatur.';
			return;
		}

		positionKpiSettingsBusy = true;
		errorMessage = '';
		successMessage = '';
		try {
			const response = await api<Envelope<PositionKpiVersion>>(`/api/v1/position-kpi-settings/${encodeURIComponent(position.id)}`, {
				method: 'PUT',
				body: JSON.stringify({
					items: positionKpiDraftItems.map(({ client_id, ...item }) => ({
						...item,
						definition_id: item.definition_id || null
					}))
				})
			});
			successMessage = response.data.version > 0
				? `Paket KPI ${position.name} tersimpan sebagai V${response.data.version}.`
				: `Paket KPI ${position.name} tersimpan.`;
			await loadPositionKpiSettings();
		} catch (error) {
			errorMessage = error instanceof Error ? error.message : 'Paket KPI jabatan belum dapat disimpan.';
		} finally {
			positionKpiSettingsBusy = false;
		}
	}

	function canManagePositions(): boolean {
		return hasAdminAccess();
	}

	function openPositions(): void {
		setView('positions');
		selectedPositionId = demoPositionMappings[0]?.id ?? '';
		positionEditorOpen = false;
		positionDeactivateId = '';
	}

	function canManageSsoMappings(): boolean {
		return hasAdminAccess();
	}

	function openSsoMappings(): void {
		if (!canManageSsoMappings()) return;
		setView('ssoMappings');
		void loadSsoMappings();
	}

	async function loadSsoMappings(): Promise<void> {
		ssoMappingLoading = true;
		errorMessage = '';
		try {
			const response = await api<Envelope<SsoMappingPayload>>('/api/v1/sso-identities');
			ssoMappings = response.data.mappings;
			ssoMappingCandidates = response.data.candidates;
			ssoMappingIssuer = response.data.issuer;
			if (!ssoMappingCandidates.some((candidate) => candidate.employee_id === ssoMappingTargetId)) {
				ssoMappingTargetId = '';
			}
		} catch (error) {
			errorMessage = error instanceof Error ? error.message : 'Mapping SSO belum dapat dimuat.';
		} finally {
			ssoMappingLoading = false;
		}
	}

	function ssoCandidateIsMapped(employeeId: string): boolean {
		return ssoMappings.some((mapping) => mapping.employee_id === employeeId && mapping.active);
	}

	async function saveSsoMapping(event: SubmitEvent): Promise<void> {
		event.preventDefault();
		if (!ssoMappingExternalId.trim() || !ssoMappingTargetId || !ssoMappingConfirmed) {
			errorMessage = 'Isi ID pegawai, pilih user KPI yang sesuai, lalu konfirmasi kecocokan identitas.';
			return;
		}

		ssoMappingBusy = true;
		errorMessage = '';
		successMessage = '';
		try {
			const response = await api<Envelope<SsoIdentityMapping>>('/api/v1/sso-identities', {
				method: 'POST',
				body: JSON.stringify({
					external_employee_id: ssoMappingExternalId.trim(),
					employee_id: ssoMappingTargetId
				})
			});
			ssoMappings = [response.data, ...ssoMappings.filter((mapping) => mapping.id !== response.data.id)];
			ssoMappingExternalId = '';
			ssoMappingTargetId = '';
			ssoMappingConfirmed = false;
			successMessage = 'Mapping SSO tersimpan. Pegawai dapat mencoba login dengan akun kerja yang sama.';
		} catch (error) {
			errorMessage = error instanceof Error ? error.message : 'Mapping SSO belum dapat disimpan.';
		} finally {
			ssoMappingBusy = false;
		}
	}

	async function setSsoMappingActive(mapping: SsoIdentityMapping, active: boolean): Promise<void> {
		ssoMappingActionId = mapping.id;
		errorMessage = '';
		successMessage = '';
		try {
			const response = await api<Envelope<SsoIdentityMapping>>('/api/v1/sso-identities/' + encodeURIComponent(mapping.id), {
				method: 'PATCH',
				body: JSON.stringify({ active })
			});
			ssoMappings = ssoMappings.map((item) => item.id === response.data.id ? response.data : item);
			successMessage = active ? 'Mapping SSO diaktifkan kembali.' : 'Mapping SSO dinonaktifkan.';
		} catch (error) {
			errorMessage = error instanceof Error ? error.message : 'Status mapping SSO belum dapat diubah.';
		} finally {
			ssoMappingActionId = '';
		}
	}

	function selectedPosition(): DemoPositionMapping {
		return demoPositionMappings.find((position) => position.id === selectedPositionId) ?? demoPositionMappings[0]!;
	}

	function positionStatusLabel(status: DemoPositionStatus): string {
		return status === 'ACTIVE' ? 'Aktif' : 'Nonaktif';
	}

	function positionEditorTitle(): string {
		return positionEditorMode === 'CREATE' ? 'Tambah jabatan' : 'Edit jabatan';
	}

	function positionFormCatalogItems(): DemoCatalogItem[] {
		if (positionFormTrack === 'BOTH') return demoCatalogItems.filter((item) => item.state === 'ACTIVE');
		return activeCatalogItemsForTrack(positionFormTrack);
	}

	function positionFormSelectedItems(): DemoCatalogItem[] {
		return positionFormCatalogItems().filter((item) => positionFormSelectedIds.includes(item.id));
	}

	function positionFormWeightTotal(): number {
		return positionFormSelectedIds.reduce((total, id) => total + (Number(positionFormWeights[id]) || 0), 0);
	}

	function positionFormWeightStatus(): string {
		if (positionFormSelectedIds.length === 0) return 'Mapping kosong diperbolehkan untuk draft jabatan.';
		return Math.abs(positionFormWeightTotal() - 100) < 0.01 ? 'Total bobot valid 100%.' : 'Total bobot harus 100%.';
	}

	function resetPositionEditor(): void {
		positionEditorMode = 'CREATE';
		positionEditorId = '';
		positionFormName = '';
		positionFormLevel = 'Staf';
		positionFormOrgUnit = 'KCU Utama';
		positionFormScope = '';
		positionFormTrack = 'BUSINESS';
		positionFormStatus = 'ACTIVE';
		positionFormSelectedIds = [];
		positionFormTargets = {};
		positionFormWeights = {};
	}

	function openPositionCreate(): void {
		resetPositionEditor();
		positionEditorOpen = true;
		positionDeactivateId = '';
		errorMessage = '';
		successMessage = '';
	}

	function openPositionEdit(position: DemoPositionMapping): void {
		positionEditorMode = 'EDIT';
		positionEditorId = position.id;
		positionFormName = position.name;
		positionFormLevel = position.level;
		positionFormOrgUnit = position.orgUnitName;
		positionFormScope = position.scopeLabel;
		positionFormTrack = position.track;
		positionFormStatus = position.status;
		positionFormSelectedIds = [...position.mappingItemIds];
		positionFormTargets = { ...position.targets };
		positionFormWeights = { ...position.weights };
		positionEditorOpen = true;
		positionDeactivateId = '';
		errorMessage = '';
		successMessage = '';
	}

	function closePositionEditor(): void {
		positionEditorOpen = false;
		positionDeactivateId = '';
		errorMessage = '';
	}

	function changePositionFormTrack(track: DemoPositionTrack): void {
		positionFormTrack = track;
		const allowedIds = new Set(positionFormCatalogItems().map((item) => item.id));
		positionFormSelectedIds = positionFormSelectedIds.filter((id) => allowedIds.has(id));
	}

	function togglePositionFormItem(id: string, checked: boolean): void {
		positionFormSelectedIds = checked
			? [...new Set([...positionFormSelectedIds, id])]
			: positionFormSelectedIds.filter((selectedId) => selectedId !== id);
		if (checked) {
			const item = demoCatalogItems.find((catalogItem) => catalogItem.id === id);
			if (item) {
				positionFormTargets = { ...positionFormTargets, [id]: positionFormTargets[id] ?? String(item.target) };
				positionFormWeights = { ...positionFormWeights, [id]: positionFormWeights[id] ?? String(Math.round(item.weight * 10000) / 100) };
			}
		}
	}

	function updatePositionFormTarget(id: string, value: string): void {
		positionFormTargets = { ...positionFormTargets, [id]: value };
	}

	function updatePositionFormWeight(id: string, value: string): void {
		positionFormWeights = { ...positionFormWeights, [id]: value };
	}

	function savePositionDemo(): void {
		errorMessage = '';
		successMessage = '';
		const name = positionFormName.trim();
		const level = positionFormLevel.trim();
		const orgUnitName = positionFormOrgUnit.trim();
		const scopeLabel = positionFormScope.trim();
		if (!name || !level || !orgUnitName || !scopeLabel) {
			errorMessage = 'Nama jabatan, level, organisasi, dan cakupan wajib diisi.';
			return;
		}
		if (demoPositionMappings.some((position) => position.id !== positionEditorId && position.name.toLowerCase() === name.toLowerCase())) {
			errorMessage = 'Nama jabatan sudah ada di master demo.';
			return;
		}
		const allowedIds = new Set(positionFormCatalogItems().map((item) => item.id));
		if (positionFormSelectedIds.some((id) => !allowedIds.has(id))) {
			errorMessage = 'Ada indikator yang tidak sesuai dengan jalur jabatan.';
			return;
		}
		for (const item of positionFormCatalogItems().filter((item) => positionFormSelectedIds.includes(item.id))) {
			const target = Number(positionFormTargets[item.id]);
			const weight = Number(positionFormWeights[item.id]);
			if (!Number.isFinite(target) || target <= 0 || !Number.isFinite(weight) || weight <= 0) {
				errorMessage = 'Target dan bobot setiap indikator yang dipilih harus lebih besar dari 0.';
				return;
			}
		}
		if (positionFormSelectedIds.length > 0 && Math.abs(positionFormWeightTotal() - 100) >= 0.01) {
			errorMessage = 'Total bobot mapping harus tepat 100%.';
			return;
		}
		const mappingItemIds = [...positionFormSelectedIds];
		const note = mappingItemIds.length > 0 ? 'Mapping demo disimpan dari editor Admin KPI.' : 'Belum ada mapping KPI Aktif.';
		if (positionEditorMode === 'EDIT') {
			demoPositionMappings = demoPositionMappings.map((position) =>
				position.id === positionEditorId
					? { ...position, name, level, orgUnitName, scopeLabel, track: positionFormTrack, status: positionFormStatus, mappingItemIds, targets: { ...positionFormTargets }, weights: { ...positionFormWeights }, note }
					: position
			);
			successMessage = `${name} diperbarui di master jabatan demo.`;
		} else {
			const id = `POS-CUSTOM-${Date.now()}`;
			demoPositionMappings = [
				...demoPositionMappings,
				{ id, name, level, orgUnitName, scopeLabel, track: positionFormTrack, status: positionFormStatus, mappingItemIds, targets: { ...positionFormTargets }, weights: { ...positionFormWeights }, note }
			];
			selectedPositionId = id;
			successMessage = `${name} ditambahkan ke master jabatan demo.`;
		}
		catalogLoaded = false;
		positionEditorOpen = false;
	}

	function requestPositionDeactivate(position: DemoPositionMapping): void {
		positionDeactivateId = position.id;
		positionEditorOpen = false;
		errorMessage = '';
		successMessage = '';
	}

	function cancelPositionDeactivation(): void {
		positionDeactivateId = '';
	}

	function confirmPositionDeactivation(): void {
		const position = demoPositionMappings.find((item) => item.id === positionDeactivateId);
		if (!position) return;
		demoPositionMappings = demoPositionMappings.map((item) => item.id === position.id ? { ...item, status: 'INACTIVE' } : item);
		catalogLoaded = false;
		positionDeactivateId = '';
		successMessage = `${position.name} dinonaktifkan pada state demo.`;
		errorMessage = '';
	}

	function activatePositionDemo(position: DemoPositionMapping): void {
		demoPositionMappings = demoPositionMappings.map((item) => item.id === position.id ? { ...item, status: 'ACTIVE' } : item);
		catalogLoaded = false;
		successMessage = `${position.name} diaktifkan kembali pada state demo.`;
		errorMessage = '';
	}

	function positionTrackLabel(track: DemoPositionTrack): string {
		if (track === 'BOTH') return 'Pemasaran + Operasional';
		return catalogTrackLabel(track);
	}

	function positionMappingItems(position: DemoPositionMapping): DemoCatalogItem[] {
		return position.mappingItemIds
			.map((id) => demoCatalogItems.find((item) => item.id === id))
			.filter((item): item is DemoCatalogItem => Boolean(item && item.state === 'ACTIVE'));
	}

	function positionMappingTotal(position: DemoPositionMapping): number {
		return positionMappingItems(position).reduce((total, item) => total + Number(position.weights[item.id] ?? item.weight * 100), 0);
	}

	function positionMappingStatus(position: DemoPositionMapping): 'MAPPED' | 'EMPTY' {
		return positionMappingItems(position).length > 0 ? 'MAPPED' : 'EMPTY';
	}

	function demoDefinitionFromPositionMapping(item: DemoCatalogItem, position: DemoPositionMapping): KpiDefinition {
		return {
			id: item.id,
			code: item.code,
			name: item.name,
			group: item.group,
			type: item.type,
			definition: `Mapping aktif dari jabatan ${position.name}.`,
			unit: item.unit,
			default_weight: Number(position.weights[item.id] ?? item.weight * 100) / 100,
			sample_target: Number(position.targets[item.id] ?? item.target),
			target_note: 'Target inherited dari mapping jabatan Aktif.',
			data_source: item.dataSource,
			quality_flag: false,
			draft: false,
			demo_only: true
		};
	}

	function openMine(): void {
		setView('mine');
		if (!canViewStaffHistory()) return;
		staffWorkspaceTab = 'KPI';
		assessmentStep = 1;
		selectedContract = null;
		errorMessage = '';
	}

	async function selectStaffWorkspaceTab(tab: StaffWorkspaceTab): Promise<void> {
		const previousTab = staffWorkspaceTab;
		if (
			tab === 'APPROVAL' &&
			selectedContract &&
			isBranchSelfAssessment() &&
			behaviorRatingsCompletedCount() === behaviorCriteria.length &&
			behaviorDraftDirty() &&
			!(await saveBehaviorEvaluation())
		) {
			staffWorkspaceTab = 'BEHAVIOR';
			return;
		}
		staffWorkspaceTab = tab;
		assessmentStep = tab === 'KPI' ? 1 : tab === 'BEHAVIOR' ? 2 : 3;
		if (tab !== previousTab) {
			const menuKeys: Record<StaffWorkspaceTab, AuditMenuKey> = {
				KPI: 'mine-kpi',
				BEHAVIOR: 'mine-behavior',
				APPROVAL: 'mine-approval',
				HISTORY: 'mine-history'
			};
			void recordMenuAccess(menuKeys[tab], menuKeys[previousTab]);
		}
	}

	function historyTrackLabel(track: StaffTrackKind): string {
		return track === 'BUSINESS' ? 'Pemasaran / Bisnis' : 'Operasional / Non-bisnis';
	}

	function staffDashboardContract(): PerformanceContract | null {
		const cycle = staffDashboardCycle();
		return cycle ? existingContractForCycle(cycle) : null;
	}

	function staffDashboardCycle(): Cycle | null {
		return openInputCycles()[0] ?? null;
	}

	function staffDashboardPeriodLabel(): string {
		return staffDashboardCycle()?.name ?? 'Tidak ada periode terbuka';
	}

	function staffDashboardProgress(): { completed: number; total: number; percent: number } {
		const contract = staffDashboardContract();
		const total = contract?.items.length ?? 0;
		const completed = contract?.items.filter((item) => ['VALID', 'NOT_APPLICABLE'].includes(item.state)).length ?? 0;
		return { completed, total, percent: total > 0 ? Math.round((completed / total) * 100) : 0 };
	}

	function staffDashboardAssignmentLabel(): string {
		const contract = staffDashboardContract();
		if (!contract) return staffDashboardCycle() ? 'Wajib diisi' : 'Tidak ada periode terbuka';
		return statusNames[contract.status] ?? contract.status;
	}

	function staffDashboardAssignmentDetail(): string {
		const contract = staffDashboardContract();
		if (!contract) {
			const cycle = staffDashboardCycle();
			return cycle ? `Admin membuka ${cycle.name}; KPI mengikuti jabatan aktif Anda.` : 'Periode pengisian akan tampil setelah Admin membukanya.';
		}
		if (contract.status === 'REVISION') return 'Ada catatan reviewer yang perlu diselesaikan.';
		if (contract.status === 'SUBMITTED') return `Penilaian menunggu ${contract.review_workflow?.current_slot ?? 'penilai'}.`;
		return 'Draf tersimpan; lengkapi indikator sebelum diajukan.';
	}

	function staffDashboardTaskLabel(): string {
		const contract = staffDashboardContract();
		if (!contract) return staffDashboardCycle() ? 'Isi KPI yang dibuka' : 'Belum ada tugas input';
		if (contract.status === 'REVISION') return 'Perbaiki KPI yang dikembalikan';
		if (contract.status === 'DRAFT') return 'Lengkapi target dan bukti';
		if (contract.status === 'SUBMITTED') return 'Menunggu keputusan reviewer';
		return 'Tidak ada tugas input';
	}

	function staffDashboardTaskDetail(): string {
		const contract = staffDashboardContract();
		if (!contract) return staffDashboardCycle() ? 'Buka periode dari daftar untuk mulai mengisi KPI jabatan.' : 'Periode yang dibuka Admin akan muncul di daftar KPI Anda.';
		if (contract.status === 'REVISION') return 'Buka KPI saya untuk membaca catatan dan mengirim perbaikan.';
		if (contract.status === 'DRAFT') return 'Isi realisasi dan bukti pada KPI jabatan yang sudah terpasang.';
		if (contract.status === 'SUBMITTED') return 'Tidak ada input yang perlu dilakukan sampai reviewer mengirim keputusan.';
		return 'Penilaian ini tidak lagi memerlukan input.';
	}

	function staffDashboardRealizationSummary(): string {
		const contract = staffDashboardContract();
		if (!contract || contract.items.length === 0) return 'Belum tersedia';
		const completed = contract.items.filter((item) => item.actual !== null).length;
		return `${completed}/${contract.items.length} indikator terisi`;
	}

	function staffDashboardLatestHistory(): AssessmentHistoryEntry | null {
		return visibleHistoryEntries()[0] ?? null;
	}

	function isBranchSelfAssessment(): boolean {
		return Boolean(
			canViewStaffHistory() &&
			selectedContract &&
			selectedContract.subject_id === currentUser?.employee_id &&
			['DRAFT', 'REVISION'].includes(selectedContract.status)
		);
	}

	function assessmentFieldValue(item: ContractItem, field: 'actual' | 'evidence'): string {
		const edit = edits[item.id];
		if (field === 'actual') {
			return edit ? draftText(edit.actual) : item.actual === null ? '' : String(item.actual);
		}
		return edit?.evidence ?? item.evidence[0]?.reference ?? '';
	}

	function draftText(value: NumericDraft): string {
		return value === null || value === undefined ? '' : String(value);
	}

	function assessmentMissingCounts(contract: PerformanceContract): { actual: number; evidence: number; unsaved: number; behavior: number } {
		let actual = 0;
		let evidence = 0;
		let unsaved = 0;
		for (const item of contract.items) {
			if (!assessmentFieldValue(item, 'actual').trim()) actual += 1;
			if (!assessmentFieldValue(item, 'evidence').trim()) evidence += 1;
			if (itemDirty(item)) unsaved += 1;
		}
		const behavior = behaviorCriteria.filter((criterion) => !behaviorDraft[criterion.key]).length;
		return { actual, evidence, unsaved, behavior };
	}

	function behaviorScorePreview(): number | null {
		const ratings = behaviorCriteria.map(({ key }) => {
			const rating = Number(behaviorDraft[key]);
			return Number.isInteger(rating) && rating >= 1 && rating <= 5 ? rating : 0;
		});
		if (ratings.length === 0) return null;
		if (ratings.every((rating) => rating === 0)) return null;
		return Number(((ratings.reduce((total, rating) => total + rating, 0) / 25) * 100).toFixed(2));
	}

	function behaviorRatingsCompletedCount(): number {
		return behaviorCriteria.filter(({ key }) => {
			const rating = Number(behaviorDraft[key]);
			return Number.isInteger(rating) && rating >= 1 && rating <= 5;
		}).length;
	}

	function calculateItemScore(type: string, target: number, actual: number): number | null {
		if (!Number.isFinite(target) || !Number.isFinite(actual)) return null;
		if (type === 'Z') {
			if (target !== 0 || actual < 0 || !Number.isInteger(actual)) return null;
			return actual === 0 ? 100 : 0;
		}
		if (!['A', 'Q', 'L'].includes(type) || target <= 0) return null;
		if ((type === 'Q' || type === 'L') && (actual < 0 || actual > 100 || target > 100)) return null;
		const raw = type === 'L' ? 100 * (2 - actual / target) : (100 * actual) / target;
		return Number(Math.max(0, Math.min(100, raw)).toFixed(3));
	}

	function kpiScorePreview(contract: PerformanceContract | null = selectedContract): {
		score: number | null;
		validWeight: number;
		completedItems: number;
		scoredItems: number;
		pendingItems: number;
		weightsValid: boolean;
		canCalculateFinal: boolean;
	} | null {
		if (!contract) return null;
		let totalWeight = 0;
		let validWeight = 0;
		let weightedScore = 0;
		let completedItems = 0;
		let scoredItems = 0;
		let pendingItems = 0;

		for (const item of contract.items) {
			totalWeight += item.weight;
			if (item.state === 'NOT_APPLICABLE' && item.reason?.trim()) {
				completedItems += 1;
				continue;
			}

			const actualText = assessmentFieldValue(item, 'actual').trim();
			const evidence = assessmentFieldValue(item, 'evidence').trim();
			const actual = actualText === '' ? Number.NaN : Number(actualText);
			const score = Number.isFinite(actual) ? calculateItemScore(item.score_type, item.target, actual) : null;
			if (score === null) {
				pendingItems += 1;
				continue;
			}
			scoredItems += 1;
			weightedScore += score * item.weight;
			if (!evidence) {
				pendingItems += 1;
				continue;
			}
			completedItems += 1;
			validWeight += item.weight;
		}

		const weightsValid = Math.abs(totalWeight - 1) < 0.000001;
		const score = weightsValid && scoredItems > 0
			? Number((pendingItems === 0 ? weightedScore / validWeight : weightedScore).toFixed(3))
			: null;
		return {
			score,
			validWeight,
			completedItems,
			scoredItems,
			pendingItems,
			weightsValid,
			canCalculateFinal: weightsValid && pendingItems === 0 && validWeight >= 0.7 && score !== null
		};
	}

	function itemScorePreview(item: ContractItem): number | null {
		const actualText = assessmentFieldValue(item, 'actual').trim();
		if (actualText === '') return item.score;
		return calculateItemScore(item.score_type, item.target, Number(actualText));
	}

	function weightedItemScorePreview(item: ContractItem): number | null {
		const score = itemScorePreview(item);
		return score === null ? null : Number((score * item.weight).toFixed(3));
	}

	function itemDraftComplete(item: ContractItem): boolean {
		if (item.state === 'NOT_APPLICABLE') return Boolean(item.reason?.trim());
		return Boolean(
			assessmentFieldValue(item, 'actual').trim() &&
			assessmentFieldValue(item, 'evidence').trim() &&
			itemScorePreview(item) !== null
		);
	}

	function isItemScorePreview(item: ContractItem): boolean {
		const edit = edits[item.id];
		return Boolean(edit && (
			draftText(edit.actual).trim() !== (item.actual === null ? '' : String(item.actual)) ||
			edit.evidence !== (item.evidence[0]?.reference ?? '')
		));
	}

	function kpiPreviewMessage(contract: PerformanceContract): string {
		const preview = kpiScorePreview(contract);
		if (!preview) return 'Skor KPI belum tersedia.';
		if (!preview.weightsValid) return 'Total bobot penilaian harus 100%.';
		if (preview.pendingItems > 0) {
			return preview.scoredItems > 0
				? `Pratinjau menjumlahkan ${preview.scoredItems} realisasi; ${preview.pendingItems} KPI masih belum lengkap.`
				: `${preview.pendingItems} KPI belum lengkap. Skor muncul setelah realisasi diisi.`;
		}
		if (preview.validWeight < 0.7) return 'Bobot data valid kurang dari 70%.';
		return 'Nilai akhir memakai bobot KPI 80% dan perilaku 20%.';
	}

	function summaryBehaviorScore(contract: PerformanceContract | null = selectedContract): number | null {
		return behaviorScorePreview() ?? contract?.score?.behavior_score ?? null;
	}

	function isFinalScoreReady(contract: PerformanceContract | null = selectedContract): boolean {
		return Boolean(
			contract &&
			kpiScorePreview(contract)?.canCalculateFinal &&
			behaviorRatingsCompletedCount() === behaviorCriteria.length
		);
	}

	function summaryFinalScore(contract: PerformanceContract | null = selectedContract): number | null {
		if (!contract) return null;
		const kpiPreview = kpiScorePreview(contract);
		const behaviorScore = summaryBehaviorScore(contract);
		if (!kpiPreview?.weightsValid || (kpiPreview.pendingItems === 0 && kpiPreview.validWeight < 0.7)) return null;
		const hasBehaviorInput = behaviorRatingsCompletedCount() > 0;
		if (kpiPreview.score === null && !hasBehaviorInput) return null;
		return Number(((kpiPreview.score ?? 0) * 0.8 + (behaviorScore ?? 0) * 0.2).toFixed(2));
	}

	function scoreContribution(score: number | null, weight: number): number | null {
		return score === null ? null : Number((score * weight).toFixed(2));
	}

	function assessmentStepHint(): string {
		const cycle = cycles.find((entry) => entry.id === selectedContract?.cycle_id) ?? activeMonthlyCycle();
		const measurementRange = cycleMeasurementRange(cycle);
		if (assessmentStep === 1) return `Isi realisasi total untuk periode data ${measurementRange || 'sebelum siklus penilaian'}, lalu cantumkan referensi bukti untuk setiap KPI.`;
		if (assessmentStep === 2) return 'Beri nilai pada lima indikator perilaku yang sama untuk semua jabatan. Nilai 3 berarti memenuhi standar.';
		return 'Periksa capaian, nilai perilaku, dan urutan penilai sebelum mengirim.';
	}

	function reviewerSlotStatus(contract: Pick<PerformanceContract, 'status' | 'review_workflow' | 'review_history'>, slot: 'P1' | 'P2'): string {
		const workflow = contract.review_workflow;
		const reviewer = workflow?.reviewer_slots?.[slot];
		if (!reviewer || reviewer.state === 'SKIPPED') return 'Dilewati karena jabatan kosong';
		if (contract.status === 'SUBMITTED' && workflow?.current_slot === slot) return 'Menunggu penilaian';
		if (contract.status === 'SUBMITTED' && workflow?.active_slots.includes(slot)) return 'Menunggu giliran';
		const latestEvent = [...(contract.review_history ?? [])].reverse().find((event) => event.slot === slot);
		if (latestEvent?.decision === 'VERIFIED') return 'Selesai';
		if (latestEvent?.decision === 'REVISION') return 'Dikembalikan untuk revisi';
		if (contract.status === 'FINAL' && workflow?.active_slots.includes(slot)) return 'Selesai';
		return 'Belum dimulai';
	}

	function reviewerDecisionLabel(decision: string): string {
		if (decision === 'VERIFIED') return 'Disetujui';
		if (decision === 'REVISION') return 'Dikembalikan';
		if (decision === 'FINAL') return 'Difinalisasi';
		return decision;
	}

	function reviewerVerifyLabel(contract: PerformanceContract): string {
		const activeSlots = contract.review_workflow?.active_slots ?? [];
		const currentSlot = contract.review_workflow?.current_slot;
		const index = currentSlot ? activeSlots.indexOf(currentSlot) : -1;
		return index >= 0 && index === activeSlots.length - 1
			? 'Verifikasi dan selesaikan'
			: 'Verifikasi dan teruskan';
	}

	function assessmentNextBlockedReason(contract: PerformanceContract): string | null {
		const counts = assessmentMissingCounts(contract);
		if (assessmentStep === 1 && counts.actual > 0) return `${counts.actual} KPI belum memiliki realisasi.`;
		if (assessmentStep === 1 && counts.evidence > 0) return `${counts.evidence} KPI belum memiliki referensi bukti.`;
		if (assessmentStep === 1 && counts.unsaved > 0) return 'Simpan perubahan KPI sebelum lanjut.';
		if (assessmentStep === 2 && counts.behavior > 0) return `${counts.behavior} indikator perilaku belum dinilai.`;
		return null;
	}

	function assessmentSubmitBlockedReason(contract: PerformanceContract): string | null {
		const counts = assessmentMissingCounts(contract);
		if (counts.actual > 0) return `${counts.actual} indikator belum memiliki realisasi.`;
		if (counts.evidence > 0) return `${counts.evidence} indikator belum memiliki bukti.`;
		if (counts.unsaved > 0) return 'Simpan perubahan indikator sebelum mengajukan.';
		if (counts.behavior > 0) return `${counts.behavior} indikator perilaku belum dinilai.`;
		return submitBlockedReason(contract);
	}

	async function moveAssessmentStep(next: SelfAssessmentStep): Promise<void> {
		if (!selectedContract || !isBranchSelfAssessment()) return;
		if (next > assessmentStep) {
			const reason = assessmentNextBlockedReason(selectedContract);
			if (reason) {
				errorMessage = reason;
				return;
			}
		}
		if (assessmentStep === 2 && next === 3 && !(await saveBehaviorEvaluation())) return;
		errorMessage = '';
		assessmentStep = next;
	}

	function activeMonthlyCycle(): Cycle | null {
		return cycles.find((cycle) => cycle.status === 'OPEN') ?? null;
	}

	function monthlyCycleDateRange(): string {
		const cycle = activeMonthlyCycle();
		return cycle ? `${formatDate(cycle.starts_at)} s.d. ${formatDate(cycle.ends_at)}` : '';
	}

	function existingContractForCycle(cycle: Cycle): PerformanceContract | null {
		return (
			contracts.find(
				(contract) =>
					contract.cycle_id === cycle.id && contract.subject_id === currentUser?.employee_id
				) ?? null
		);
	}

	function openInputCycles(): Cycle[] {
		return cycles
			.filter((cycle) => cycle.status === 'OPEN')
			.filter((cycle) => {
				const contract = existingContractForCycle(cycle);
				return !contract || ['DRAFT', 'REVISION'].includes(contract.status);
			})
			.sort((left, right) => left.ends_at.localeCompare(right.ends_at));
	}

	function submitBlockedReason(contract: PerformanceContract): string | null {
		if (contract.items.length < 1) return 'Minimal satu KPI sesuai jabatan harus tersedia.';
		if (contract.items.some((item) => !['VALID', 'NOT_APPLICABLE'].includes(item.state))) {
			return 'Lengkapi target, realisasi, dan bukti setiap indikator sebelum pengajuan.';
		}
		if (!contract.behavior_evaluation || Object.keys(contract.behavior_evaluation.ratings ?? {}).length !== 5) {
			return 'Nilai lima indikator perilaku sebelum pengajuan.';
		}
		return null;
	}

	function statusVariant(status: string): 'default' | 'secondary' | 'destructive' | 'outline' {
		if (status === 'SUBMITTED') return 'default';
		if (status === 'REVISION') return 'destructive';
		if (status === 'VERIFIED' || status === 'FINAL') return 'secondary';
		return 'outline';
	}

	function formatNumber(value: number | null, digits = 0): string {
		if (value === null || !Number.isFinite(value)) return 'Belum dihitung';
		return new Intl.NumberFormat('id-ID', {
			minimumFractionDigits: 0,
			maximumFractionDigits: digits
		}).format(value);
	}

	function formatDate(value: string): string {
		return new Intl.DateTimeFormat('id-ID', {
			day: 'numeric',
			month: 'long',
			year: 'numeric'
		}).format(new Date(value));
	}

	function formatDateTime(value: string | null): string {
		if (!value) return 'Waktu tidak tersedia';
		return new Intl.DateTimeFormat('id-ID', {
			day: 'numeric',
			month: 'short',
			year: 'numeric',
			hour: '2-digit',
			minute: '2-digit'
		}).format(new Date(value));
	}

	function auditActionLabel(action: string): string {
		return auditActionNames[action] ?? action.replaceAll('_', ' ').toLocaleLowerCase('id-ID');
	}

	function auditChangeLabel(event: AuditEvent): string | null {
		const changes: string[] = [];
		const before = event.before;
		const after = event.after;
		if (!before || !after) return null;

		if (typeof before.status === 'string' && typeof after.status === 'string') {
			changes.push(
				'Status ' +
					(statusNames[before.status] ?? before.status) +
					' → ' +
					(statusNames[after.status] ?? after.status)
			);
		}

		const item = selectedContract?.items.find((contractItem) => contractItem.id === event.object_id);
		if (typeof before.actual === 'number' && typeof after.actual === 'number' && before.actual !== after.actual) {
			const unit = item?.unit ? ' ' + item.unit : '';
			changes.push('Realisasi ' + formatNumber(before.actual, 2) + unit + ' → ' + formatNumber(after.actual, 2) + unit);
		}
		if (typeof before.target === 'number' && typeof after.target === 'number') {
			const unit = item?.unit ? ' ' + item.unit : '';
			changes.push(
				'Target ' +
					formatNumber(before.target, 2) +
					unit +
					' → ' +
					formatNumber(after.target, 2) +
					unit
			);
		}
		if (typeof before.weight === 'number' && typeof after.weight === 'number' && before.weight !== after.weight) {
			changes.push(
				'Bobot ' +
					formatNumber(before.weight * 100, 1) +
					'% → ' +
					formatNumber(after.weight * 100, 1) +
					'%'
			);
		}
		const beforeRatings = before.ratings as Record<string, unknown> | undefined;
		const afterRatings = after.ratings as Record<string, unknown> | undefined;
		if (beforeRatings && afterRatings) {
			for (const criterion of behaviorCriteria) {
				if (beforeRatings[criterion.key] !== afterRatings[criterion.key]) {
					changes.push(`${criterion.label} ${String(beforeRatings[criterion.key] ?? '—')} → ${String(afterRatings[criterion.key] ?? '—')}`);
				}
			}
		}

		return changes.length > 0 ? changes.join(' · ') : null;
	}

	function initials(value: string): string {
		return value
			.split(/\s+/)
			.slice(0, 2)
			.map((part) => part[0] ?? '')
			.join('')
			.toLocaleUpperCase('id-ID');
	}

	async function initialize(): Promise<void> {
		loading = true;
		errorMessage = '';
		try {
			const response = await api<Envelope<CurrentUser>>('/api/v1/me');
			currentUser = response.data;
			await refreshWorkspace();
			await openSelfServiceHome();
		} catch (error) {
			currentUser = null;
			if (error instanceof ApiError && error.status !== 401) errorMessage = error.message;
			await loadDemoIdentities();
		} finally {
			loading = false;
		}
	}

	async function refreshWorkspace(): Promise<void> {
		if (!currentUser) return;
		const cyclesResponse = await api<Envelope<Cycle[]>>('/api/v1/cycles');
		cycles = cyclesResponse.data;
		configuredPeriods = cycles.map(cycleToPeriod);
		cycleStatusDrafts = Object.fromEntries(configuredPeriods.map((period) => [period.id, period.status]));
		const activeCycle = cycles.find((cycle) => cycle.status === 'OPEN');
		const cycleQuery = activeCycle ? '?cycle_id=' + encodeURIComponent(activeCycle.id) : '';
		const filterContractsToCycle = Boolean(activeCycle && hasAdminAccess() && !canViewStaffHistory());
		const contractQuery = (filterContractsToCycle ? cycleQuery + '&' : '?') + 'per_page=100';
		const [dashboardResponse, firstContractPage] = await Promise.all([
			activeCycle ? api<Envelope<Dashboard>>('/api/v1/dashboard' + cycleQuery) : Promise.resolve(null),
			api<{ data: PerformanceContract[]; meta: Record<string, number> }>(
				'/api/v1/contracts' + contractQuery
			)
		]);
		dashboard = dashboardResponse?.data ?? null;
		const allContracts = [...firstContractPage.data];
		const pageSize = firstContractPage.meta.per_page || 100;
		const total = firstContractPage.meta.total || allContracts.length;
		const pageCount = Math.ceil(total / pageSize);
		for (let page = 2; page <= pageCount; page += 1) {
			const pageResponse = await api<{ data: PerformanceContract[]; meta: Record<string, number> }>(
				'/api/v1/contracts' + contractQuery + '&page=' + page
			);
			allContracts.push(...pageResponse.data);
		}
		contracts = allContracts;

		if (selectedContract) {
			const stillVisible = contracts.find((contract) => contract.id === selectedContract?.id);
			if (stillVisible) await openContract(stillVisible.id);
			else selectedContract = null;
		}
	}

	async function openSelfAssignment(cycleId?: string): Promise<void> {
		if (!canSelfAssignRole()) return;
		errorMessage = '';
		if (!cycleId) {
			setView('assignment');
			return;
		}

		const cycle = cycles.find((item) => item.id === cycleId && item.status === 'OPEN');
		if (!cycle) {
			setView('assignment');
			errorMessage = 'Periode ini tidak sedang dibuka untuk pengisian.';
			return;
		}

		const existing = existingContractForCycle(cycle);
		if (existing) {
			if (!['DRAFT', 'REVISION'].includes(existing.status)) {
				setView('mine');
				errorMessage = 'Periode ini sudah diajukan atau diselesaikan. Lihat statusnya di Riwayat saya.';
				return;
			}
			setView('mine');
			staffWorkspaceTab = 'KPI';
			await openContract(existing.id);
			return;
		}
		await createSelfAssignment(cycle.id);
	}

	async function openSelfServiceHome(): Promise<void> {
		if (canSelfAssignRole() && !hasAdminAccess()) {
			await openMine();
			return;
		}
		setView('overview', true);
	}

	function toggleDefinition(id: string, checked: boolean): void {
		selectedDefinitionIds = checked
			? [...new Set([...selectedDefinitionIds, id])]
			: selectedDefinitionIds.filter((selectedId) => selectedId !== id);
	}

	function updateTargetDraft(id: string, value: string): void {
		targetDrafts = { ...targetDrafts, [id]: value };
	}

	async function createSelfAssignment(cycleId: string): Promise<void> {
		const cycle = cycles.find((item) => item.id === cycleId && item.status === 'OPEN');
		if (!cycle) {
			errorMessage = 'Periode ini tidak sedang dibuka untuk pengisian.';
			return;
		}
		const existing = existingContractForCycle(cycle);
		if (existing) {
			if (['DRAFT', 'REVISION'].includes(existing.status)) {
				setView('mine');
				staffWorkspaceTab = 'KPI';
				await openContract(existing.id);
			} else {
				await openMine();
				errorMessage = 'Periode ini sudah diajukan atau diselesaikan. Lihat statusnya di Riwayat saya.';
			}
			return;
		}

		busy = true;
		errorMessage = '';
		successMessage = '';
		try {
			const response = await api<Envelope<PerformanceContract>>('/api/v1/contracts', {
				method: 'POST',
				body: JSON.stringify({ cycle_id: cycle.id })
			});
			await refreshWorkspace();
			setView('mine');
			await openContract(response.data.id);
			successMessage = 'KPI sesuai jabatan otomatis disiapkan untuk periode ini.';
		} catch (error) {
			errorMessage = error instanceof Error ? error.message : 'Penilaian belum dapat disiapkan.';
		} finally {
			busy = false;
		}
	}

	async function login(): Promise<void> {
		if (!selectedIdentityId) {
		errorMessage = 'Pilih profil pegawai terlebih dahulu.';
			return;
		}
		busy = true;
		errorMessage = '';
		successMessage = '';
		try {
			const response = await api<Envelope<CurrentUser>>('/api/v1/demo/session', {
				method: 'POST',
				body: JSON.stringify({ identity_id: selectedIdentityId })
			});
			currentUser = response.data;
			await refreshWorkspace();
			await openSelfServiceHome();
		} catch (error) {
			errorMessage = error instanceof Error ? error.message : 'Login belum berhasil.';
		} finally {
			busy = false;
		}
	}

	async function loginWithSso(): Promise<void> {
		if (!ssoEmployeeId.trim() || !ssoPassword) {
			errorMessage = 'Isi ID pegawai dan password akun kerja Anda.';
			return;
		}
		busy = true;
		errorMessage = '';
		successMessage = '';
		try {
			const response = await api<Envelope<CurrentUser>>('/api/v1/auth/sso/login', {
				method: 'POST',
				body: JSON.stringify({ id_peg: ssoEmployeeId.trim(), password: ssoPassword })
			});
			currentUser = response.data;
			ssoPassword = '';
			await refreshWorkspace();
			await openSelfServiceHome();
		} catch (error) {
			errorMessage = error instanceof Error ? error.message : 'Login SSO belum berhasil.';
		} finally {
			busy = false;
		}
	}

	async function logout(): Promise<void> {
		busy = true;
		errorMessage = '';
		try {
			await api('/api/v1/demo/session', { method: 'DELETE' });
			currentUser = null;
			ssoEmployeeId = '';
			ssoPassword = '';
			dashboard = null;
			kpiDefinitions = [];
			selectedDefinitionIds = [];
			targetDrafts = {};
			catalogLoaded = false;
			catalogLoading = false;
			contracts = [];
			selectedContract = null;
			assessmentStep = 1;
			selectedHistoryId = '';
			reviewTrackFilter = 'ALL';
			reviewStatusFilter = 'ALL';
			reviewAgeFilter = 'ALL';
			selectedReviewTaskId = '';
			reviewDecisionNote = '';
			monitoringTrackFilter = 'ALL';
			monitoringStatusFilter = 'ALL';
			monitoringDrilldownFocus = null;
			adminPeriodFilter = 'DEMO-PERIOD-002';
			auditTrackFilter = 'ALL';
			auditStatusFilter = 'ALL';
			selectedAuditTrailId = '';
			auditTrailFocus = null;
			comparisonPeriodA = 'DEMO-PERIOD-001';
			comparisonPeriodB = 'DEMO-PERIOD-002';
			catalogTrackFilter = 'ALL';
			catalogStateFilter = 'ALL';
			resetCatalogForm();
			assignmentTrack = 'BUSINESS';
			assignmentSelectedIds = [];
			assignmentTargetDrafts = {};
			assignmentWeightDrafts = {};
			assignmentSaveState = 'DRAFT';
			staffAssignmentStatus = 'NONE';
			view = 'overview';
			auditEvents = [];
			auditLoadFailed = false;
			await initialize();
		} catch (error) {
			errorMessage = error instanceof Error ? error.message : 'Logout belum berhasil.';
		} finally {
			busy = false;
		}
	}

	function setEdits(contract: PerformanceContract): void {
		const next: Record<string, EditItem> = {};
		for (const item of contract.items) {
			next[item.id] = {
				actual: item.actual === null ? '' : String(item.actual),
				evidence: item.evidence[0]?.reference ?? '',
				note: item.note ?? '',
				target: String(item.target),
				weightPercent: String(Math.round(item.weight * 10000) / 100),
				reason: ''
			};
		}
		edits = next;
	}

	async function openContract(id: string): Promise<void> {
		const sameContract = selectedContract?.id === id;
		errorMessage = '';
		auditEvents = [];
		auditLoadFailed = false;
		try {
			const response = await api<Envelope<PerformanceContract>>('/api/v1/contracts/' + id);
			selectedContract = response.data;
			setEdits(response.data);
			if (!sameContract) assessmentStep = 1;
			commentDraft = '';
			reviewComment = '';
			behaviorRevisionComment = '';

			try {
				const history = await api<{ data: AuditEvent[] }>(
					'/api/v1/audit-events?contract_id=' + encodeURIComponent(id) + '&per_page=100'
				);
				auditEvents = history.data;
			} catch {
				auditLoadFailed = true;
			}
		behaviorDraft = Object.fromEntries(
			behaviorCriteria.map(({ key }) => [key, String(response.data.behavior_evaluation?.ratings?.[key] ?? '')])
			) as Partial<Record<BehaviorKey, string>>;
		} catch (error) {
			errorMessage = error instanceof Error ? error.message : 'Detail kontrak belum dapat dimuat.';
		}
	}

	async function refreshSelectedContract(): Promise<void> {
		const contractId = selectedContract?.id;
		if (!contractId) return;
		await openContract(contractId);
		if (errorMessage || selectedContract?.id !== contractId) {
			throw new Error(errorMessage || 'Nilai terbaru belum dapat dimuat.');
		}
		const refreshedContract = selectedContract;
		contracts = contracts.map((contract) => contract.id === contractId ? refreshedContract : contract);
	}

	function closeSelectedContract(): void {
		selectedContract = null;
		if (view === 'mine') {
			staffWorkspaceTab = 'KPI';
			assessmentStep = 1;
		}
	}

	function canEditActual(contract: PerformanceContract): boolean {
		return Boolean(
			['DRAFT', 'REVISION'].includes(contract.status) &&
				currentUser &&
				(contract.subject_id === currentUser.employee_id || hasAccessRole('SDM'))
		);
	}

	function canReviewSubmitted(contract: PerformanceContract): boolean {
		return Boolean(
			contract.status === 'SUBMITTED' &&
			currentUser &&
			contract.reviewer_employee_id === currentUser.employee_id
		);
	}

	function canEditTarget(contract: PerformanceContract): boolean {
		return Boolean(
			['DRAFT', 'REVISION'].includes(contract.status) &&
				currentUser &&
				(contract.reviewer_employee_id === currentUser.employee_id || hasAccessRole('SDM'))
		);
	}

	function itemDirty(item: ContractItem): boolean {
		const edit = edits[item.id];
		if (!edit) return false;
		return (
			draftText(edit.actual) !== (item.actual === null ? '' : String(item.actual)) ||
			edit.evidence !== (item.evidence[0]?.reference ?? '') ||
			edit.note !== (item.note ?? '') ||
			Number(draftText(edit.target)) !== item.target ||
			Number(draftText(edit.weightPercent)) / 100 !== item.weight
		);
	}

	function itemActualDirty(item: ContractItem): boolean {
		const edit = edits[item.id];
		if (!edit) return false;
		const value = draftText(edit.actual).trim();
		if (value === '') return item.actual !== null;
		return item.actual === null || Number(value) !== item.actual;
	}

	function canSaveItem(item: ContractItem): boolean {
		if (!itemDirty(item)) return false;
		if (selectedContract && canReviewSubmitted(selectedContract)) {
			return itemActualDirty(item) && Boolean(draftText(edits[item.id]?.actual).trim()) && Boolean(edits[item.id]?.reason.trim());
		}
		return true;
	}

	function behaviorDraftDirty(): boolean {
		if (!selectedContract) return false;
		return behaviorCriteria.some(({ key }) =>
			Number(behaviorDraft[key]) !== Number(selectedContract?.behavior_evaluation?.ratings?.[key])
		);
	}

	async function saveItem(item: ContractItem): Promise<void> {
		if (!selectedContract) return;
		const edit = edits[item.id];
		if (!edit) return;
		const payload: Record<string, unknown> = {};
		const isReviewCorrection = canReviewSubmitted(selectedContract);

		if (isReviewCorrection) {
			if (!itemActualDirty(item)) return;
			if (!edit.reason.trim()) {
				errorMessage = 'Catatan alasan koreksi KPI wajib diisi.';
				return;
			}
			const actual = draftText(edit.actual).trim();
			if (actual === '') {
				errorMessage = 'Nilai realisasi hasil koreksi wajib diisi.';
				return;
			}
			payload.actual = Number(actual);
			payload.reviewer_comment = edit.reason.trim();
		} else {

			if (canEditActual(selectedContract)) {
				const actual = draftText(edit.actual).trim();
				const evidence = edit.evidence.trim();
				payload.actual = actual === '' ? null : Number(actual);
				payload.note = edit.note;
				payload.evidence = evidence ? [{ label: 'Referensi', reference: evidence }] : [];
				payload.state = actual !== '' && evidence ? 'VALID' : 'PENDING';
			}
			if (canEditTarget(selectedContract)) {
				const target = draftText(edit.target).trim();
				const weightPercent = draftText(edit.weightPercent).trim();
				if (target && Number(target) !== item.target) payload.target = Number(target);
				if (weightPercent && Number(weightPercent) / 100 !== item.weight) {
					payload.weight = Number(weightPercent) / 100;
				}
				if ('target' in payload || 'weight' in payload) payload.reason = edit.reason;
			}
		}
		if (Object.keys(payload).length === 0) return;

		busy = true;
		errorMessage = '';
		successMessage = '';
		try {
			await api('/api/v1/contracts/' + selectedContract.id + '/items/' + item.id, {
				method: 'PATCH',
				body: JSON.stringify(payload)
			});
			await refreshSelectedContract();
			successMessage = isReviewCorrection
				? `Koreksi KPI tersimpan. Nilai akhir sekarang ${formatNumber(summaryFinalScore(), 2)}.`
				: 'Perubahan item tersimpan.';
		} catch (error) {
			errorMessage = error instanceof Error ? error.message : 'Perubahan belum tersimpan.';
		} finally {
			busy = false;
		}
	}

	async function saveBehaviorEvaluation(): Promise<boolean> {
		if (!selectedContract) return false;
		const missing = behaviorCriteria.filter(({ key }) => !behaviorDraft[key]);
		if (missing.length > 0) {
			errorMessage = `${missing.length} indikator perilaku belum dinilai.`;
			return false;
		}
		const isReviewCorrection = canReviewSubmitted(selectedContract);
		if (isReviewCorrection && !behaviorDraftDirty()) {
			errorMessage = 'Ubah minimal satu nilai perilaku sebelum menyimpan revisi.';
			return false;
		}
		if (isReviewCorrection && !behaviorRevisionComment.trim()) {
			errorMessage = 'Catatan alasan koreksi perilaku wajib diisi.';
			return false;
		}

		busy = true;
		errorMessage = '';
		successMessage = '';
		try {
			await api('/api/v1/contracts/' + selectedContract.id + '/behavior', {
				method: 'PUT',
				body: JSON.stringify({
					ratings: Object.fromEntries(behaviorCriteria.map(({ key }) => [key, Number(behaviorDraft[key])])),
					...(isReviewCorrection ? { reviewer_comment: behaviorRevisionComment.trim() } : {})
				})
			});
			behaviorRevisionComment = '';
			await refreshSelectedContract();
			successMessage = isReviewCorrection
				? `Koreksi perilaku tersimpan. Nilai akhir sekarang ${formatNumber(summaryFinalScore(), 2)}.`
				: 'Penilaian perilaku tersimpan.';
			return true;
		} catch (error) {
			errorMessage = error instanceof Error ? error.message : 'Nilai perilaku belum tersimpan.';
			return false;
		} finally {
			busy = false;
		}
	}

	async function addComment(): Promise<void> {
		if (!selectedContract || !commentDraft.trim()) return;
		busy = true;
		errorMessage = '';
		successMessage = '';
		try {
			await api('/api/v1/contracts/' + selectedContract.id + '/comments', {
				method: 'POST',
				body: JSON.stringify({ message: commentDraft.trim() })
			});
			commentDraft = '';
			successMessage = 'Komentar tersimpan.';
			await refreshWorkspace();
		} catch (error) {
			errorMessage = error instanceof Error ? error.message : 'Komentar belum tersimpan.';
		} finally {
			busy = false;
		}
	}

	async function submitContract(): Promise<void> {
		if (!selectedContract) return;
		busy = true;
		errorMessage = '';
		successMessage = '';
		try {
			await api('/api/v1/contracts/' + selectedContract.id + '/submit', { method: 'POST' });
			successMessage = 'Kontrak diajukan ke reviewer.';
			await refreshWorkspace();
		} catch (error) {
			errorMessage = error instanceof Error ? error.message : 'Kontrak belum dapat diajukan.';
		} finally {
			busy = false;
		}
	}

	async function reviewContract(decision: 'VERIFIED' | 'REVISION' | 'FINAL'): Promise<void> {
		if (!selectedContract || !reviewComment.trim()) {
			errorMessage = 'Catatan keputusan wajib diisi.';
			return;
		}
		busy = true;
		errorMessage = '';
		successMessage = '';
		try {
			await api('/api/v1/contracts/' + selectedContract.id + '/reviews', {
				method: 'POST',
				body: JSON.stringify({ decision, comment: reviewComment.trim() })
			});
			reviewComment = '';
			successMessage =
				decision === 'VERIFIED'
					? 'Kontrak terverifikasi.'
					: decision === 'REVISION'
						? 'Kontrak dikembalikan untuk revisi.'
						: 'Kontrak difinalisasi.';
			await refreshWorkspace();
		} catch (error) {
			errorMessage = error instanceof Error ? error.message : 'Keputusan belum tersimpan.';
		} finally {
			busy = false;
		}
	}

	function recordMenuAccess(next: AuditMenuKey, previous: AuditMenuKey): Promise<void> {
		if (!currentUser) return Promise.resolve();
		const labels: Record<AuditMenuKey, string> = {
			overview: 'Ringkasan',
			mine: 'KPI saya',
				reviews: canReviewInbox() ? 'Inbox evaluasi' : 'Tinjauan nilai',
			assignment: 'Isi KPI',
			periods: 'Periode penilaian',
			history: 'Riwayat saya',
			monitoring: 'Monitoring',
			catalog: 'Katalog KPI',
			mapping: 'Mapping KPI',
			positionKpis: 'Indikator jabatan',
			positions: 'Master jabatan',
			ssoMappings: 'Mapping identitas SSO',
			audit: 'Audit trail keputusan',
			comparison: 'Banding periode',
			auditLog: 'Audit log',
			'mine-kpi': 'KPI',
			'mine-behavior': 'Perilaku',
			'mine-approval': 'Periksa penilaian',
			'mine-history': 'Riwayat tindakan'
		};
		return api('/api/v1/audit-events/menu-access', {
			method: 'POST',
			body: JSON.stringify({ menu_key: next, menu_label: labels[next], previous_menu: previous, previous_menu_label: labels[previous] })
		}).then(() => undefined).catch(() => undefined);
	}

	async function loadAdminAuditLog(page = 1): Promise<void> {
		if (!canViewAuditTrail()) return;
		adminAuditLoading = true;
		adminAuditFailed = false;
		const params = new URLSearchParams({ page: String(page), per_page: '50' });
		if (adminAuditSearch.trim()) params.set('search', adminAuditSearch.trim());
		if (adminAuditAction) params.set('action', adminAuditAction);
		if (adminAuditFrom) params.set('from', adminAuditFrom);
		if (adminAuditTo) params.set('to', adminAuditTo);
		try {
			const response = await api<AdminAuditLogPage>('/api/v1/admin/audit-log?' + params.toString());
			adminAuditEvents = response.data;
			adminAuditMeta = response.meta;
		} catch {
			adminAuditFailed = true;
		} finally {
			adminAuditLoading = false;
		}
	}

	function setView(next: WorkspaceView, logSameView = false): void {
		const previous = view;
		view = next;
		if (next !== 'positions') {
			positionEditorOpen = false;
			positionDeactivateId = '';
		}
		selectedContract = null;
		auditEvents = [];
		auditLoadFailed = false;
		errorMessage = '';
		successMessage = '';
		const accessLog = next !== previous || logSameView
			? recordMenuAccess(next, previous)
			: Promise.resolve();
		if (next === 'auditLog') void accessLog.then(() => loadAdminAuditLog());
	}

	function pageTitle(): string {
		if (view === 'mine') return 'KPI saya';
		if (view === 'reviews') {
			if (canReviewInbox()) return 'Inbox evaluasi';
			return hasAccessRole('SDM') ? 'Finalisasi dan tinjauan' : 'Tinjauan nilai';
		}
		if (view === 'assignment') return staffTrack() ? `Isi KPI · ${staffTrackLabel()}` : 'Isi KPI jabatan';
		if (view === 'periods') return 'Atur periode';
		if (view === 'monitoring') return 'Monitoring Admin KPI';
		if (view === 'audit') return 'Audit trail keputusan';
		if (view === 'auditLog') return 'Audit log';
		if (view === 'comparison') return 'Banding periode';
		if (view === 'catalog') return 'Katalog KPI dan target';
		if (view === 'mapping' || view === 'positionKpis') return 'Indikator KPI jabatan';
		if (view === 'positions') return 'Master jabatan';
		if (view === 'ssoMappings') return 'Mapping identitas SSO';
		if (view === 'history') return 'Riwayat penilaian';
		if (view === 'overview' && canViewStaffHistory()) return 'Dashboard saya';
		return 'Ringkasan kinerja';
	}

	function navigationAction(action: () => void): () => void {
		return () => {
			closeMobileSidebar();
			action();
		};
	}

	let navigationSections = $derived.by((): WorkspaceNavigationSection[] => {
		const focusedSelfService = canSelfAssignRole() && !hasAdminAccess();
		const workspaceItems: WorkspaceNavigationSection['items'] = focusedSelfService ? [] : [
			{
				id: 'overview',
				label: canViewStaffHistory() ? 'Dashboard saya' : 'Ringkasan',
				icon: LayoutDashboard,
				active: view === 'overview',
				onSelect: navigationAction(() => setView('overview'))
			},
			{
				id: 'mine',
				label: 'KPI saya',
				icon: UserRound,
				active: view === 'mine',
				onSelect: navigationAction(() => void openMine())
			}
		];

		if (canSelfAssignRole()) {
			workspaceItems.push({
				id: 'assignment',
				label: 'Isi KPI',
				icon: ListPlus,
				active: view === 'assignment',
				onSelect: navigationAction(() => void openSelfAssignment())
			});
		}

		if (canViewStaffHistory()) {
			workspaceItems.push({
				id: 'history',
				label: 'Riwayat saya',
				icon: History,
				active: view === 'history',
				onSelect: navigationAction(openHistory)
			});
		}

		if (!focusedSelfService || canReviewAssignedContracts()) {
			workspaceItems.push({
				id: 'reviews',
				label: canReviewInbox() ? 'Inbox evaluasi' : 'Tinjauan nilai',
				icon: ClipboardCheck,
				active: view === 'reviews',
				count: dashboard?.contracts_needing_action ?? undefined,
				onSelect: navigationAction(() => (canReviewInbox() ? openReviews() : setView('reviews')))
			});
		}

		const sections: WorkspaceNavigationSection[] = [{ label: 'Ruang kerja', items: workspaceItems }];

		if (hasAdminAccess()) {
			sections.push({
				label: 'Administrasi',
				items: [
					{ id: 'positionKpis', label: 'Indikator jabatan', icon: ListPlus, active: view === 'positionKpis', onSelect: navigationAction(openAssignmentMapping) },
					{ id: 'periods', label: 'Periode penilaian', icon: CalendarDays, active: view === 'periods', onSelect: navigationAction(() => setView('periods')) },
					{ id: 'auditLog', label: 'Audit log', icon: FileClock, active: view === 'auditLog', onSelect: navigationAction(() => setView('auditLog')) }
				]
			});
		}

		return sections;
	});

	onMount(() => {
		void initialize();
	});
</script>

<svelte:head>
	<title>Tera · Kinerja BKK Jateng</title>
	<meta name="description" content="Ruang kerja kinerja BKK Jateng." />
	<link rel="icon" type="image/svg+xml" href="/tera-mark.svg" />
</svelte:head>

<svelte:window onkeydown={handleWindowKeydown} />

{#if loading}
	<main class="loading-screen" aria-live="polite">
		<div class="loading-mark" aria-hidden="true"></div>
		<p>Memuat ruang KPI…</p>
	</main>
{:else if !currentUser}
	<main class="login-page">
		<ThemeToggle isDark={isDark} onToggle={toggleTheme} className="login-theme-toggle" />
		<div class="login-frame">
			<section class="login-context" aria-labelledby="login-title">
				<div class="brand-line">
					<span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 40 40"><path d="M6 6h26v5H17.5v22h-5V11H6z"/><path class="brand-accent" d="M22 28h3v5h-3zm5-6h3v11h-3zm5-7h3v18h-3z"/></svg></span>
					<div><strong>Tera</strong><span>Kinerja BKK Jateng</span></div>
				</div>

				<div class="login-context-copy">
					<p class="login-step">RUANG KERJA KPI</p>
					<h1 id="login-title">Masuk dengan akun kerja Anda</h1>
					<p>Profil kerja menentukan jabatan dan akses yang dipakai di ruang KPI.</p>
				</div>

				<dl class="login-facts">
					<div><dt>Periode</dt><dd>Diatur Admin</dd></div>
					<div><dt>Cakupan</dt><dd>Pusat, wilayah, dan cabang</dd></div>
					<div><dt>Unit</dt><dd>Sesuai jabatan pegawai</dd></div>
				</dl>

				<p class="login-context-note">Periode tersedia 1, 3, 6, atau 12 bulan. Default rancangan mengikuti triwulan PRD.</p>
			</section>

			<section class="login-card" aria-labelledby="identity-title">
			<div class="login-copy">
				<h2 id="identity-title">Masuk ke ruang KPI</h2>
				<p>Gunakan akun kerja atau pilih profil pegawai untuk melanjutkan.</p>
			</div>
			<div class="login-mode-switch" role="group" aria-label="Metode masuk">
				<button type="button" class:active={loginMode === 'SSO'} aria-pressed={loginMode === 'SSO'} onclick={() => { loginMode = 'SSO'; errorMessage = ''; }}>Akun kerja SSO</button>
				<button type="button" class:active={loginMode === 'DEMO'} aria-pressed={loginMode === 'DEMO'} onclick={() => { loginMode = 'DEMO'; errorMessage = ''; }}>Pilih profil</button>
			</div>
			{#if errorMessage}<p class="alert error-alert" role="alert">{errorMessage}</p>{/if}
			{#if loginMode === 'SSO'}
				<form class="sso-login-form" onsubmit={(event) => { event.preventDefault(); void loginWithSso(); }}>
					<label class="field-label" for="sso-employee-id">ID Pegawai</label>
					<input id="sso-employee-id" class="text-input" bind:value={ssoEmployeeId} autocomplete="username" required />
					<label class="field-label" for="sso-password">Password</label>
					<input id="sso-password" class="text-input" type="password" bind:value={ssoPassword} autocomplete="current-password" required />
					<p class="sso-login-note">Pastikan akun ini sudah dipetakan Admin/SDM ke pegawai KPI. Password dan token diproses backend, bukan disimpan di browser.</p>
					<Button class="login-button" size="lg" type="submit" disabled={busy || !ssoEmployeeId.trim() || !ssoPassword}>
						{busy ? 'Memverifikasi akun…' : 'Masuk dengan SSO'}
					</Button>
				</form>
			{:else}

			{#if identitiesLoading}
				<p class="empty-inline" role="status">Memuat profil demo...</p>
			{:else if identitiesError}
				<div class="identity-load-state" role="alert">
					<p>{identitiesError}</p>
					<button type="button" class="identity-reload" onclick={() => void loadDemoIdentities()}>Muat ulang daftar</button>
				</div>
			{:else}
				<label class="field-label" for="identity-profile">Pilih profil demo</label>
				<select id="identity-profile" class="text-input identity-select" bind:value={selectedIdentityId} disabled={identities.length === 0}>
					<option value="" disabled>Pilih nama dan jabatan</option>
					{#each availableIdentityGroups() as group (group.key)}
						<optgroup label={group.label}>
							{#each group.identities as identity (identity.id)}
								<option value={identity.id}>{identity.display_name} — {identityRoleLabel(identity)} · {identity.org_unit_name}</option>
							{/each}
						</optgroup>
					{/each}
				</select>
			{/if}

			{#if selectedIdentity()}
				<div class="identity-selection" role="status" aria-live="polite">
					<span class="selection-label">IDENTITAS DIPILIH</span>
					<strong>{selectedIdentity()?.display_name}</strong>
					<span>{identityRoleLabel(selectedIdentity())} · {selectedIdentity()?.org_unit_name}</span>
				</div>
			{/if}

			<Button class="login-button" size="lg" disabled={busy || !selectedIdentityId} onclick={login}>
				{busy ? 'Menyiapkan sesi…' : 'Masuk'}
			</Button>
			{/if}
			</section>
		</div>
	</main>
{:else}
	<div class="app-shell" class:is-collapsed={sidebarCollapsed}>
		<WorkspaceSidebar
			sections={navigationSections}
			collapsed={sidebarCollapsed}
			mobileOpen={mobileSidebarOpen}
			userName={currentUser.display_name}
			userRole={roleListLabel(currentUser.access_role, currentUser.access_roles)}
			userInitials={initials(currentUser.display_name)}
			isDark={isDark}
			busy={busy}
			onToggleCollapsed={toggleSidebarCollapsed}
			onToggleTheme={toggleTheme}
			onLogout={logout}
			onClose={closeMobileSidebar}
		/>

		<div class="workspace">
			<header class="topbar">
				<div class="topbar-leading">
					<Button
						variant="ghost"
						size="icon"
						class="mobile-sidebar-trigger"
						aria-label="Buka navigasi utama"
						aria-expanded={mobileSidebarOpen}
						aria-controls="main-sidebar"
						onclick={() => (mobileSidebarOpen = true)}
					>
						<Menu size={19} strokeWidth={1.8} aria-hidden="true" />
					</Button>
				<div class="breadcrumb">Tera <span>/</span> {pageTitle()}</div>
				</div>
				<div class="topbar-actions">
					<Button
						variant="ghost"
						size="icon"
						class="notification-button"
						aria-label={dashboard && dashboard.contracts_needing_action > 0 ? 'Notifikasi, ' + dashboard.contracts_needing_action + ' perlu tindakan' : 'Notifikasi'}
						title="Notifikasi dan tugas yang perlu tindakan"
						onclick={() => (canReviewInbox() ? openReviews() : setView('reviews'))}
					>
						<Bell size={18} strokeWidth={1.8} aria-hidden="true" />
						{#if dashboard && dashboard.contracts_needing_action > 0}<span class="notification-count" aria-hidden="true">{dashboard.contracts_needing_action}</span>{/if}
					</Button>
					<span class="user-avatar topbar-profile" role="img" aria-label={'Profil ' + currentUser.display_name} title={currentUser.display_name}>
						<UserRound size={18} strokeWidth={1.8} aria-hidden="true" />
					</span>
				</div>
			</header>
			<main class="page-content">
				{#snippet openInputCycleCards()}
					{@const openCycles = openInputCycles()}
					{#if openCycles.length === 0}
						<div class="open-cycle-empty" role="status">
							<strong>{cycles.some((cycle) => cycle.status === 'OPEN') ? 'Tidak ada periode yang perlu diisi' : 'Belum ada periode pengisian yang dibuka'}</strong>
							<p>{cycles.some((cycle) => cycle.status === 'OPEN') ? 'Periode yang sudah diajukan dapat dilihat pada Riwayat saya.' : 'Periode KPI akan muncul di sini setelah Admin membuka pengisian.'}</p>
						</div>
					{:else}
						<div class="open-cycle-list" aria-label="Daftar periode KPI yang perlu diisi">
							{#each openCycles as cycle (cycle.id)}
								{@const contract = existingContractForCycle(cycle)}
								<article class="open-cycle-card">
									<div class="open-cycle-copy">
										<span>{periodCadenceLabel(cycleCadenceMonths(cycle))} · {formatDate(cycle.starts_at)} – {formatDate(cycle.ends_at)}</span>
										<h3>{cycle.name}</h3>
										<p>{contract?.status === 'REVISION' ? 'Penilai meminta perbaikan pada penilaian ini.' : contract ? 'Draf tersimpan. Lanjutkan pengisian KPI jabatan Anda.' : 'Wajib diisi. Lengkapi KPI jabatan dan lima indikator perilaku.'}</p>
									</div>
									<div class="open-cycle-actions">
										<Badge variant={contract ? statusVariant(contract.status) : 'default'}>{contract ? statusNames[contract.status] : 'Wajib diisi'}</Badge>
										<Button size="sm" onclick={() => void openSelfAssignment(cycle.id)}>{contract?.status === 'REVISION' ? 'Perbaiki KPI' : contract ? 'Lanjutkan isi KPI' : 'Isi KPI'}</Button>
									</div>
								</article>
							{/each}
						</div>
					{/if}
				{/snippet}
				<div class="page-heading">
					<div>
						<p class="eyebrow">{view === 'periods' || view === 'positionKpis' ? 'KONFIGURASI ADMIN' : view === 'history' ? 'RIWAYAT SAYA' : view === 'reviews' ? 'TINJAUAN PENILAIAN' : view === 'assignment' ? 'PILIH PERIODE' : view === 'mine' && canViewStaffHistory() ? staffDashboardPeriodLabel() : (dashboard?.cycle.name ?? 'KCU UTAMA')}</p>
						<h1>{pageTitle()}</h1>
						<p class="page-subtitle">{currentUser.org_unit_name} · {currentUser.position_name}</p>
					</div>
					{#if dashboard?.cycle && view !== 'mine' && view !== 'assignment' && view !== 'periods' && view !== 'history' && view !== 'monitoring' && view !== 'catalog' && view !== 'mapping' && view !== 'positionKpis' && view !== 'positions' && view !== 'ssoMappings' && view !== 'audit' && view !== 'comparison'}
						<div class="period-pill"><span class="period-dot"></span><span class="period-pill-copy"><span><strong>Siklus penilaian:</strong> {formatDate(dashboard.cycle.starts_at)} – {formatDate(dashboard.cycle.ends_at)}</span><small>Periode realisasi: {cycleMeasurementRange(dashboard.cycle)}</small></span></div>
					{/if}
				</div>

				{#if view === 'mine' && canViewStaffHistory()}
					<nav class="staff-workspace-tabs" aria-label="Bagian KPI saya" role="tablist">
						<button id="staff-tab-kpi" type="button" role="tab" aria-controls="staff-workspace-tab-panel" aria-selected={staffWorkspaceTab === 'KPI'} class:active={staffWorkspaceTab === 'KPI'} onclick={() => void selectStaffWorkspaceTab('KPI')}>KPI</button>
						<button id="staff-tab-behavior" type="button" role="tab" aria-controls="staff-workspace-tab-panel" aria-selected={staffWorkspaceTab === 'BEHAVIOR'} class:active={staffWorkspaceTab === 'BEHAVIOR'} onclick={() => void selectStaffWorkspaceTab('BEHAVIOR')}>Perilaku</button>
						<button id="staff-tab-approval" type="button" role="tab" aria-controls="staff-workspace-tab-panel" aria-selected={staffWorkspaceTab === 'APPROVAL'} class:active={staffWorkspaceTab === 'APPROVAL'} onclick={() => void selectStaffWorkspaceTab('APPROVAL')}>Periksa</button>
						<button id="staff-tab-history" type="button" role="tab" aria-controls="staff-workspace-tab-panel" aria-selected={staffWorkspaceTab === 'HISTORY'} class:active={staffWorkspaceTab === 'HISTORY'} onclick={() => void selectStaffWorkspaceTab('HISTORY')}>Riwayat tindakan</button>
					</nav>
				{/if}

				{#if view !== 'mine'}
				<div class="notice scoring-notice" role="note">
					<Info class="notice-mark" size={17} strokeWidth={1.8} aria-hidden="true" />
							<div><strong>Bobot penilaian</strong><p>Nilai akhir terdiri dari 80% KPI jabatan dan 20% perilaku.</p></div>
				</div>
				{/if}
				{#if errorMessage || successMessage}
					<div class="app-toast-stack" aria-live="polite">
						{#if errorMessage}<p class="alert error-alert" role="alert">{errorMessage}</p>{/if}
						{#if successMessage}<p class="alert success-alert" role="status">{successMessage}</p>{/if}
					</div>
				{/if}

				{#if view === 'overview' && dashboard}
					{#if canViewStaffHistory()}
						{@const inputCycle = staffDashboardCycle()}
						{@const inputContract = staffDashboardContract()}
						<section class="staff-dashboard" aria-labelledby="staff-dashboard-title">
							<div class="staff-dashboard-heading">
								<div>
									<p class="eyebrow">DASHBOARD SAYA · {staffTrackLabel()}</p>
									<h2 id="staff-dashboard-title">Fokus kerja periode aktif</h2>
									<p class="muted-copy">Satu tempat untuk melihat apa yang sudah selesai dan tindakan KPI berikutnya.</p>
								</div>
								<span class="staff-track-chip">KCU Utama</span>
							</div>

							<div class="staff-dashboard-top">
								<section class="staff-period-card" aria-labelledby="staff-period-title">
									<div class="staff-card-heading"><span class="dashboard-label">PERIODE PENGISIAN</span>{#if inputCycle}<span class="period-status-dot"></span>{/if}</div>
									<h3 id="staff-period-title">{inputCycle?.name ?? 'Tidak ada periode terbuka'}</h3>
									<p>{inputCycle ? `${formatDate(inputCycle.starts_at)} – ${formatDate(inputCycle.ends_at)}` : 'Admin belum membuka periode pengisian.'}</p>
									<div class="staff-period-meta"><span>Jalur</span><strong>{staffTrackLabel()}</strong></div>
								</section>
								<section class="staff-task-card" aria-labelledby="staff-task-title">
									<div class="staff-card-heading"><span class="dashboard-label">TUGAS BERIKUTNYA</span>{#if inputCycle}<span class="task-urgency">Prioritas</span>{/if}</div>
									<h3 id="staff-task-title">{staffDashboardTaskLabel()}</h3>
									<p>{staffDashboardTaskDetail()}</p>
									<div class="staff-task-footer"><span>Batas pengisian</span><strong>{inputCycle ? formatDate(inputCycle.ends_at) : '—'}</strong></div>
									{#if inputCycle}
									<Button class="staff-task-button" size="sm" onclick={() => void openSelfAssignment(inputCycle.id)}>{inputContract?.status === 'REVISION' ? 'Buka revisi' : inputContract ? 'Lanjutkan KPI' : 'Mulai isi KPI'}</Button>
									{/if}
								</section>
							</div>

							<div class="staff-dashboard-signals" aria-label="Status KPI saya">
								{#if inputContract}
									<section class="staff-signal-card">
										<span class="dashboard-label">PENILAIAN MANDIRI</span>
										<strong>{staffDashboardAssignmentLabel()}</strong>
										<p>{staffDashboardAssignmentDetail()}</p>
										<Badge variant={inputContract.status === 'SUBMITTED' ? 'secondary' : statusVariant(inputContract.status)}>{statusNames[inputContract.status]}</Badge>
									</section>
									<section class="staff-signal-card">
										<div class="staff-signal-heading"><span class="dashboard-label">PROGRES KPI</span><strong>{staffDashboardProgress().percent}%</strong></div>
										<div class="dashboard-progress" aria-label={'Progres KPI ' + staffDashboardProgress().percent + ' persen'}><span style={'width: ' + staffDashboardProgress().percent + '%'}></span></div>
										<p>{staffDashboardProgress().completed} dari {staffDashboardProgress().total} indikator lengkap.</p>
									</section>
									<section class="staff-signal-card">
										<span class="dashboard-label">TARGET VS REALISASI</span>
										<strong>{staffDashboardRealizationSummary()}</strong>
										<p>Ringkasan pengisian periode aktif.</p>
									</section>
								{:else}
									<section class="staff-signal-card">
										<span class="dashboard-label">STATUS PENGISIAN</span>
										<strong>{staffDashboardAssignmentLabel()}</strong>
										<p>{staffDashboardAssignmentDetail()}</p>
									</section>
								{/if}
							</div>

							<section class="staff-history-card" aria-labelledby="staff-history-title">
								<div>
									<p class="eyebrow">PENILAIAN TERAKHIR</p>
									<h3 id="staff-history-title">{staffDashboardLatestHistory()?.periodName ?? 'Belum ada riwayat'}</h3>
									<p>{staffDashboardLatestHistory() ? `${statusNames[staffDashboardLatestHistory()?.status ?? 'DRAFT']} · Skor ${staffDashboardLatestHistory()?.score === null ? 'belum dihitung' : formatNumber(staffDashboardLatestHistory()?.score ?? null, 1)}` : 'Riwayat akan muncul setelah penilaian tersedia.'}</p>
								</div>
								<Button class="staff-history-button" variant="outline" size="sm" onclick={openHistory}>Buka riwayat</Button>
							</section>
						</section>
					{:else}
					<section class="stat-grid" aria-label="Ringkasan siklus">
						<Card.Root class="stat-card">
							<Card.Header><Card.Description>Kontrak dalam cakupan</Card.Description><Card.Title>{formatNumber(dashboard.counts.total_contracts)}</Card.Title></Card.Header>
							<Card.Content><span class="stat-note">Siklus {dashboard.cycle.name}</span></Card.Content>
						</Card.Root>
						<Card.Root class="stat-card">
							<Card.Header><Card.Description>Siap ditinjau</Card.Description><Card.Title>{formatNumber(dashboard.counts.ready_contracts)}</Card.Title></Card.Header>
							<Card.Content><span class="stat-note">KPI lengkap pada penilaian</span></Card.Content>
						</Card.Root>
						<Card.Root class="stat-card">
							<Card.Header><Card.Description>Diajukan</Card.Description><Card.Title>{formatNumber(dashboard.counts.submitted_contracts)}</Card.Title></Card.Header>
							<Card.Content><span class="stat-note">Menunggu keputusan reviewer</span></Card.Content>
						</Card.Root>
						<Card.Root class="stat-card stat-card-gold">
							<Card.Header><Card.Description>Perlu tindakan Anda</Card.Description><Card.Title>{formatNumber(dashboard.contracts_needing_action)}</Card.Title></Card.Header>
							<Card.Content><span class="stat-note">Antrean sesuai peran aktif</span></Card.Content>
						</Card.Root>
					</section>
					{/if}
				{/if}

				{#if view === 'positionKpis' && canManageAssignment()}
					<section class="position-version-admin" aria-labelledby="position-version-title">
						<header class="position-version-heading">
							<div>
								<p class="eyebrow">ADMIN KPI / SDM</p>
								<h2 id="position-version-title">Indikator KPI jabatan</h2>
								<p class="muted-copy">Atur indikator, target, dan bobot untuk setiap jabatan. Perubahan paket tersimpan sebagai versi baru.</p>
							</div>
							<Button variant="outline" size="sm" disabled={positionKpiSettingsLoading} onclick={() => void loadPositionKpiSettings()}>{positionKpiSettingsLoading ? 'Memuat…' : 'Muat ulang'}</Button>
						</header>

						{#if positionKpiSettingsLoading}
							<div class="position-version-empty" role="status">Memuat indikator jabatan…</div>
						{:else if positionKpiSettings.length === 0}
			<div class="position-version-empty" role="status"><strong>Belum ada jabatan</strong><p>Jabatan Pusat, Wilayah, dan Cabang akan tampil setelah data master dimuat.</p></div>
						{:else}
							{@const selectedPositionKpi = selectedPositionKpiSetting()}
							<div class="position-version-layout">
								<aside class="position-version-list" aria-label="Pilih jabatan">
								<div class="position-version-list-heading"><p class="eyebrow">JABATAN ORGANISASI</p><span>{positionKpiSettings.length} jabatan</span></div>
									{#each positionKpiSettings as position (position.id)}
										{@const activeVersion = activePositionKpiVersion(position)}
										<button type="button" class="position-version-list-item" class:selected={selectedPositionKpiId === position.id} aria-pressed={selectedPositionKpiId === position.id} onclick={() => selectPositionKpiSetting(position.id)}>
											<strong>{position.name}</strong>
											<small>{position.group_name} · {position.access_role}</small>
											<span>{activeVersion ? `V${activeVersion.version} aktif · ${activeVersion.items.length} indikator` : 'Belum ada paket KPI'}</span>
										</button>
									{/each}
								</aside>

								{#if selectedPositionKpi}
									{@const activeVersion = activePositionKpiVersion(selectedPositionKpi)}
									<article class="position-version-detail">
										<header class="position-version-detail-heading">
											<div><p class="eyebrow">{selectedPositionKpi.group_name} · {selectedPositionKpi.access_role}</p><h3>{selectedPositionKpi.name}</h3><p class="muted-copy">Self-assignment pada periode yang dibuka memakai versi yang sudah dikunci untuk periode tersebut.</p></div>
											<Badge variant={activeVersion ? 'secondary' : 'outline'}>{activeVersion ? `V${activeVersion.version} · Aktif` : 'Belum diatur'}</Badge>
										</header>

										<section class="position-version-editor" aria-label="Edit indikator paket aktif">
											<div class="position-version-editor-heading"><div><p class="eyebrow">PAKET KPI</p><h4>{activeVersion ? `Versi V${activeVersion.version}` : 'Paket pertama'}</h4></div><span>{positionKpiDraftItems.length} indikator</span></div>
											{#if positionKpiDraftItems.length === 0}<div class="position-version-empty"><strong>Belum ada indikator</strong><p>Tambahkan indikator untuk membuat paket V1 bagi jabatan ini.</p></div>{/if}
											<div class="position-version-items">
												{#each positionKpiDraftItems as item, index (item.client_id)}
													<article class="position-version-item">
														<div class="position-version-item-heading"><strong>Indikator {index + 1}</strong><Button type="button" variant="ghost" size="sm" disabled={positionKpiSettingsBusy} onclick={() => removePositionKpiDraftItem(item.client_id)}>Hapus</Button></div>
														<div class="position-version-fields">
															<label>Kode<input class="text-input" value={item.code} maxlength="80" oninput={(event) => updatePositionKpiDraftItem(item.client_id, 'code', event.currentTarget.value)} /></label>
															<label>Nama indikator<input class="text-input" value={item.name} maxlength="160" oninput={(event) => updatePositionKpiDraftItem(item.client_id, 'name', event.currentTarget.value)} /></label>
															<label>Jenis pengukuran<select class="text-input" value={item.score_type} onchange={(event) => updatePositionKpiDraftItem(item.client_id, 'score_type', event.currentTarget.value)}><option value="A">A · Makin tinggi</option><option value="Q">Q · Kualitas</option><option value="L">L · Makin rendah</option><option value="Z">Z · Tanpa target</option></select></label>
															<label>Target<input class="text-input" type="number" min={item.score_type === 'Z' ? 0 : 0.01} max={['Q', 'L'].includes(item.score_type) ? 100 : undefined} step="any" value={item.target} disabled={item.score_type === 'Z'} oninput={(event) => updatePositionKpiDraftItem(item.client_id, 'target', event.currentTarget.value)} /></label>
															<label>Satuan<input class="text-input" value={item.unit} maxlength="40" oninput={(event) => updatePositionKpiDraftItem(item.client_id, 'unit', event.currentTarget.value)} /></label>
															<label>Bobot (%)<input class="text-input" type="number" min="0.01" max="100" step="0.01" value={item.weight_percent} oninput={(event) => updatePositionKpiDraftItem(item.client_id, 'weight_percent', event.currentTarget.value)} /></label>
														</div>
														<label class="position-version-description">Keterangan indikator<textarea class="text-input textarea-input" rows="2" maxlength="3000" value={item.definition} oninput={(event) => updatePositionKpiDraftItem(item.client_id, 'definition', event.currentTarget.value)}></textarea></label>
													</article>
												{/each}
											</div>
											<div class="position-version-add"><Button type="button" variant="outline" size="sm" disabled={positionKpiSettingsBusy} onclick={addPositionKpiDraftItem}>Tambah indikator</Button><span>Total bobot <strong class:assignment-total-valid={Math.abs(positionKpiWeightTotal() - 100) < 0.011}>{formatNumber(positionKpiWeightTotal(), 2)}%</strong> dari 100%</span></div>
											{#if positionKpiSaveBlockedReason()}<p class="position-version-validation" role="status">{positionKpiSaveBlockedReason()}</p>{/if}
										<div class="position-version-save"><p>{activeVersion ? `Simpan perubahan akan membuat V${activeVersion.version + 1}. Periode yang sudah dibuka tetap memakai versi terkunci; versi lama tetap tersimpan.` : 'Simpan paket pertama sebagai V1.'}</p><Button disabled={positionKpiSettingsBusy || Boolean(positionKpiSaveBlockedReason())} onclick={() => void savePositionKpiSettings()}>{positionKpiSettingsBusy ? 'Menyimpan…' : activeVersion ? `Simpan sebagai V${activeVersion.version + 1}` : 'Simpan paket V1'}</Button></div>
										</section>

										<details class="position-version-history">
											<summary>Riwayat versi · {selectedPositionKpi.versions.filter((version) => version.status === 'ARCHIVED').length} versi sebelumnya</summary>
											{#if selectedPositionKpi.versions.filter((version) => version.status === 'ARCHIVED').length === 0}<p>Versi lama akan tampil setelah paket pertama diperbarui.</p>{:else}
												{#each selectedPositionKpi.versions.filter((version) => version.status === 'ARCHIVED') as version (version.id)}
													<article><div><strong>V{version.version}</strong><span>{version.items.length} indikator · {version.created_at ? formatDate(version.created_at) : 'Tanggal tidak tersedia'}</span></div><ul>{#each version.items as item (item.definition_id)}<li><span>{item.code} · {item.name}</span><small>Target {formatNumber(item.target, 2)} {item.unit} · Bobot {formatNumber(item.weight_percent, 2)}%</small></li>{/each}</ul></article>
												{/each}
											{/if}
										</details>
									</article>
								{/if}
							</div>
						{/if}
					</section>
				{/if}

				{#if view === 'mapping' && canManageAssignment()}
					<section class="mapping-panel" aria-labelledby="mapping-title">
						<div class="mapping-heading">
							<div>
								<p class="eyebrow">ADMIN KPI / SDM</p>
								<h2 id="mapping-title">Penetapan KPI ke jabatan</h2>
								<p class="muted-copy">Pilih indikator Aktif dari katalog, sesuaikan target dan bobot, lalu periksa paket yang akan muncul pada self-assignment.</p>
							</div>
				<div class="mapping-state-note"><strong>{assignmentSaveState === 'SAVED' ? 'Tersimpan untuk preview' : 'Draf penetapan'}</strong><span>Belum mengubah API Laravel.</span></div>
						</div>

						<div class="mapping-context-bar">
							<label>Jalur jabatan<select class="text-input" value={assignmentTrack} onchange={(event) => seedAssignmentForTrack(event.currentTarget.value as StaffTrackKind)}><option value="BUSINESS">Staf Pemasaran / Bisnis</option><option value="NON_BUSINESS">Staf Operasional / Non-bisnis</option></select></label>
							<div><span>Indikator Aktif</span><strong>{activeCatalogItemsForTrack(assignmentTrack).length} tersedia</strong></div>
							<div><span>Total bobot</span><strong class:assignment-total-valid={Math.abs(assignmentWeightTotal() - 100) < 0.01}>{formatNumber(assignmentWeightTotal(), 2)}%</strong></div>
						</div>

						<div class="mapping-layout">
							<section class="mapping-selection" aria-labelledby="mapping-selection-title">
								<div class="mapping-section-heading"><div><p class="eyebrow">PILIH DARI KATALOG</p><h3 id="mapping-selection-title">Indikator untuk {catalogTrackLabel(assignmentTrack)}</h3></div><span>Hanya state Aktif</span></div>
								{#if activeCatalogItemsForTrack(assignmentTrack).length === 0}
									<div class="mapping-empty" role="status"><strong>Belum ada indikator Aktif</strong><p>Aktifkan indikator dari katalog KPI sebelum menetapkan ke jabatan.</p><Button variant="outline" onclick={openCatalog}>Buka katalog KPI</Button></div>
								{:else}
									<div class="mapping-item-list">
										{#each activeCatalogItemsForTrack(assignmentTrack) as item (item.id)}
											<article class="mapping-item" class:selected={assignmentSelectedIds.includes(item.id)}>
												<label class="mapping-item-selector"><input type="checkbox" checked={assignmentSelectedIds.includes(item.id)} onchange={(event) => toggleAssignmentItem(item.id, event.currentTarget.checked)} /><span><strong>{item.name}</strong><small>{item.code} / {item.group} / {item.unit}</small></span></label>
												{#if assignmentSelectedIds.includes(item.id)}
													<div class="mapping-item-fields"><label>Target<input class="text-input" type="number" min="0.01" step="any" value={assignmentTargetDrafts[item.id] ?? String(item.target)} oninput={(event) => updateAssignmentTarget(item.id, event.currentTarget.value)} /></label><label>Bobot (%)<input class="text-input" type="number" min="0.01" max="100" step="0.01" value={assignmentWeightDrafts[item.id] ?? ''} oninput={(event) => updateAssignmentWeight(item.id, event.currentTarget.value)} /></label></div>
												{:else}
													<p class="mapping-item-sample">Target katalog {formatNumber(item.target, 2)} {item.unit} / bobot {formatNumber(item.weight * 100, 2)}%</p>
												{/if}
											</article>
										{/each}
									</div>
								{/if}
								<div class="mapping-selection-footer"><p><strong>{selectedAssignmentItems().length} indikator dipilih</strong><span>{assignmentWeightStatus()}</span></p><Button variant="outline" size="sm" disabled={selectedAssignmentItems().length === 0} onclick={normalizeAssignmentWeights}>Bagi bobot rata</Button></div>
							</section>

							<aside class="mapping-preview" aria-labelledby="mapping-preview-title">
				<div class="mapping-section-heading"><div><p class="eyebrow">PREVIEW SELF-ASSIGNMENT</p><h3 id="mapping-preview-title">Paket KPI {catalogTrackLabel(assignmentTrack)}</h3></div><span>{assignmentSaveState === 'SAVED' ? 'Preview tersimpan' : 'Belum disimpan'}</span></div>
								<div class="mapping-preview-intro"><strong>Yang akan dilihat staf</strong><p>Indikator Aktif di bawah akan muncul sebagai pilihan KPI untuk jabatan ini.</p></div>
								{#if selectedAssignmentItems().length === 0}
									<div class="mapping-preview-empty"><strong>Belum ada KPI dipilih</strong><p>Pilih minimal satu indikator dari daftar sebelah kiri.</p></div>
								{:else}
									<div class="mapping-preview-list">
										{#each selectedAssignmentItems() as item (item.id)}
											<div class="mapping-preview-row"><div><strong>{item.name}</strong><small>{item.code} / {item.unit}</small></div><span>{formatNumber(Number(assignmentTargetDrafts[item.id]), 2)} {item.unit}</span><strong>{formatNumber(Number(assignmentWeightDrafts[item.id]), 2)}%</strong></div>
										{/each}
									</div>
								{/if}
								<div class="mapping-preview-total"><span>Status bobot</span><strong class:assignment-total-valid={Math.abs(assignmentWeightTotal() - 100) < 0.01}>{assignmentWeightStatus()}</strong><small>Total {formatNumber(assignmentWeightTotal(), 2)}% dari 100%.</small></div>
				<Button class="mapping-save-button" disabled={selectedAssignmentItems().length === 0 || Math.abs(assignmentWeightTotal() - 100) >= 0.01} onclick={saveDemoAssignmentMapping}>Simpan preview penetapan</Button>
							</aside>
						</div>
						<p class="mapping-footnote">Penetapan ini menjadi preview kebijakan per jabatan. Pengaturan KPI untuk jabatan akan tersedia di langkah berikutnya.</p>
					</section>
				{/if}

				{#if view === 'ssoMappings' && canManageSsoMappings()}
					<section class="sso-mapping-panel" aria-labelledby="sso-mapping-title">
						<div class="sso-mapping-heading">
							<div>
								<p class="eyebrow">ADMIN KPI / SDM</p>
								<h2 id="sso-mapping-title">Hubungkan akun kerja ke user KPI</h2>
								<p class="muted-copy">Masukkan ID pegawai yang dipakai saat login SSO, lalu pilih identitas demo yang memang sesuai.</p>
							</div>
							<Button variant="outline" size="sm" disabled={ssoMappingLoading} onclick={() => void loadSsoMappings()}>Muat ulang</Button>
						</div>

						<div class="sso-mapping-notice" role="note">
							<strong>Pasangkan orang yang sama</strong>
							<p>Jabatan dan unit kerja harus cocok. Hak Admin KPI dicatat sebagai akses tambahan, tanpa mengubah jabatan pegawai. Jika belum ada user demo yang cocok, hentikan mapping dulu.</p>
						</div>

						{#if ssoMappingLoading}
							<div class="sso-mapping-empty" role="status">Memuat mapping SSO…</div>
						{:else}
							<div class="sso-mapping-layout">
								<form class="sso-mapping-form" onsubmit={saveSsoMapping}>
									<div>
										<p class="eyebrow">TAMBAH MAPPING</p>
										<h3>Akun SSO baru</h3>
									</div>
									<label for="sso-external-employee-id">ID pegawai SSO
										<input id="sso-external-employee-id" class="text-input" bind:value={ssoMappingExternalId} maxlength="80" autocomplete="off" placeholder="Contoh: 102-121" required />
									</label>
									<label for="sso-target-employee">Profil pegawai
										<select id="sso-target-employee" class="text-input" bind:value={ssoMappingTargetId} required>
											<option value="" disabled>Pilih identitas yang cocok</option>
											{#each ssoMappingCandidates as candidate (candidate.employee_id)}
												<option value={candidate.employee_id} disabled={ssoCandidateIsMapped(candidate.employee_id)}>{candidate.display_name} · {roleListLabel(candidate.access_role, candidate.access_roles)} · {candidate.position_name} · {candidate.org_unit_name}{ssoCandidateIsMapped(candidate.employee_id) ? ' (sudah dipasangkan)' : ''}</option>
											{/each}
										</select>
									</label>
									<label class="sso-mapping-confirm"><input type="checkbox" bind:checked={ssoMappingConfirmed} /><span>Saya sudah memastikan ID SSO dan user KPI ini milik pegawai yang sama dengan jabatan serta unit yang sesuai.</span></label>
									<Button type="submit" disabled={ssoMappingBusy || !ssoMappingExternalId.trim() || !ssoMappingTargetId || !ssoMappingConfirmed}>{ssoMappingBusy ? 'Menyimpan…' : 'Simpan mapping'}</Button>
									{#if ssoMappingIssuer}<small class="sso-mapping-issuer">Penerbit: {ssoMappingIssuer}</small>{/if}
								</form>

								<section class="sso-mapping-list-panel" aria-labelledby="sso-mapping-list-title">
									<div class="sso-mapping-list-heading"><div><p class="eyebrow">DAFTAR IDENTITAS</p><h3 id="sso-mapping-list-title">Mapping tersimpan</h3></div><span>{ssoMappings.filter((mapping) => mapping.active).length} aktif</span></div>
									{#if ssoMappings.length === 0}
										<div class="sso-mapping-empty" role="status"><strong>Belum ada mapping</strong><p>Login SSO belum dapat membuat sesi KPI sampai ID pegawai dipasangkan dengan user KPI demo.</p></div>
									{:else}
										<div class="sso-mapping-list">
											{#each ssoMappings as mapping (mapping.id)}
												<article class="sso-mapping-row">
													<div class="sso-mapping-row-main"><span class="sso-mapping-id">{mapping.external_employee_id}</span><strong>{mapping.employee_name ?? mapping.employee_id}</strong><small>{roleListLabel(mapping.access_role ?? '', mapping.access_roles)} · {mapping.position_name ?? 'Penugasan tidak tersedia'} · {mapping.org_unit_name ?? 'Unit tidak tersedia'}</small></div>
													<div class="sso-mapping-row-actions"><Badge variant={mapping.active ? 'secondary' : 'outline'}>{mapping.active ? 'Aktif' : 'Nonaktif'}</Badge><Button variant="outline" size="sm" disabled={ssoMappingActionId === mapping.id} onclick={() => void setSsoMappingActive(mapping, !mapping.active)}>{ssoMappingActionId === mapping.id ? 'Menyimpan…' : mapping.active ? 'Nonaktifkan' : 'Aktifkan'}</Button></div>
												</article>
											{/each}
										</div>
									{/if}
								</section>
							</div>
						{/if}
						<p class="sso-mapping-footnote">Mapping hanya tersedia untuk user demo Admin KPI/SDM. ID SSO disimpan pada backend dan tidak ditampilkan ke user biasa.</p>
					</section>
				{/if}

				{#if view === 'positions' && canManagePositions()}
					<section class="position-mapping-panel" aria-labelledby="position-mapping-title">
						<div class="position-mapping-heading">
							<div>
								<p class="eyebrow">ADMIN KPI / SDM</p>
								<h2 id="position-mapping-title">Master jabatan dan mapping KPI</h2>
								<p class="muted-copy">Pilih jabatan untuk membaca organisasi dan indikator aktif pada katalog KPI.</p>
							</div>
							<div class="position-heading-actions"><div class="position-readonly-note"><strong>Frontend-only</strong><span>Belum tersambung API Laravel.</span></div><Button size="sm" onclick={openPositionCreate}>Tambah jabatan</Button></div>
						</div>

						<div class="position-mapping-toolbar">
							<label for="position-selector">Jabatan<select id="position-selector" class="text-input" bind:value={selectedPositionId}>{#each demoPositionMappings as position (position.id)}<option value={position.id}>{position.name}</option>{/each}</select></label>
							<div class="position-count-note"><span>JABATAN DEMO</span><strong>{demoPositionMappings.length} tersedia</strong><small>Mapping dibaca dari katalog Aktif.</small></div>
						</div>

						<div class="position-mapping-layout">
							<aside class="position-list-panel" aria-labelledby="position-list-title">
								<div class="position-section-heading"><div><p class="eyebrow">MASTER JABATAN</p><h3 id="position-list-title">Daftar jabatan</h3></div><span>{demoPositionMappings.length} contoh</span></div>
								<div class="position-list">
									{#each demoPositionMappings as position (position.id)}
										<button type="button" class="position-list-item" class:selected={selectedPositionId === position.id} aria-pressed={selectedPositionId === position.id} aria-label={'Lihat detail ' + position.name} onclick={() => (selectedPositionId = position.id)}>
											<div><strong>{position.name}</strong><small>{position.level} / {position.orgUnitName}</small></div>
											<div class="position-list-status"><span class={'position-list-state position-list-state-' + position.status.toLowerCase()}>{positionStatusLabel(position.status)}</span><small>{positionMappingStatus(position) === 'MAPPED' ? 'Mapping siap' : 'Mapping kosong'}</small></div>
										</button>
									{/each}
								</div>
							</aside>

							<article class="position-detail-panel" aria-labelledby="position-detail-title">
								<div class="position-detail-heading">
									<div><p class="eyebrow">DETAIL JABATAN</p><h3 id="position-detail-title">{selectedPosition().name}</h3><p>{selectedPosition().note}</p></div>
									<div class="position-detail-tools"><div class="position-detail-badges"><Badge variant={selectedPosition().status === 'ACTIVE' ? 'secondary' : 'outline'}>{positionStatusLabel(selectedPosition().status)}</Badge><Badge variant={positionMappingStatus(selectedPosition()) === 'MAPPED' ? 'secondary' : 'outline'}>{positionMappingStatus(selectedPosition()) === 'MAPPED' ? 'Mapping aktif' : 'Belum ada mapping'}</Badge></div><div class="position-detail-actions"><Button variant="outline" size="sm" onclick={() => openPositionEdit(selectedPosition())}>Edit jabatan</Button>{#if selectedPosition().status === 'ACTIVE'}<Button variant="destructive" size="sm" onclick={() => requestPositionDeactivate(selectedPosition())}>Nonaktifkan</Button>{:else}<Button variant="outline" size="sm" onclick={() => activatePositionDemo(selectedPosition())}>Aktifkan</Button>{/if}</div></div>
								</div>
								<div class="position-facts">
									<div><span>Organisasi</span><strong>{selectedPosition().orgUnitName}</strong></div>
									<div><span>Level</span><strong>{selectedPosition().level}</strong></div>
									<div><span>Jalur</span><strong>{positionTrackLabel(selectedPosition().track)}</strong></div>
									<div><span>Cakupan</span><strong>{selectedPosition().scopeLabel}</strong></div>
								</div>

								<div class="position-kpi-heading"><div><p class="eyebrow">INDIKATOR AKTIF</p><h4>KPI yang diwariskan</h4></div><span>{positionMappingItems(selectedPosition()).length} indikator</span></div>
								{#if positionMappingItems(selectedPosition()).length === 0}
									<div class="position-mapping-empty" role="status"><strong>Belum ada mapping KPI Aktif</strong><p>Jabatan ini belum memiliki indikator yang siap diwariskan ke alur penilaian. Atur mapping melalui layar Penetapan KPI.</p></div>
								{:else}
									<div class="position-kpi-list">
										{#each positionMappingItems(selectedPosition()) as item (item.id)}
											<article class="position-kpi-item">
												<div class="position-kpi-item-heading"><div><span>{item.code} / {item.group}</span><h5>{item.name}</h5><small>{item.dataSource}</small></div><span class="position-kpi-state">Aktif</span></div>
												<div class="position-kpi-facts"><div><span>Target</span><strong>{selectedPosition().targets[item.id] ?? formatNumber(item.target, 2)} {item.unit}</strong></div><div><span>Bobot</span><strong>{selectedPosition().weights[item.id] ?? formatNumber(item.weight * 100, 2)}%</strong></div><div><span>Status katalog</span><strong>{catalogStateLabel(item.state)}</strong></div></div>
											</article>
										{/each}
									</div>
									<div class="position-mapping-total"><span>Total bobot mapping</span><strong>{formatNumber(positionMappingTotal(selectedPosition()), 2)}%</strong><small>Snapshot demo, tidak dapat diedit dari layar ini.</small></div>
								{/if}
								{#if positionDeactivateId === selectedPosition().id}
									<div class="position-deactivate-confirm" role="alert"><div><strong>Nonaktifkan jabatan ini?</strong><p>Jabatan tetap tersimpan, tetapi tidak lagi menjadi pilihan aktif untuk mapping berikutnya.</p></div><div class="position-confirm-actions"><Button variant="outline" size="sm" onclick={cancelPositionDeactivation}>Batal</Button><Button variant="destructive" size="sm" onclick={confirmPositionDeactivation}>Ya, nonaktifkan</Button></div></div>
								{/if}
								<p class="position-detail-footnote">Target dan bobot di atas adalah inherited read-only. Perubahan kebijakan dilakukan Admin melalui Penetapan KPI dan akan menjadi langkah berikutnya untuk API.</p>
							</article>
						</div>

						{#if positionEditorOpen}
							<section class="position-editor-panel" aria-labelledby="position-editor-title">
				<div class="position-editor-heading"><div><p class="eyebrow">{positionEditorMode === 'CREATE' ? 'BUAT JABATAN' : 'EDIT JABATAN'}</p><h3 id="position-editor-title">{positionEditorTitle()}</h3><p>Perubahan hanya tersimpan di browser selama sesi ini.</p></div><Button variant="outline" size="sm" onclick={closePositionEditor}>Tutup editor</Button></div>
								<form class="position-editor-form" onsubmit={(event) => { event.preventDefault(); savePositionDemo(); }}>
									<div class="position-editor-fields"><label>Nama jabatan<input id="position-form-name" class="text-input" bind:value={positionFormName} placeholder="Contoh: Kepala Sub Bidang Dana" required /></label><label>Level<input class="text-input" bind:value={positionFormLevel} placeholder="Contoh: Supervisor 2" required /></label><label>Organisasi<input class="text-input" bind:value={positionFormOrgUnit} placeholder="Contoh: KCU Utama" required /></label><label>Cakupan kerja<input class="text-input" bind:value={positionFormScope} placeholder="Contoh: Pengawasan dana cabang" required /></label><label>Jalur mapping<select class="text-input" value={positionFormTrack} onchange={(event) => changePositionFormTrack(event.currentTarget.value as DemoPositionTrack)}><option value="BUSINESS">Pemasaran / Bisnis</option><option value="NON_BUSINESS">Operasional / Non-bisnis</option><option value="BOTH">Gabungan Pemasaran + Operasional</option></select></label><label>Status<select class="text-input" bind:value={positionFormStatus}><option value="ACTIVE">Aktif</option><option value="INACTIVE">Nonaktif</option></select></label></div>
									<div class="position-editor-mapping"><div class="position-editor-section-heading"><div><p class="eyebrow">MAPPING KPI</p><h4>Pilih indikator Aktif</h4></div><span>{positionFormSelectedIds.length} dipilih</span></div>{#if positionFormCatalogItems().length === 0}<div class="position-editor-empty" role="status"><strong>Belum ada indikator Aktif</strong><p>Aktifkan indikator dari Katalog KPI sebelum membuat mapping untuk jabatan ini.</p></div>{:else}<div class="position-editor-kpi-list">{#each positionFormCatalogItems() as item (item.id)}<article class="position-editor-kpi" class:selected={positionFormSelectedIds.includes(item.id)}><label class="position-editor-kpi-choice"><input type="checkbox" checked={positionFormSelectedIds.includes(item.id)} onchange={(event) => togglePositionFormItem(item.id, event.currentTarget.checked)} /><span><strong>{item.name}</strong><small>{item.code} / {catalogTrackLabel(item.track)} / {item.unit}</small></span></label>{#if positionFormSelectedIds.includes(item.id)}<div class="position-editor-kpi-fields"><label>Target<input class="text-input" type="number" min="0.01" step="any" value={positionFormTargets[item.id] ?? String(item.target)} oninput={(event) => updatePositionFormTarget(item.id, event.currentTarget.value)} /></label><label>Bobot (%)<input class="text-input" type="number" min="0.01" max="100" step="0.01" value={positionFormWeights[item.id] ?? String(Math.round(item.weight * 10000) / 100)} oninput={(event) => updatePositionFormWeight(item.id, event.currentTarget.value)} /></label></div>{/if}</article>{/each}</div>{/if}</div>
									<div class="position-editor-preview" role="status" aria-live="polite"><div class="position-editor-section-heading"><div><p class="eyebrow">PREVIEW SELF-ASSIGNMENT</p><h4>Yang akan tampil ke staf</h4></div><span>{positionFormSelectedItems().length} KPI</span></div>{#if positionFormSelectedItems().length === 0}<p class="position-editor-preview-empty">Belum ada indikator dipilih. Setelah disimpan, staf akan melihat empty state mapping.</p>{:else}<div class="position-editor-preview-list">{#each positionFormSelectedItems() as item (item.id)}<div><span><strong>{item.name}</strong><small>{item.unit}</small></span><span>Target {positionFormTargets[item.id] ?? item.target} · Bobot {positionFormWeights[item.id] ?? Math.round(item.weight * 10000) / 100}%</span></div>{/each}</div>{/if}<p class="position-editor-preview-footnote">Preview ini mengikuti perubahan form saat ini dan baru menjadi sumber self-assignment setelah tombol simpan digunakan.</p></div>
				<div class="position-editor-footer"><p><strong>{positionFormWeightStatus()}</strong><span>Total bobot {formatNumber(positionFormWeightTotal(), 2)}% dari 100%.</span><small class="position-editor-preview-note">Preview self-assignment berubah setelah disimpan.</small></p><div><Button type="button" variant="outline" onclick={closePositionEditor}>Batal</Button><Button type="submit">Simpan jabatan</Button></div></div>
								</form>
							</section>
						{/if}
								<p class="position-mapping-footnote">Master jabatan ini membatasi konteks organisasi pada KCU Utama.</p>
					</section>
				{/if}

				{#if view === 'catalog' && canManageCatalog()}
					<section class="catalog-panel" aria-labelledby="catalog-title">
						<div class="catalog-heading">
							<div>
								<p class="eyebrow">ADMIN KPI / SDM</p>
								<h2 id="catalog-title">Katalog KPI dan target</h2>
								<p class="muted-copy">Kelola indikator yang boleh dipilih staf pada jalur Pemasaran dan Operasional.</p>
							</div>
							<div class="catalog-demo-note"><strong>KCU Utama</strong><span>Perubahan hanya tersimpan di browser.</span></div>
						</div>

						<div class="catalog-summary" aria-label="Ringkasan katalog KPI">
							<div><span>Total indikator</span><strong>{demoCatalogItems.length}</strong><small>Semua jalur</small></div>
							<div><span>Aktif</span><strong>{demoCatalogItems.filter((item) => item.state === 'ACTIVE').length}</strong><small>Siap dipakai self-assignment</small></div>
							<div><span>Draft</span><strong>{demoCatalogItems.filter((item) => item.state === 'DRAFT').length}</strong><small>Masih perlu ditinjau</small></div>
						</div>

						<div class="catalog-toolbar" aria-label="Filter katalog KPI">
							<label>Jalur<select class="text-input" bind:value={catalogTrackFilter}><option value="ALL">Semua jalur</option><option value="BUSINESS">Pemasaran / Bisnis</option><option value="NON_BUSINESS">Operasional / Non-bisnis</option></select></label>
							<label>Status<select class="text-input" bind:value={catalogStateFilter}><option value="ALL">Semua status</option><option value="ACTIVE">Aktif</option><option value="DRAFT">Draft</option></select></label>
							<div class="catalog-visible-count"><span>Menampilkan</span><strong>{visibleCatalogItems().length} indikator</strong></div>
						</div>

						<div class="catalog-layout">
							<section class="catalog-list-panel" aria-labelledby="catalog-list-title">
								<div class="catalog-section-heading"><div><p class="eyebrow">KATALOG KPI</p><h3 id="catalog-list-title">Indikator tersedia</h3></div><span>Target dan bobot contoh</span></div>
								{#if visibleCatalogItems().length === 0}
									<div class="catalog-empty" role="status"><strong>Tidak ada indikator sesuai filter</strong><p>Ganti jalur atau status untuk melihat indikator lain.</p></div>
								{:else}
									<div class="catalog-items">
										{#each visibleCatalogItems() as item (item.id)}
											<article class="catalog-item">
												<div class="catalog-item-heading"><div><span class="catalog-item-code">{item.code} / {item.group}</span><h4>{item.name}</h4><p>{catalogTrackLabel(item.track)} / {catalogTypeLabel(item.type)}</p></div><span class={'catalog-state catalog-state-' + item.state.toLowerCase()}>{catalogStateLabel(item.state)}</span></div>
												<div class="catalog-item-facts"><div><span>Target</span><strong>{formatNumber(item.target, 2)} {item.unit}</strong></div><div><span>Bobot</span><strong>{formatNumber(item.weight * 100, 2)}%</strong></div><div><span>Sumber data</span><strong>{item.dataSource}</strong></div></div>
											</article>
										{/each}
									</div>
								{/if}
							</section>

							<form class="catalog-form" onsubmit={(event) => { event.preventDefault(); saveDemoCatalogItem(); }}>
								<div class="catalog-section-heading"><div><p class="eyebrow">KATALOG KPI</p><h3>Tambah indikator</h3></div><span>Belum tersambung</span></div>
								<label class="catalog-field">Jalur<select class="text-input" bind:value={catalogFormTrack}><option value="BUSINESS">Pemasaran / Bisnis</option><option value="NON_BUSINESS">Operasional / Non-bisnis</option></select></label>
								<div class="catalog-field-grid"><label class="catalog-field">Kode <span class="catalog-optional">Opsional</span><input class="text-input" bind:value={catalogFormCode} placeholder="Contoh: KPI-P-05" /></label><label class="catalog-field">Kelompok<input class="text-input" bind:value={catalogFormGroup} placeholder="Contoh: Dana" required /></label></div>
								<label class="catalog-field">Nama indikator<input class="text-input" bind:value={catalogFormName} placeholder="Contoh: Aktivitas layanan nasabah" required /></label>
								<div class="catalog-field-grid"><label class="catalog-field">Tipe<select class="text-input" bind:value={catalogFormType}><option value="Q">Kuantitatif</option><option value="N">Numerik durasi</option><option value="L">Batas maksimum</option><option value="Z">Zero tolerance</option></select></label><label class="catalog-field">Unit<input class="text-input" bind:value={catalogFormUnit} placeholder="%, rekening, menit" required /></label></div>
								<div class="catalog-field-grid"><label class="catalog-field">Target<input class="text-input" type="number" min={catalogFormType === 'Z' ? 0 : 0.01} max={['Q', 'L'].includes(catalogFormType) ? 100 : undefined} step="any" bind:value={catalogFormTarget} placeholder="100" required /></label><label class="catalog-field">Bobot (%)<input class="text-input" type="number" min="0.01" max="100" step="0.01" bind:value={catalogFormWeight} placeholder="20" required /></label></div>
								<label class="catalog-field">Sumber data<input class="text-input" bind:value={catalogFormSource} placeholder="Contoh: Rekap sistem operasional" required /></label>
								<label class="catalog-field">State awal<select class="text-input" bind:value={catalogFormState}><option value="DRAFT">Draft</option><option value="ACTIVE">Aktif</option></select></label>
								<div class="catalog-form-footer"><p><strong>Belum tersambung API</strong><span>Perubahan belum disimpan ke server.</span></p><Button type="submit">Simpan indikator</Button></div>
							</form>
						</div>
						<p class="catalog-footnote">Katalog aktif menjadi sumber self-assignment. Target dan bobot di layar ini masih contoh desain, belum menjadi kebijakan KPI resmi.</p>
					</section>
				{/if}

				{#if view === 'monitoring' && canViewMonitoring()}
					<section class="monitoring-panel" aria-labelledby="monitoring-title">
						<div class="monitoring-heading">
							<div>
								<p class="eyebrow">ADMIN KPI / SDM</p>
								<h2 id="monitoring-title">Monitoring Admin KPI</h2>
								<p class="muted-copy">Pantau kesehatan periode aktif sebelum tugas bergerak ke review dan finalisasi.</p>
							</div>
							<div class="monitoring-period-tools"><label class="admin-period-selector">Filter periode<select class="text-input" bind:value={adminPeriodFilter}><option value="ALL">Semua periode</option>{#each demoPeriods as period (period.id)}<option value={period.id}>{period.name}</option>{/each}</select></label><div class="monitoring-period-context"><span>PERIODE TERPILIH</span><strong>{selectedAdminPeriodLabel()}</strong><small>{selectedAdminPeriodDateRange()}</small></div></div>
						</div>
						{#if monitoringDrilldownFocus}
							<div class="monitoring-drilldown-focus" role="status"><div><span class="dashboard-label">KONTEKS DARI PERBANDINGAN</span><strong>{selectedAdminPeriodLabel()} · {monitoringDrilldownFocus.status === 'FINAL' ? 'Final' : statusNames[monitoringDrilldownFocus.status]}</strong><p>{monitoringDrilldownFocus.track === 'ALL' ? 'Semua jalur' : monitoringTrackLabel(monitoringDrilldownFocus.track)} · daftar kontrak sudah difilter dari kartu perbandingan.</p></div><Button variant="outline" size="sm" onclick={openMonitoring}>Lihat semua monitoring</Button></div>
		{/if}

						<div class="monitoring-stat-grid" aria-label="Ringkasan monitoring KPI">
							<div class="monitoring-stat-card"><span>Total kontrak</span><strong>{monitoringRecordsForPeriod().length}</strong><small>KCU Utama · {selectedAdminPeriodLabel()}</small></div>
							<div class="monitoring-stat-card monitoring-stat-warn"><span>Menunggu review</span><strong>{monitoringStatusCount('SUBMITTED')}</strong><small>Perlu keputusan Kepala Cabang</small></div>
							<div class="monitoring-stat-card monitoring-stat-danger"><span>Melewati deadline</span><strong>{monitoringOverdueCount()}</strong><small>Perlu perhatian Admin</small></div>
							<div class="monitoring-stat-card monitoring-stat-ready"><span>Siap finalisasi</span><strong>{monitoringStatusCount('VERIFIED')}</strong><small>Sudah diverifikasi reviewer</small></div>
						</div>

						<div class="monitoring-overview-grid">
							<section class="monitoring-breakdown-card" aria-labelledby="monitoring-status-title">
							<div class="monitoring-card-heading"><div><p class="eyebrow">STATUS KONTRAK</p><h3 id="monitoring-status-title">Distribusi status</h3></div><span>{monitoringRecordsForPeriod().length} contoh</span></div>
								<div class="monitoring-status-list">
									{#each ['DRAFT', 'SUBMITTED', 'REVISION', 'VERIFIED', 'FINAL'] as status}
										<div class="monitoring-status-row"><span><i class={'monitoring-status-dot monitoring-status-' + status.toLowerCase()}></i>{statusNames[status]}</span><strong>{monitoringStatusCount(status as MonitoringStatus)}</strong></div>
									{/each}
								</div>
							</section>
							<section class="monitoring-breakdown-card" aria-labelledby="monitoring-track-title">
								<div class="monitoring-card-heading"><div><p class="eyebrow">JALUR KERJA</p><h3 id="monitoring-track-title">Pemasaran dan Operasional</h3></div><span>{monitoringRecordsForPeriod().length} contoh</span></div>
								<div class="monitoring-track-list">
									<div class="monitoring-track-row"><div><strong>Pemasaran / Bisnis</strong><small>{monitoringTrackCount('BUSINESS')} kontrak</small></div><span class="monitoring-track-bar"><i style={'width: ' + (monitoringRecordsForPeriod().length ? (monitoringTrackCount('BUSINESS') / monitoringRecordsForPeriod().length) * 100 : 0) + '%'}></i></span></div>
									<div class="monitoring-track-row"><div><strong>Operasional / Non-bisnis</strong><small>{monitoringTrackCount('NON_BUSINESS')} kontrak</small></div><span class="monitoring-track-bar"><i style={'width: ' + (monitoringRecordsForPeriod().length ? (monitoringTrackCount('NON_BUSINESS') / monitoringRecordsForPeriod().length) * 100 : 0) + '%'}></i></span></div>
								</div>
							<p class="monitoring-card-footnote">Cakupan saat ini hanya KCU Utama.</p>
							</section>
						</div>

						<section class="monitoring-table-panel" aria-labelledby="monitoring-table-title">
							<div class="monitoring-table-heading"><div><p class="eyebrow">TINDAKAN ADMIN</p><h3 id="monitoring-table-title">Kontrak terfilter</h3><p class="muted-copy">Gunakan filter untuk melihat kontrak dari periode, jalur, dan status yang dipilih.</p></div><span class="monitoring-table-count">{visibleMonitoringRecords().length} kontrak</span></div>
							<div class="monitoring-filters" aria-label="Filter monitoring kontrak">
								<label>Jalur<select class="text-input" bind:value={monitoringTrackFilter}><option value="ALL">Semua jalur</option><option value="BUSINESS">Pemasaran / Bisnis</option><option value="NON_BUSINESS">Operasional / Non-bisnis</option></select></label>
								<label>Status<select class="text-input" bind:value={monitoringStatusFilter}><option value="ALL">Semua status aktif</option><option value="DRAFT">Draf</option><option value="SUBMITTED">Diajukan</option><option value="REVISION">Revisi</option><option value="VERIFIED">Terverifikasi</option><option value="FINAL">Final</option></select></label>
							</div>
							{#if visibleMonitoringRecords().length === 0}
								<div class="monitoring-empty" role="status"><strong>Tidak ada penilaian sesuai filter</strong><p>Periode, jalur, atau status ini belum memiliki penilaian.</p></div>
							{:else}
								<div class="table-scroll monitoring-table-scroll">
									<table class="monitoring-table"><thead><tr><th scope="col">Pegawai</th><th scope="col">Jalur / periode</th><th scope="col">Status</th><th scope="col">Deadline</th><th scope="col">Perhatian</th><th scope="col"><span class="sr-only">Audit</span></th></tr></thead><tbody>
										{#each visibleMonitoringRecords() as record (record.id)}
											<tr><td><strong class="table-primary">{record.employeeName}</strong><span class="table-secondary">{record.positionName}</span></td><td><strong class="table-primary">{monitoringTrackLabel(record.track)}</strong><span class="table-secondary">{record.periodName}</span></td><td><Badge variant={statusVariant(record.status)}>{statusNames[record.status]}</Badge></td><td><span class={'monitoring-deadline ' + monitoringAgeTone(record)}>{formatDate(record.dueAt)}</span><span class="table-secondary">{record.submittedAt ? 'Diajukan ' + formatDate(record.submittedAt) : 'Belum diajukan'}</span></td><td><strong class="monitoring-attention">{record.attention}</strong><span class="table-secondary">{record.score === null ? 'Skor belum dihitung' : 'Skor KPI ' + formatNumber(record.score, 1)}</span></td><td class="monitoring-table-action"><Button variant="outline" size="sm" aria-label={'Lihat audit ' + record.employeeName} onclick={() => openAuditForMonitoring(record)}>Lihat audit</Button></td></tr>
										{/each}
									</tbody></table>
								</div>
							{/if}
						</section>
							<p class="monitoring-footnote">Ringkasan ini menampilkan progres penilaian di KCU Utama.</p>
					</section>
				{/if}

				{#if view === 'auditLog' && canViewAuditTrail()}
					<section class="audit-log-panel" aria-labelledby="audit-log-title">
						<div class="audit-log-heading">
							<div>
								<p class="eyebrow">ADMIN KPI / SDM</p>
								<h2 id="audit-log-title">Audit log</h2>
								<p class="muted-copy">Catatan akses menu dan perubahan penilaian seluruh pengguna.</p>
							</div>
							<strong>{adminAuditMeta.total} kejadian</strong>
						</div>
						<form class="audit-log-filters" onsubmit={(event) => { event.preventDefault(); void loadAdminAuditLog(1); }}>
							<label>Pengguna<input class="text-input" bind:value={adminAuditSearch} placeholder="Nama atau ID pegawai" /></label>
							<label>Aktivitas<select class="text-input" bind:value={adminAuditAction}><option value="">Semua aktivitas</option><option value="MENU_ACCESSED">Akses menu</option><option value="API_ACTION">Perubahan data</option></select></label>
							<label>Dari<input class="text-input" type="date" bind:value={adminAuditFrom} /></label>
							<label>Sampai<input class="text-input" type="date" bind:value={adminAuditTo} /></label>
							<Button size="sm" type="submit" disabled={adminAuditLoading}>{adminAuditLoading ? 'Memuat…' : 'Terapkan filter'}</Button>
						</form>
						{#if adminAuditFailed}
							<div class="audit-log-empty" role="alert"><strong>Audit log belum dapat dimuat</strong><p>Periksa koneksi, lalu coba muat ulang.</p><Button size="sm" variant="outline" onclick={() => void loadAdminAuditLog(adminAuditMeta.page)}>Muat ulang</Button></div>
						{:else if adminAuditLoading && adminAuditEvents.length === 0}
							<p class="audit-log-state" role="status">Memuat aktivitas pengguna…</p>
						{:else if adminAuditEvents.length === 0}
							<div class="audit-log-empty"><strong>Belum ada aktivitas</strong><p>Aktivitas menu dan perubahan penilaian akan muncul di sini.</p></div>
						{:else}
							<div class="audit-log-table-wrap">
								<table class="audit-log-table">
									<thead><tr><th>Pengguna</th><th>Aktivitas</th><th>Menu / data</th><th>Waktu</th><th>Catatan</th></tr></thead>
									<tbody>
										{#each adminAuditEvents as event (event.id)}
											<tr>
												<td><strong>{event.actor_name}</strong><small>{event.actor_id}{event.actor_position_name ? ` · ${event.actor_position_name}` : ''}{event.actor_org_unit_name ? ` · ${event.actor_org_unit_name}` : ''}</small></td>
												<td>{event.action === 'MENU_ACCESSED' ? 'Membuka menu' : event.action === 'API_ACTION' ? `Perubahan data · ${String(event.after?.method ?? '')}` : auditActionLabel(event.action)}</td>
												<td>{event.object_type === 'MENU' ? String(event.after?.menu_label ?? event.object_id) : event.object_type === 'API_ENDPOINT' ? event.object_id.replace('api/v1/', '') : `${event.object_type} · ${event.object_id}`}</td>
												<td>{formatDateTime(event.occurred_at)}</td>
												<td>{event.reason ?? auditChangeLabel(event) ?? (event.object_type === 'MENU' && event.after?.previous_menu ? `Dari ${String(event.after.previous_menu_label ?? event.after.previous_menu)}` : '—')}</td>
											</tr>
										{/each}
									</tbody>
								</table>
							</div>
							<div class="audit-log-pagination"><span>Halaman {adminAuditMeta.page} dari {adminAuditMeta.last_page}</span><div><Button size="sm" variant="outline" disabled={adminAuditLoading || adminAuditMeta.page <= 1} onclick={() => void loadAdminAuditLog(adminAuditMeta.page - 1)}>Sebelumnya</Button><Button size="sm" variant="outline" disabled={adminAuditLoading || adminAuditMeta.page >= adminAuditMeta.last_page} onclick={() => void loadAdminAuditLog(adminAuditMeta.page + 1)}>Berikutnya</Button></div></div>
						{/if}
					</section>
				{/if}

				{#if view === 'audit' && canViewAuditTrail()}
					<section class="audit-trail-panel" aria-labelledby="audit-trail-title">
						<div class="audit-trail-heading">
							<div>
								<p class="eyebrow">ADMIN KPI / SDM</p>
								<h2 id="audit-trail-title">Audit trail keputusan</h2>
								<p class="muted-copy">Baca jejak verifikasi reviewer dan penguncian final tanpa mengubah snapshot penilaian.</p>
							</div>
							<div class="audit-trail-tools"><label class="admin-period-selector">Filter periode<select class="text-input" bind:value={adminPeriodFilter}><option value="ALL">Semua periode</option>{#each demoPeriods as period (period.id)}<option value={period.id}>{period.name}</option>{/each}</select></label><div class="audit-trail-context"><span>PERIODE TERPILIH</span><strong>{visibleAuditTrailEntries().length} kejadian</strong><small>{selectedAdminPeriodLabel()} · KCU Utama</small></div></div>
						</div>
						{#if auditTrailFocus}
							<div class="audit-trail-focus" role="status"><div><span class="dashboard-label">{auditTrailFocus.origin === 'COMPARISON_DRILLDOWN' ? 'KONTEKS DARI DRILL-DOWN' : 'KONTEKS DARI MONITORING'}</span><strong>{auditTrailFocus.employeeName}</strong><p>{monitoringTrackLabel(auditTrailFocus.track)} Â· {auditTrailFocus.periodName} Â· Status monitoring {statusNames[auditTrailFocus.status]}</p></div><Button variant="outline" size="sm" onclick={openAuditTrail}>Lihat semua audit</Button></div>
						{/if}
						{#if auditContractSummary()}
							<section class="audit-contract-summary" aria-labelledby="audit-contract-summary-title">
								<div class="audit-contract-summary-heading">
									<div>
										<p class="eyebrow">RINGKASAN KONTRAK · READ-ONLY</p>
										<h3 id="audit-contract-summary-title">{auditContractSummary()?.employeeName}</h3>
										<p>{auditContractSummary()?.positionName}</p>
									</div>
									<Badge variant={statusVariant(auditContractSummary()?.status ?? 'DRAFT')}>{statusNames[auditContractSummary()?.status ?? 'DRAFT']}</Badge>
								</div>
								<div class="audit-contract-summary-facts">
									<div><span>Jalur</span><strong>{monitoringTrackLabel(auditContractSummary()?.track ?? 'BUSINESS')}</strong></div>
									<div><span>Periode</span><strong>{auditContractSummary()?.periodName}</strong></div>
									<div><span>Skor KPI</span><strong>{auditContractSummary()?.score === null ? 'Belum dihitung' : formatNumber(auditContractSummary()?.score ?? null, 1)}</strong></div>
									<div><span>Reviewer</span><strong>{auditContractSummary()?.reviewerName ?? 'Belum tersedia'}</strong></div>
									<div><span>Diajukan</span><strong>{auditContractSummary()?.submittedAt ? formatDate(auditContractSummary()?.submittedAt ?? '') : 'Belum tersedia'}</strong></div>
									<div><span>Final</span><strong>{auditContractSummary()?.finalDate ? formatDate(auditContractSummary()?.finalDate ?? '') : 'Belum final'}</strong></div>
								</div>
								<p class="audit-contract-summary-note">Ringkasan ini adalah snapshot read-only. Perubahan dilakukan melalui workflow penilaian yang berwenang.</p>
							</section>
						{/if}

						<div class="audit-trail-filters" aria-label="Filter audit trail">
							<label>Jalur<select class="text-input" bind:value={auditTrackFilter}><option value="ALL">Semua jalur</option><option value="BUSINESS">Pemasaran / Bisnis</option><option value="NON_BUSINESS">Operasional / Non-bisnis</option></select></label>
							<label>Status<select class="text-input" bind:value={auditStatusFilter}><option value="ALL">Semua keputusan</option><option value="VERIFIED">Terverifikasi</option><option value="FINAL">Final</option></select></label>
						</div>

						{#if visibleAuditTrailEntries().length === 0}
							<div class="audit-trail-empty" role="status"><strong>Belum ada audit trail sesuai filter</strong><p>Perubahan filter akan menampilkan kejadian yang tersedia.</p></div>
						{:else}
							<div class="audit-trail-layout">
								<div class="audit-trail-list" aria-label="Daftar audit trail keputusan">
									{#each visibleAuditTrailEntries() as entry (entry.id)}
										<button type="button" class="audit-trail-row" class:selected={selectedAuditTrail()?.id === entry.id} onclick={() => (selectedAuditTrailId = entry.id)}>
											<div class="audit-trail-row-heading"><div><strong>{entry.employeeName}</strong><small>{entry.positionName} / {monitoringTrackLabel(entry.track)}</small></div><Badge variant="secondary">{statusNames[entry.status]}</Badge></div>
											<p>{entry.periodName}</p>
											<div class="audit-trail-row-meta"><span>{entry.actorName}</span><span>{formatDateTime(entry.actedAt)}</span></div>
										</button>
									{/each}
								</div>

								{#if selectedAuditTrail()}
									<article class="audit-trail-detail" aria-labelledby="audit-trail-detail-title">
										<div class="audit-trail-detail-heading"><div><p class="eyebrow">DETAIL KEJADIAN</p><h3 id="audit-trail-detail-title">{selectedAuditTrail()?.employeeName}</h3><p>{selectedAuditTrail()?.positionName} / {monitoringTrackLabel(selectedAuditTrail()?.track ?? 'BUSINESS')}</p></div><Badge variant="secondary">{statusNames[selectedAuditTrail()?.status ?? 'VERIFIED']}</Badge></div>
										<div class="audit-trail-facts">
											<div><span>Periode</span><strong>{selectedAuditTrail()?.periodName}</strong></div>
											<div><span>Jalur</span><strong>{monitoringTrackLabel(selectedAuditTrail()?.track ?? 'BUSINESS')}</strong></div>
											<div><span>Aktor</span><strong>{selectedAuditTrail()?.actorName}</strong><small>{selectedAuditTrail()?.actorRole}</small></div>
											<div><span>Waktu kejadian</span><strong>{formatDateTime(selectedAuditTrail()?.actedAt ?? null)}</strong></div>
										</div>
										<div class="audit-trail-note"><strong>Catatan keputusan</strong><p>{selectedAuditTrail()?.note}</p></div>
										<div class="audit-trail-snapshot"><div class="audit-trail-snapshot-heading"><div><p class="eyebrow">SNAPSHOT READ-ONLY</p><h4>Indikator saat keputusan</h4></div><span>{selectedAuditTrail()?.items.length ?? 0} indikator</span></div>
												<div class="audit-trail-items-heading"><span>Indikator</span><span>Target</span><span>Realisasi</span><span>Skor</span></div>
												{#each selectedAuditTrail()?.items ?? [] as item (item.code)}
													<div class="audit-trail-item"><div><strong>{item.name}</strong><small>{item.code} Â· {item.unit}</small></div><span>{item.target}</span><span>{item.actual}</span><strong>{item.score === null ? 'Belum dihitung' : formatNumber(item.score, 1)}</strong></div>
												{/each}
											</div>
										<p class="audit-trail-footnote">Riwayat ini hanya untuk membaca jejak keputusan. Perubahan data dilakukan melalui workflow yang berwenang.</p>
									</article>
								{/if}
							</div>
						{/if}
					</section>
				{/if}

				{#if view === 'comparison' && canViewPeriodComparison()}
					<section class="period-comparison-panel" aria-labelledby="period-comparison-title">
						<div class="period-comparison-heading">
							<div>
								<p class="eyebrow">ADMIN KPI / SDM</p>
								<h2 id="period-comparison-title">Bandingkan dua periode</h2>
								<p class="muted-copy">Baca perubahan status kontrak dan jumlah final antar-periode tanpa mengubah data penilaian.</p>
							</div>
							<span class="period-comparison-badge">READ-ONLY · PERBANDINGAN</span>
						</div>

						<div class="period-comparison-controls" aria-label="Pilih periode pembanding">
							<label>Periode A<select class="text-input" bind:value={comparisonPeriodA}>{#each demoPeriods as period (period.id)}<option value={period.id}>{period.name}</option>{/each}</select></label>
							<span class="period-comparison-vs" aria-hidden="true">vs</span>
							<label>Periode B<select class="text-input" bind:value={comparisonPeriodB}>{#each demoPeriods as period (period.id)}<option value={period.id}>{period.name}</option>{/each}</select></label>
						</div>

						{#if comparisonPeriodA === comparisonPeriodB}
							<div class="period-comparison-notice" role="status"><strong>Pilih dua periode berbeda</strong><p>Perbandingan membutuhkan dua periode agar perubahan status dapat terbaca.</p></div>
						{/if}

						<div class="period-comparison-grid">
							{#each [comparisonPeriodA, comparisonPeriodB] as periodId, index}
								<article class="period-comparison-card">
									<div class="period-comparison-card-heading"><div><p class="eyebrow">PERIODE {index === 0 ? 'A' : 'B'}</p><h3>{comparisonPeriodFor(periodId)?.name ?? 'Periode belum tersedia'}</h3><span>{comparisonPeriodFor(periodId) ? `${periodDateLabel(comparisonPeriodFor(periodId)?.startsAt ?? '')} - ${periodDateLabel(comparisonPeriodFor(periodId)?.endsAt ?? '')}` : 'Belum ada ringkasan'}</span></div>{#if comparisonPeriodFor(periodId)}<span class={'period-status-chip period-status-' + (comparisonPeriodFor(periodId)?.status ?? 'DRAFT').toLowerCase()}>{periodStatusNames[comparisonPeriodFor(periodId)?.status ?? 'DRAFT']}</span>{/if}</div>
									{#if comparisonSummaryFor(periodId)}
							<div class="period-comparison-total"><span>Total kontrak</span><strong>{comparisonSummaryFor(periodId)?.totalContracts}</strong><small>Ringkasan pada periode ini</small></div>
										<div class="period-comparison-section"><div class="period-comparison-section-heading"><h4>Distribusi status</h4><span>Kontrak</span></div><div class="period-comparison-status-list">{#each ['DRAFT', 'SUBMITTED', 'REVISION', 'VERIFIED', 'FINAL'] as status}<div><span><i class={'monitoring-status-dot monitoring-status-' + status.toLowerCase()}></i>{statusNames[status]}</span><strong>{comparisonStatusCount(periodId, status as MonitoringStatus)}</strong><Button class="period-comparison-action" variant="outline" size="sm" onclick={() => openMonitoringDrilldown(periodId, status as MonitoringStatus)}>Lihat kontrak</Button></div>{/each}</div></div>
										<div class="period-comparison-section"><div class="period-comparison-section-heading"><h4>Final per jalur</h4><span>{comparisonStatusCount(periodId, 'FINAL')} total</span></div><div class="period-comparison-final-list"><div><span>Pemasaran / Bisnis</span><strong>{comparisonFinalCount(periodId, 'BUSINESS')}</strong><Button class="period-comparison-action" variant="outline" size="sm" onclick={() => openMonitoringDrilldown(periodId, 'FINAL', 'BUSINESS')}>Lihat kontrak</Button></div><div><span>Operasional / Non-bisnis</span><strong>{comparisonFinalCount(periodId, 'NON_BUSINESS')}</strong><Button class="period-comparison-action" variant="outline" size="sm" onclick={() => openMonitoringDrilldown(periodId, 'FINAL', 'NON_BUSINESS')}>Lihat kontrak</Button></div></div></div>
									{:else}
										<div class="period-comparison-empty"><strong>Belum ada snapshot perbandingan</strong><p>Periode ini tersedia pada katalog periode, tetapi belum memiliki ringkasan.</p></div>
									{/if}
								</article>
							{/each}
						</div>

						{#if comparisonPeriodA !== comparisonPeriodB && comparisonSummaryFor(comparisonPeriodA) && comparisonSummaryFor(comparisonPeriodB)}
							<section class="period-comparison-delta" aria-labelledby="period-comparison-delta-title"><div><p class="eyebrow">PERUBAHAN A KE B</p><h3 id="period-comparison-delta-title">Ringkasan pergeseran</h3><p>Angka positif berarti Periode B memiliki jumlah lebih tinggi daripada Periode A.</p></div><div class="period-comparison-delta-grid"><div><span>Total kontrak</span><strong>{comparisonDelta(comparisonPeriodA, comparisonPeriodB, 'total')}</strong></div><div><span>Total final</span><strong>{comparisonDelta(comparisonPeriodA, comparisonPeriodB, 'final')}</strong></div><div><span>Final Pemasaran</span><strong>{comparisonDelta(comparisonPeriodA, comparisonPeriodB, 'businessFinal')}</strong></div><div><span>Final Operasional</span><strong>{comparisonDelta(comparisonPeriodA, comparisonPeriodB, 'nonBusinessFinal')}</strong></div></div></section>
						{/if}
						<p class="period-comparison-footnote">Ringkasan ini sintetis untuk prototipe UI. Detail keputusan tetap dibaca dari Monitoring dan Audit trail pada periode yang dipilih.</p>
					</section>
				{/if}

				{#if view === 'periods'}
					<section class="period-admin-panel" aria-labelledby="period-admin-title">
						<div class="period-admin-heading">
							<div>
								<p class="eyebrow">ADMIN KPI / SDM</p>
								<h2 id="period-admin-title">Atur periode penilaian</h2>
								<p class="muted-copy">Tentukan siklus penilaian. Realisasi KPI yang diisi memakai durasi yang sama sebelum tanggal mulai siklus.</p>
							</div>
							<div class="period-admin-note"><strong>Durasi tersedia</strong><span>1, 3, 6, atau 12 bulan</span></div>
						</div>

						<div class="period-admin-grid">
							<form class="period-form" onsubmit={(event) => { event.preventDefault(); saveDemoPeriod(); }}>
								<div class="period-form-section">
									<label class="field-label" for="period-name">Nama periode</label>
									<input id="period-name" class="text-input" type="text" bind:value={periodName} placeholder="Contoh: Triwulan I 2027" />
									<p class="field-help">Nama ini akan tampil pada header periode dan laporan.</p>
								</div>

								<div class="period-field-grid">
									<label class="period-field" for="period-cadence">
										<span>Cadence periode</span>
										<select id="period-cadence" class="text-input" bind:value={periodCadenceMonths}>
											<option value={1}>1 bulan</option>
											<option value={3}>3 bulan (triwulan)</option>
											<option value={6}>6 bulan (semester)</option>
											<option value={12}>12 bulan (tahunan)</option>
										</select>
									</label>
									<label class="period-field" for="period-start">
										<span>Tanggal mulai</span>
										<input id="period-start" class="text-input" type="date" bind:value={periodStartDate} />
									</label>
								</div>

								<fieldset class="period-status-field">
									<legend>Status awal periode</legend>
									<div class="period-status-options" role="group" aria-label="Status awal periode">
										{#each periodStatusOptions as status}
											<button type="button" class:active={periodStatus === status} aria-pressed={periodStatus === status} onclick={() => (periodStatus = status)}>
												<strong>{periodStatusNames[status]}</strong>
												<span>{status === 'DRAFT' ? 'Masih disiapkan' : status === 'OPEN' ? 'Siap diproses' : 'Pengisian selesai'}</span>
											</button>
										{/each}
									</div>
								</fieldset>

								<div class="period-form-footer">
									<p><strong>Simpan pengaturan periode</strong><span>Rentang tanggal dihitung dari durasi yang dipilih.</span></p>
									<Button type="submit" class="period-save-button" disabled={busy}>{busy ? 'Menyimpan...' : 'Simpan periode'}</Button>
								</div>
							</form>

							<aside class="period-preview" aria-labelledby="period-preview-title">
								<div class="period-preview-heading">
									<p class="eyebrow">PREVIEW PERIODE</p>
									<span class={'period-status-chip period-status-' + periodStatus.toLowerCase()}>{periodStatusNames[periodStatus]}</span>
								</div>
								<h3 id="period-preview-title">{periodName.trim() || 'Nama periode belum diisi'}</h3>
								<p class="period-preview-cadence">{periodCadenceLabel(periodCadenceMonths)}</p>
								<div class="period-date-range">
									<div><span>Mulai</span><strong>{periodDateLabel(periodStartDate)}</strong></div>
									<div><span>Selesai</span><strong>{periodDateLabel(periodEndDateInput())}</strong></div>
								</div>
								<div class="period-preview-status">
									<strong>{periodStatusNames[periodStatus]}</strong>
									<p>{periodStatusDescription(periodStatus)}</p>
								</div>
								<p class="period-preview-footnote">Periode realisasi KPI: {measurementPeriodRange(periodStartDate, periodCadenceMonths)}. Contoh siklus November bulanan memakai realisasi Oktober.</p>
							</aside>
						</div>

						<section class="period-history" aria-labelledby="period-history-title">
									<div class="period-history-heading">
										<div><p class="eyebrow">PERIODE PENILAIAN</p><h3 id="period-history-title">Periode yang tersedia</h3></div>
								<span class="period-history-count">{configuredPeriods.length} periode</span>
							</div>
							<div class="period-history-list">
								{#each configuredPeriods as period (period.id)}
									<div class="period-history-row">
										<span class="period-history-copy"><strong>{period.name}</strong><small>{periodCadenceLabel(period.cadenceMonths)} · {periodDateLabel(period.startsAt)} – {periodDateLabel(period.endsAt)}</small></span>
										<div class="period-history-actions">
											<span class={'period-status-chip period-status-' + period.status.toLowerCase()}>{periodStatusNames[period.status]}</span>
											<label class="sr-only" for={'period-status-' + period.id}>Status {period.name}</label>
											<select id={'period-status-' + period.id} class="text-input period-status-select" value={cycleStatusDrafts[period.id] ?? period.status} onchange={(event) => setCycleStatusDraft(period.id, event.currentTarget.value as PeriodStatus)}>
												{#each periodStatusOptions as status}<option value={status}>{periodStatusNames[status]}</option>{/each}
											</select>
											<Button size="sm" variant="outline" disabled={busy || (cycleStatusDrafts[period.id] ?? period.status) === period.status} onclick={() => void saveCycleStatus(period)}>Simpan status</Button>
										</div>
									</div>
								{/each}
							</div>
						</section>
					</section>
				{:else if view === 'history'}
					<section class="history-panel" aria-labelledby="history-title">
						<div class="history-heading">
							<div>
				<p class="eyebrow">RIWAYAT SAYA</p>
								<h2 id="history-title">Riwayat penilaian</h2>
								<p class="muted-copy">Status, skor, dan tahapan penilai untuk setiap periode Anda.</p>
							</div>
							<span class="history-scope">{visibleHistoryEntries().length} penilaian</span>
						</div>

						{#if visibleHistoryEntries().length === 0}
							<div class="history-empty" role="status">
									<strong>Belum ada penilaian</strong>
									<p>KPI akan muncul otomatis dari jabatan saat periode penilaian dibuka.</p>
							</div>
						{:else}
							<div class="history-layout">
								<div class="history-list" aria-label="Daftar riwayat penilaian">
									{#each visibleHistoryEntries() as entry (entry.id)}
										<button type="button" class="history-entry" class:selected={selectedHistoryId === entry.id} onclick={() => (selectedHistoryId = entry.id)}>
											<div class="history-entry-heading">
												<strong>{entry.periodName}</strong>
												<Badge variant={statusVariant(entry.status)}>{statusNames[entry.status]}</Badge>
											</div>
											<span>{entry.cadence} · {formatDate(entry.startsAt)} – {formatDate(entry.endsAt)}</span>
							<div class="history-entry-meta"><span>Skor {entry.score === null ? 'belum dihitung' : formatNumber(entry.score, 1)}</span><span>{entry.status === 'DRAFT' && entry.submittedDate ? 'Disusun ' + formatDate(entry.submittedDate) : entry.finalDate ? 'Final ' + formatDate(entry.finalDate) : entry.submittedDate ? 'Diajukan ' + formatDate(entry.submittedDate) : 'Belum diajukan'}</span></div>
										</button>
									{/each}
								</div>

								{#if selectedHistory()}
									{@const historyEntry = selectedHistory()!}
									<article class="history-detail" aria-labelledby="history-detail-title">
										<div class="history-detail-heading">
											<div><p class="eyebrow">DETAIL PERIODE</p><h3 id="history-detail-title">{selectedHistory()?.periodName}</h3></div>
											<Badge variant={statusVariant(selectedHistory()?.status ?? 'DRAFT')}>{statusNames[selectedHistory()?.status ?? 'DRAFT']}</Badge>
										</div>
										<div class="history-facts">
						<div><span>Jabatan</span><strong>{currentUser?.position_name ?? 'Jabatan Anda'}</strong></div>
											<div><span>Rentang</span><strong>{formatDate(selectedHistory()?.startsAt ?? '')} – {formatDate(selectedHistory()?.endsAt ?? '')}</strong></div>
						<div><span>Skor</span><strong>{selectedHistory()?.score === null ? 'Belum dihitung' : formatNumber(selectedHistory()?.score ?? null, 1)}</strong></div>
											<div><span>Reviewer</span><strong>{selectedHistory()?.reviewer ?? 'Belum ditetapkan'}</strong></div>
										</div>
									{#if historyEntry.review_workflow}
										<div class="history-review-progress">
											<strong>Posisi proses penilaian</strong>
											<div class="reviewer-preview-list">
												{#each ['P1', 'P2'] as slot}
													{@const reviewer = historyEntry.review_workflow.reviewer_slots[slot as 'P1' | 'P2']}
													<div><span>Penilai {slot.slice(1)}</span><strong>{reviewer?.name ?? 'Tidak tersedia'}</strong><small>{reviewer?.position_name ?? ''} {reviewer ? '· ' + reviewerSlotStatus(historyEntry, slot as 'P1' | 'P2') : ''}</small></div>
												{/each}
											</div>
										</div>
									{/if}
									{#if historyEntry.review_history?.length}
										<ol class="assessment-review-history-list history-review-list">
											{#each historyEntry.review_history as event, index (`${event.reviewer_employee_id}-${event.created_at ?? index}`)}
												<li><div><strong>{event.reviewer_name}</strong><span>{event.slot ? `Penilai ${event.slot.slice(1)}` : 'Admin'}</span></div><p>{reviewerDecisionLabel(event.decision)} · {event.comment}</p><time>{formatDateTime(event.created_at)}</time></li>
											{/each}
										</ol>
									{/if}
										<div class="history-readonly-note"><strong>Catatan proses</strong><p>{selectedHistory()?.note}</p></div>
										<div class="history-items" aria-label="Indikator pada penilaian">
											<div class="history-items-heading"><span>Indikator</span><span>Target</span><span>Realisasi</span><span>Skor</span></div>
											{#each selectedHistory()?.items ?? [] as item (item.code)}
												<div class="history-item-row"><div><strong>{item.name}</strong><small>{item.code} · {item.unit}</small></div><span>{item.target}</span><span>{item.actual}</span><strong>{item.score === null ? '—' : formatNumber(item.score, 1)}</strong></div>
											{/each}
										</div>
						<p class="history-detail-footnote">Detail ini menampilkan snapshot penilaian pada periode tersebut, termasuk posisi proses dan keputusan reviewer.</p>
									</article>
								{/if}
							</div>
						{/if}
					</section>
				{:else if view === 'assignment'}
					<section class="assignment-panel" aria-labelledby="assignment-title">
						<div class="assignment-heading">
							<div>
								<p class="eyebrow">KPI JABATAN · {staffTrackLabel()}</p>
								<h2 id="assignment-title">Pilih periode pengisian</h2>
								<p class="muted-copy">Periode yang dibuka Admin tampil di sini. Pilih satu periode untuk mulai atau melanjutkan pengisian KPI jabatan Anda.</p>
							</div>
						</div>
						{@render openInputCycleCards()}
					</section>{:else if view === 'mine' && canViewStaffHistory() && !selectedContract}
					<section class="kpi-work-panel" id="staff-workspace-tab-panel" role="tabpanel" tabindex="0" aria-labelledby={'staff-tab-' + staffWorkspaceTab.toLowerCase()}>
						<div class="kpi-work-heading">
							<div>
								<p class="eyebrow">KPI SAYA · {staffTrackLabel()}</p>
								<h2 id="kpi-work-title">{staffWorkspaceTab === 'KPI' ? 'Periode KPI yang perlu diisi' : staffWorkspaceTab === 'BEHAVIOR' ? 'Penilaian perilaku' : staffWorkspaceTab === 'APPROVAL' ? 'Periksa penilaian' : 'Riwayat tindakan'}</h2>
								<p class="muted-copy">{staffWorkspaceTab === 'KPI' ? 'Pilih periode yang dibuka Admin untuk mulai mengisi KPI sesuai jabatan Anda.' : staffWorkspaceTab === 'BEHAVIOR' ? 'Lima indikator perilaku dinilai pada skala 1 sampai 5.' : staffWorkspaceTab === 'APPROVAL' ? 'Periksa skor KPI, perilaku, dan nilai akhir gabungan.' : 'Lihat catatan perubahan dan keputusan pada penilaian Anda.'}</p>
							</div>
							{#if staffWorkspaceTab === 'KPI'}
								<div class="kpi-work-heading-actions">
									{#if openInputCycles().length > 0}<span class="kpi-work-period">{openInputCycles().length} periode dibuka</span>{/if}
									<Button size="sm" variant="outline" disabled={busy} onclick={() => void refreshWorkspace()}>Perbarui daftar</Button>
								</div>
							{/if}
						</div>

						{#if staffWorkspaceTab !== 'KPI'}
							<div class="kpi-work-empty" role="status">
								<strong>{staffWorkspaceTab === 'BEHAVIOR' ? 'Belum ada penilaian perilaku aktif' : staffWorkspaceTab === 'APPROVAL' ? 'Belum ada penilaian untuk diperiksa' : 'Belum ada tindakan pada periode ini'}</strong>
								<p>{staffWorkspaceTab === 'HISTORY' ? 'Riwayat tindakan akan tampil setelah penilaian dibuka atau ada keputusan reviewer.' : staffWorkspaceTab === 'APPROVAL' ? 'Lengkapi KPI dan perilaku untuk melihat nilai gabungannya.' : 'Mulai atau buka penilaian KPI untuk melihat bagian ini.'}</p>
								{#if staffWorkspaceTab === 'HISTORY'}<Button variant="outline" onclick={openHistory}>Buka riwayat saya</Button>{:else}<Button onclick={() => void openSelfAssignment()}>Buka penilaian KPI</Button>{/if}
							</div>
						{:else}
							{@render openInputCycleCards()}
						{/if}
					</section>
				{:else if view === 'reviews' && canReviewInbox()}
					<section class="review-inbox-panel" aria-labelledby="review-inbox-title">
						<div class="review-inbox-heading">
							<div>
								<p class="eyebrow">KEPALA CABANG / REVIEWER</p>
								<h2 id="review-inbox-title">Inbox evaluasi</h2>
								<p class="muted-copy">Tugas review dari jalur Pemasaran dan Operasional yang perlu dibaca Kepala Cabang.</p>
							</div>
							<span class="review-inbox-count">{visibleReviewTasks().length} tugas</span>
						</div>

						<div class="review-filters" aria-label="Filter inbox evaluasi">
							<label>Jalur
								<select class="text-input" bind:value={reviewTrackFilter}>
									<option value="ALL">Semua jalur</option>
									<option value="BUSINESS">Pemasaran / Bisnis</option>
									<option value="NON_BUSINESS">Operasional / Non-bisnis</option>
								</select>
							</label>
							<label>Status
								<select class="text-input" bind:value={reviewStatusFilter}>
									<option value="ALL">Semua status</option>
									<option value="SUBMITTED">Diajukan</option>
									<option value="REVISION">Revisi</option>
								</select>
							</label>
							<label>Usia tugas
								<select class="text-input" bind:value={reviewAgeFilter}>
									<option value="ALL">Semua usia</option>
									<option value="0_3">0-3 hari</option>
									<option value="4_7">4-7 hari</option>
									<option value="8_PLUS">8+ hari</option>
								</select>
							</label>
						</div>

						{#if visibleReviewTasks().length === 0}
							<div class="review-inbox-empty" role="status">
								<strong>Tidak ada tugas sesuai filter</strong>
								<p>Coba longgarkan filter jalur, status, atau usia tugas.</p>
							</div>
						{:else}
							<div class="review-inbox-layout">
								<div class="review-task-list" aria-label="Daftar tugas evaluasi">
									{#each visibleReviewTasks() as task (task.id)}
										<button type="button" class="review-task-row" class:selected={selectedReviewTaskId === task.id} onclick={() => selectReviewTask(task.id)}>
											<div class="review-task-heading">
												<div><strong>{task.employeeName}</strong><small>{task.positionName} / {reviewTrackLabel(task.track)}</small></div>
												<Badge variant={statusVariant(task.status)}>{statusNames[task.status]}</Badge>
											</div>
											<p>{task.periodName} / Diajukan {formatDate(task.submittedAt)}</p>
											<div class="review-task-meta"><span class={'review-age ' + reviewAgeTone(task.ageDays)}>{reviewAgeLabel(task.ageDays)} dalam antrean</span><span>Deadline {formatDate(task.dueAt)}</span></div>
										</button>
									{/each}
								</div>

								{#if selectedReviewTask()}
									<article class="review-task-detail" aria-labelledby="review-task-detail-title">
										<div class="review-task-detail-heading">
											<div><p class="eyebrow">DETAIL TUGAS</p><h3 id="review-task-detail-title">{selectedReviewTask()?.employeeName}</h3><p>{selectedReviewTask()?.positionName} / {reviewTrackLabel(selectedReviewTask()?.track ?? 'BUSINESS')}</p></div>
											<Badge variant={statusVariant(selectedReviewTask()?.status ?? 'SUBMITTED')}>{statusNames[selectedReviewTask()?.status ?? 'SUBMITTED']}</Badge>
										</div>
										<div class="review-task-facts">
											<div><span>Periode</span><strong>{selectedReviewTask()?.periodName}</strong></div>
											<div><span>Usia</span><strong>{reviewAgeLabel(selectedReviewTask()?.ageDays ?? 0)} dalam antrean</strong></div>
											<div><span>Skor KPI</span><strong>{selectedReviewTask()?.score === null ? 'Belum dihitung' : formatNumber(selectedReviewTask()?.score ?? null, 1)}</strong></div>
											<div><span>Deadline</span><strong>{formatDate(selectedReviewTask()?.dueAt ?? '')}</strong></div>
										</div>
				<div class="review-task-note"><strong>{selectedReviewTask()?.lastAction}</strong><p>Periksa capaian, bukti, dan penjelasan staf sebelum memilih keputusan.</p></div>
										<div class="review-item-detail-list" aria-label="Snapshot indikator dan bukti staf">
											{#each selectedReviewTask()?.items ?? [] as item (item.code)}
												<article class="review-item-detail">
													<div class="review-item-heading"><div><span class="review-item-code">{item.code} / {item.unit}</span><h4>{item.name}</h4></div><strong>{item.score === null ? 'Belum dihitung' : formatNumber(item.score, 1)}<small>skor KPI</small></strong></div>
													<div class="review-item-facts">
														<div><span>Target</span><strong>{item.target} {item.unit}</strong></div>
														<div><span>Realisasi</span><strong>{item.actual} {item.unit}</strong></div>
														<div><span>Bukti</span><strong>{item.evidence ?? 'Referensi belum dicatat'}</strong></div>
													</div>
													<div class="review-item-note"><span>Catatan staf</span><p>{item.note ?? 'Staf belum menambahkan catatan.'}</p></div>
												</article>
											{/each}
										</div>

										<div class="review-decision-box">
				<div class="review-decision-heading"><div><p class="eyebrow">KEPUTUSAN REVIEWER</p><h4>Catat keputusan reviewer</h4></div>{#if selectedReviewFinalization()}<Badge variant="secondary">FINAL</Badge>{:else if selectedReviewDecision()}<Badge variant={statusVariant(selectedReviewDecision()?.decision ?? 'VERIFIED')}>{statusNames[selectedReviewDecision()?.decision ?? 'VERIFIED']}</Badge>{/if}</div>
											<label class="review-decision-field" for="review-decision-note">Catatan keputusan reviewer<textarea id="review-decision-note" class="text-input textarea-input" bind:value={reviewDecisionNote} rows="4" placeholder="Tulis alasan yang bisa ditindaklanjuti staf." readonly={Boolean(selectedReviewFinalization())}></textarea></label>
											<p class="review-decision-hint">Catatan wajib diisi. Tombol di bawah menyimpan keputusan penilai.</p>
											{#if !selectedReviewFinalization()}<div class="review-decision-actions"><Button variant="destructive" disabled={!reviewDecisionNote.trim()} onclick={() => saveDemoReviewDecision('REVISION')}>Minta revisi</Button><Button disabled={!reviewDecisionNote.trim()} onclick={() => saveDemoReviewDecision('VERIFIED')}>Verifikasi</Button></div>{/if}
				{#if selectedReviewDecision()}<div class="review-decision-result" role="status"><strong>Keputusan tersimpan</strong><p>{selectedReviewDecision()?.note}</p><small>Disimpan {formatDateTime(selectedReviewDecision()?.savedAt ?? null)}</small></div>{/if}
											{#if selectedReviewDecision()?.decision === 'VERIFIED' && !selectedReviewFinalization()}<div class="review-finalize-actions"><Button onclick={saveDemoFinalization}>Finalisasi dan kunci snapshot</Button><span>Setelah finalisasi, keputusan dan snapshot tidak dapat diubah.</span></div>{/if}
											{#if selectedReviewFinalization()}<div class="review-finalization-result" role="status"><strong>Snapshot sudah final dan terkunci</strong><p>{selectedReviewFinalization()?.note}</p><small>Difinalisasi {formatDateTime(selectedReviewFinalization()?.finalizedAt ?? null)}</small></div>{/if}
										</div>
									</article>
								{/if}
							</div>
						{/if}
					</section>
				{:else}
				{#if !selectedContract}
				<section class="contract-panel" aria-labelledby="contract-list-title">
					<div class="panel-heading">
						<div>
							<p class="eyebrow">PERIODE PENILAIAN</p>
							<h2 id="contract-list-title">{view === 'mine' ? 'Kontrak milik Anda' : view === 'reviews' ? 'Antrean tindakan' : 'Kontrak KPI'}</h2>
							<p class="muted-copy">{view === 'reviews' ? 'Kontrak yang menjadi tanggung jawab reviewer aktif.' : 'Daftar mengikuti identitas dan cakupan akses saat ini.'}</p>
						</div>
						{#if cycles.length > 0}<Badge variant="outline" class="cycle-badge">{cycles.find((cycle) => cycle.status === 'OPEN')?.name ?? cycles[0].name}</Badge>{/if}
					</div>

					{#if visibleContracts().length > 0}
						<div class="table-scroll">
							<table>
								<thead><tr><th scope="col">Pegawai</th><th scope="col">Jabatan dan unit</th><th scope="col">Status</th><th scope="col">Nilai akhir</th><th scope="col"><span class="sr-only">Detail</span></th></tr></thead>
								<tbody>
									{#each visibleContracts() as contract (contract.id)}
										<tr>
											<td><strong class="table-primary">{contract.employee_name ?? contract.org_unit_name}</strong><span class="table-secondary">{contract.id}</span></td>
											<td><strong class="table-primary">{contract.position_name ?? 'Unit kerja'}</strong><span class="table-secondary">{contract.org_unit_name}</span></td>
											<td><Badge variant={statusVariant(contract.status)}>{statusNames[contract.status]}</Badge></td>
										<td><strong class="score-cell">{formatNumber(contract.score?.final_score ?? null, 1)}</strong></td>
											<td class="table-action"><Button variant="outline" size="sm" onclick={() => openContract(contract.id)}>Buka</Button></td>
										</tr>
									{/each}
								</tbody>
							</table>
						</div>
					{:else}
						<div class="empty-state">
							<strong>{view === 'reviews' ? 'Tidak ada kontrak di antrean ini' : 'Belum ada kontrak di cakupan ini'}</strong>
							<p>Pilih profil lain atau buka riwayat penilaian.</p>
							{#if view !== 'overview'}<Button variant="outline" onclick={() => setView('overview')}>Lihat ringkasan</Button>{/if}
						</div>
					{/if}
				</section>
				{/if}

				{#if selectedContract}
					{@const kpiPreview = kpiScorePreview(selectedContract)}
					{@const completedKpis = kpiPreview?.completedItems ?? 0}
					{@const totalKpis = selectedContract.items.length}
					{@const completionPercent = totalKpis > 0 ? Math.round((completedKpis / totalKpis) * 100) : 0}
					<section class="detail-panel" id={view === 'mine' ? 'staff-workspace-tab-panel' : undefined} role={view === 'mine' ? 'tabpanel' : undefined} tabindex={view === 'mine' ? 0 : undefined} aria-labelledby={view === 'mine' ? 'staff-tab-' + staffWorkspaceTab.toLowerCase() : 'contract-detail-title'}>
						<div class="panel-heading detail-heading">
							<div><p class="eyebrow">DETAIL KONTRAK · {selectedContract.id}</p><h2 id="contract-detail-title">{selectedContract.employee_name ?? selectedContract.org_unit_name}</h2><p class="muted-copy">{selectedContract.position_name} · {selectedContract.org_unit_name}</p><p class="measurement-period-note"><strong>Periode realisasi yang dinilai:</strong> {contractMeasurementRange(selectedContract)}</p>{#if selectedContract.position_kpi_version}<p class="measurement-period-note"><strong>Versi indikator jabatan:</strong> V{selectedContract.position_kpi_version}</p>{/if}</div>
							<div class="detail-heading-actions"><Badge variant={statusVariant(selectedContract.status)}>{statusNames[selectedContract.status]}</Badge><Button variant="ghost" size="sm" onclick={closeSelectedContract}>Tutup</Button></div>
						</div>

						{#if isBranchSelfAssessment() && view !== 'mine'}
							<section class="assessment-flow" aria-labelledby="assessment-flow-title">
								<div class="assessment-flow-heading"><div><p class="eyebrow">SELF-ASSESSMENT BERTAHAP</p><h3 id="assessment-flow-title">Lengkapi sebelum diajukan</h3></div><span class="assessment-flow-count">Tahap {assessmentStep} dari 3</span></div>
								<div class="assessment-stepper" role="list" aria-label="Tahap self-assessment">
									<button type="button" class:active={assessmentStep === 1} class:complete={assessmentStep > 1} onclick={() => void moveAssessmentStep(1)}><span>1</span><strong>Isi KPI</strong><small>Realisasi & bukti</small></button>
									<button type="button" class:active={assessmentStep === 2} class:complete={assessmentStep > 2} onclick={() => void moveAssessmentStep(2)}><span>2</span><strong>Perilaku</strong><small>Lima indikator</small></button>
									<button type="button" class:active={assessmentStep === 3} onclick={() => void moveAssessmentStep(3)}><span>3</span><strong>Periksa</strong><small>Ringkasan penilaian</small></button>
								</div>
								<p class="assessment-flow-hint">{assessmentStepHint()}</p>
							</section>
						{/if}

						{#if canReviewSubmitted(selectedContract)}<div class="review-correction-notice"><strong>Penilai dapat merevisi nilai langsung.</strong><span>Perubahan nilai KPI maupun perilaku dihitung ulang dan penilaian diteruskan ke tahap berikutnya. Setiap koreksi wajib disertai alasan.</span></div>{/if}

						{#if view !== 'mine' || staffWorkspaceTab === 'KPI' || staffWorkspaceTab === 'BEHAVIOR' || staffWorkspaceTab === 'APPROVAL'}
						<div class="scorecard-layout">
						<div class="scorecard-main">
						{#if view === 'mine' ? staffWorkspaceTab === 'KPI' || staffWorkspaceTab === 'APPROVAL' : !isBranchSelfAssessment() || assessmentStep !== 2}
						{#if view === 'mine' && staffWorkspaceTab === 'APPROVAL'}
							<div class="assessment-review-heading assessment-kpi-heading"><div><p class="eyebrow">RINCIAN KPI</p><h3>Nilai setiap indikator</h3></div><span>{selectedContract.items.length} indikator</span></div>
						{/if}
						<div class="metric-list">
							{#each selectedContract.items as item (item.id)}
								{@const draftActual = assessmentFieldValue(item, 'actual').trim()}
								<article class="metric-card">
									<div class="metric-heading">
										<div><span class="metric-code">{item.code} · {item.score_type}</span><h3>{item.name}</h3></div>
										<div class="metric-result"><strong>{formatNumber(weightedItemScorePreview(item), 1)}</strong><span>{isItemScorePreview(item) ? 'pratinjau berbobot' : 'kontribusi berbobot'}</span><small>{formatNumber(itemScorePreview(item), 1)} × {formatNumber(item.weight * 100)}% bobot</small></div>
									</div>
									<div class="metric-facts">
										<div><span>Target</span><strong>{formatNumber(item.target, 2)} {item.unit}</strong></div>
										<div><span>Realisasi</span><strong>{draftActual === '' ? 'Belum diisi' : formatNumber(Number(draftActual), 2) + ' ' + item.unit}</strong></div>
										<div><span>Bobot</span><strong>{formatNumber(item.weight * 100)}%</strong></div>
										<div><span>Status data</span><Badge variant={itemDraftComplete(item) ? 'secondary' : 'outline'}>{item.state === 'NOT_APPLICABLE' ? 'Tidak berlaku' : itemDraftComplete(item) ? (itemDirty(item) ? 'Siap disimpan' : 'Lengkap') : 'Belum lengkap'}</Badge></div>
									</div>

										{#if (canEditActual(selectedContract) || canEditTarget(selectedContract) || canReviewSubmitted(selectedContract)) && (!isBranchSelfAssessment() || assessmentStep !== 3) && (view !== 'mine' || staffWorkspaceTab === 'KPI')}
										<div class="metric-edit">
											{#if (canEditActual(selectedContract) || canReviewSubmitted(selectedContract)) && (!isBranchSelfAssessment() || assessmentStep === 1)}
										<label><span>{canReviewSubmitted(selectedContract) ? 'Koreksi realisasi periode data' : 'Realisasi total periode data'}</span><input class="text-input" type="number" step="any" bind:value={edits[item.id].actual} /></label>
											{/if}
											{#if canEditActual(selectedContract) && (!isBranchSelfAssessment() || assessmentStep === 1)}
												<label><span>Referensi bukti</span><input class="text-input" type="text" bind:value={edits[item.id].evidence} placeholder="Nomor dokumen atau tautan bukti" /></label>
												<label class="wide-field"><span>Catatan</span><input class="text-input" type="text" bind:value={edits[item.id].note} /></label>
											{/if}
											{#if canReviewSubmitted(selectedContract)}
												<label class="wide-field"><span>Alasan koreksi KPI <b aria-hidden="true">*</b></span><input class="text-input" type="text" bind:value={edits[item.id].reason} placeholder="Jelaskan alasan perubahan nilai ini" /></label>
											{/if}
											{#if canEditTarget(selectedContract)}
												<label><span>Target baru</span><input class="text-input" type="number" step="any" bind:value={edits[item.id].target} /></label>
												<label><span>Bobot (%)</span><input class="text-input" type="number" min="0.01" max="100" step="0.01" bind:value={edits[item.id].weightPercent} /></label>
												<label class="wide-field"><span>Alasan perubahan target/bobot</span><input class="text-input" type="text" bind:value={edits[item.id].reason} placeholder="Wajib jika target atau bobot diubah" /></label>
											{/if}
											<div class="metric-save"><Button size="sm" disabled={busy || !canSaveItem(item)} onclick={() => saveItem(item)}>{canReviewSubmitted(selectedContract) ? 'Simpan koreksi KPI' : 'Simpan item'}</Button></div>
										</div>
									{/if}
								</article>
							{/each}
						</div>
						{/if}

						{#if (view === 'mine' ? staffWorkspaceTab === 'BEHAVIOR' || staffWorkspaceTab === 'APPROVAL' : (isBranchSelfAssessment() && assessmentStep === 2) || canReviewSubmitted(selectedContract))}
							<section class="behavior-evaluation-panel" aria-labelledby="behavior-evaluation-title">
								<div class="behavior-evaluation-heading"><div><p class="eyebrow">PENILAIAN PERILAKU · BOBOT 20%</p><h3 id="behavior-evaluation-title">{view === 'mine' && staffWorkspaceTab === 'APPROVAL' ? 'Penilaian akhir perilaku' : 'Nilai lima perilaku kerja'}</h3><p>Gunakan skala 1–5. Nilai 3 berarti memenuhi standar.</p></div><Badge variant="outline">{view === 'mine' && staffWorkspaceTab === 'APPROVAL' ? 'Tinjauan baca saja' : 'Sama untuk semua jabatan'}</Badge></div>
								<div class="behavior-criteria-list">
									{#each behaviorCriteria as criterion, index (criterion.key)}
										<fieldset class="behavior-criterion">
											<legend>{index + 1}. {criterion.label}</legend>
											<div class="behavior-rating-options" role="group" aria-label={'Nilai ' + criterion.label}>
												{#each [1, 2, 3, 4, 5] as rating}
											<button type="button" disabled={view === 'mine' ? staffWorkspaceTab !== 'BEHAVIOR' : !isBranchSelfAssessment() && !canReviewSubmitted(selectedContract)} class:selected={behaviorDraft[criterion.key] === String(rating)} aria-pressed={behaviorDraft[criterion.key] === String(rating)} onclick={() => (behaviorDraft = { ...behaviorDraft, [criterion.key]: String(rating) })}>{rating}</button>
												{/each}
											</div>
											{#if behaviorDraft[criterion.key] === '3'}<p class="behavior-rating-hint">Memenuhi standar</p>{:else if behaviorDraft[criterion.key]}<p class="behavior-rating-hint">Nilai {behaviorDraft[criterion.key]} dipilih</p>{:else}<p class="behavior-rating-hint">Belum dinilai</p>{/if}
										</fieldset>
									{/each}
								</div>
								{#if canReviewSubmitted(selectedContract)}
									<div class="behavior-revision-actions">
										<label for="behavior-revision-comment">Alasan koreksi perilaku <b aria-hidden="true">*</b></label>
										<textarea id="behavior-revision-comment" class="text-input textarea-input" bind:value={behaviorRevisionComment} rows="3" placeholder="Jelaskan aspek yang dikoreksi dan dasar penilaiannya"></textarea>
										<Button disabled={busy || !behaviorDraftDirty() || !behaviorRevisionComment.trim()} onclick={saveBehaviorEvaluation}>Simpan koreksi perilaku</Button>
									</div>
								{/if}
							</section>
						{/if}

						{#if view !== 'mine' && isBranchSelfAssessment() && assessmentStep === 3}
							{@const kpiScoreForReview = kpiPreview?.score ?? selectedContract.score?.kpi_score ?? null}
							{@const behaviorScoreForReview = summaryBehaviorScore()}
							{@const finalScoreForReview = summaryFinalScore() ?? selectedContract.score?.final_score ?? null}
							{@const canSubmitReview = selectedContract.status === 'DRAFT' || selectedContract.status === 'REVISION'}
							<section class="assessment-review-summary" aria-labelledby="assessment-review-title">
								<div class="assessment-review-heading"><div><p class="eyebrow">{canSubmitReview ? 'RINGKASAN PENGAJUAN' : 'RINGKASAN NILAI'}</p><h3 id="assessment-review-title">{canSubmitReview ? 'Periksa sebelum diajukan' : 'Nilai gabungan penilaian'}</h3></div><span>Nilai otomatis</span></div>
								<div class="assessment-review-facts">
									<div><span>Indikator</span><strong>{selectedContract.items.length} KPI</strong></div>
									<div><span>Realisasi</span><strong>{selectedContract.items.filter((item) => assessmentFieldValue(item, 'actual').trim()).length}/{selectedContract.items.length} terisi</strong></div>
									<div><span>Bukti</span><strong>{selectedContract.items.filter((item) => assessmentFieldValue(item, 'evidence').trim()).length}/{selectedContract.items.length} tersedia</strong></div>
									<div><span>Skor KPI</span><strong>{formatNumber(kpiScoreForReview, 2)}</strong></div>
									<div><span>Kontribusi KPI - 80%</span><strong>{formatNumber(scoreContribution(kpiScoreForReview, 0.8), 2)}</strong><small>Dari maksimal 80 poin</small></div>
									<div><span>Skor perilaku</span><strong>{formatNumber(behaviorScoreForReview, 2)}</strong><small>{behaviorRatingsCompletedCount()}/5 indikator dinilai</small></div>
									<div><span>Kontribusi perilaku - 20%</span><strong>{formatNumber(scoreContribution(behaviorScoreForReview, 0.2), 2)}</strong><small>Dari maksimal 20 poin</small></div>
									<div class="assessment-review-total"><span>{finalScoreForReview !== null && !isFinalScoreReady() ? 'Nilai akhir sementara' : 'Nilai akhir'}</span><strong>{finalScoreForReview === null ? 'Belum dihitung' : formatNumber(finalScoreForReview, 2)}</strong></div>
								</div>
								{#if canSubmitReview && assessmentSubmitBlockedReason(selectedContract)}<p class="assessment-review-warning">{assessmentSubmitBlockedReason(selectedContract)}</p>{/if}
							</section>
						{/if}
						</div>

						<Card.Root class="score-summary-card" aria-labelledby="score-summary-title">
							<Card.Header class="score-summary-header">
								<Card.Title id="score-summary-title">Ringkasan penilaian</Card.Title>
							</Card.Header>
							<Card.Content class="score-summary-content">
								<div class="score-completion"><span>KPI lengkap</span><strong>{completedKpis} / {totalKpis}</strong></div>
								<div class="score-progress" role="progressbar" aria-label="KPI lengkap" aria-valuemin="0" aria-valuemax={Math.max(totalKpis, 1)} aria-valuenow={completedKpis}>
									<span style={`width: ${completionPercent}%`}></span>
								</div>
								<div class="score-component"><span>{kpiPreview && kpiPreview.pendingItems > 0 ? 'Skor KPI sementara' : 'Skor hasil · 80%'}</span><strong>{formatNumber(kpiPreview?.score ?? null, 2)}</strong></div>
				<div class="score-component"><span>{behaviorRatingsCompletedCount() > 0 && behaviorRatingsCompletedCount() < behaviorCriteria.length ? 'Perilaku sementara · 20%' : 'Perilaku · 20%'}</span><strong>{formatNumber(summaryBehaviorScore(), 2)}</strong></div>
								<div class="score-final"><span>{summaryFinalScore() !== null && !isFinalScoreReady() ? 'Nilai akhir sementara' : 'Nilai akhir'}</span><strong class:score-final-pending={summaryFinalScore() === null}>{formatNumber(summaryFinalScore(), 2)}</strong></div>
								{#if selectedContract.score?.rating}<Badge variant="secondary" class="score-rating">{selectedContract.score.rating}</Badge>{/if}
								<p class="score-summary-message">{kpiPreviewMessage(selectedContract)}{behaviorRatingsCompletedCount() === 0 ? ' Isi nilai perilaku untuk menghitung komponennya.' : behaviorRatingsCompletedCount() < behaviorCriteria.length ? ` Skor perilaku sementara dari ${behaviorRatingsCompletedCount()}/${behaviorCriteria.length} indikator.` : ''}{summaryFinalScore() !== null && !isFinalScoreReady() ? ' Nilai akhir sementara mengikuti input yang sudah dinilai.' : ''}</p>
								<p class="score-summary-footnote">Bobot KPI 80% + perilaku 20%. Bobot KPI valid: {kpiPreview ? formatNumber(kpiPreview.validWeight * 100) + '%' : 'Belum tersedia'}</p>
							</Card.Content>
						</Card.Root>
						</div>
						{/if}

						{#if selectedContract.review_workflow && (view !== 'mine' || staffWorkspaceTab === 'HISTORY')}
							<section class="assessment-review-history" aria-labelledby="assessment-review-history-title">
								<div class="assessment-review-heading"><div><p class="eyebrow">ALUR PERSETUJUAN</p><h3 id="assessment-review-history-title">{view === 'mine' && staffWorkspaceTab === 'APPROVAL' ? 'Persetujuan penilaian' : 'Riwayat penilai'}</h3></div><Badge variant={statusVariant(selectedContract.status)}>{statusNames[selectedContract.status]}</Badge></div>
								<p class="assessment-review-history-current">{selectedContract.status === 'SUBMITTED' && selectedContract.review_workflow.current_slot ? `Sedang menunggu Penilai ${selectedContract.review_workflow.current_slot.slice(1)}.` : selectedContract.status === 'REVISION' ? 'Dikembalikan untuk perbaikan oleh pegawai.' : selectedContract.status === 'FINAL' ? 'Seluruh tahapan penilaian selesai.' : 'Urutan penilai ditentukan otomatis dari hirarki jabatan.'}</p>
								<div class="reviewer-preview-list">
									{#each ['P1', 'P2'] as slot}
										{@const reviewer = selectedContract.review_workflow.reviewer_slots[slot as 'P1' | 'P2']}
										<div><span>Penilai {slot.slice(1)}</span><strong>{reviewer?.name ?? 'Tidak tersedia'}</strong><small>{reviewer?.position_name ?? ''} {reviewer ? '· ' + reviewerSlotStatus(selectedContract, slot as 'P1' | 'P2') : ''}</small></div>
									{/each}
								</div>
								{#if selectedContract.review_history?.length}
									<ol class="assessment-review-history-list">
										{#each selectedContract.review_history as event, index (`${event.reviewer_employee_id}-${event.created_at ?? index}`)}
											<li><div><strong>{event.reviewer_name}</strong><span>{event.slot ? `Penilai ${event.slot.slice(1)}` : 'Admin'}</span></div><p>{reviewerDecisionLabel(event.decision)} · {event.comment}</p><time>{formatDateTime(event.created_at)}</time></li>
										{/each}
									</ol>
								{:else}
									<p class="assessment-review-history-empty">Belum ada keputusan penilai.</p>
								{/if}
							</section>
						{/if}

						{#if selectedContract.comments.length > 0 && (view !== 'mine' || staffWorkspaceTab === 'HISTORY')}
							<section class="comments-section" aria-labelledby="comments-title">
								<h3 id="comments-title">Catatan kontrak</h3>
								{#each selectedContract.comments as comment (comment.id)}
									<div class="comment-row"><div class="comment-avatar">{initials(comment.actor_name)}</div><div><strong>{comment.actor_name}</strong><p>{comment.message}</p></div></div>
								{/each}
							</section>
						{/if}

						{#if view !== 'mine' || staffWorkspaceTab === 'HISTORY'}
						<section class="audit-section" aria-labelledby="audit-title">
							<h3 id="audit-title">Riwayat tindakan</h3>
							{#if auditLoadFailed}
								<p class="audit-empty">Riwayat tindakan belum dapat dimuat.</p>
							{:else if auditEvents.length === 0}
								<p class="audit-empty">Belum ada tindakan yang tercatat pada kontrak ini.</p>
							{:else}
								<ol class="audit-list">
									{#each auditEvents as event (event.id)}
										<li class="audit-row">
											<span class="audit-marker" aria-hidden="true"></span>
											<div class="audit-copy">
												<div class="audit-heading">
													<strong>{auditActionLabel(event.action)}</strong>
													<time datetime={event.occurred_at ?? undefined}>{formatDateTime(event.occurred_at)}</time>
												</div>
												{#if auditChangeLabel(event)}<p class="audit-change">{auditChangeLabel(event)}</p>{/if}
												<p class="audit-reason">{event.actor_name}{event.reason ? ' · ' + event.reason : ''}</p>
											</div>
										</li>
									{/each}
								</ol>
							{/if}
						</section>
						{/if}

						{#if isBranchSelfAssessment() && (view !== 'mine' || (staffWorkspaceTab === 'APPROVAL' && (selectedContract.status === 'DRAFT' || selectedContract.status === 'REVISION')))}
							<section class="workflow-box assessment-workflow-box">
								{#if assessmentStep === 3}
									<label for="comment-draft">Catatan untuk reviewer <span class="optional-label">Opsional</span></label>
									<textarea id="comment-draft" class="text-input textarea-input" bind:value={commentDraft} rows="3" placeholder="Tambahkan konteks singkat sebelum pengajuan"></textarea>
									{#if assessmentSubmitBlockedReason(selectedContract)}<p class="workflow-hint">{assessmentSubmitBlockedReason(selectedContract)}</p>{/if}
									<div class="workflow-actions">
										<Button variant="outline" onclick={() => { if (view === 'mine') void selectStaffWorkspaceTab('BEHAVIOR'); else void moveAssessmentStep(2); }}>Kembali ke perilaku</Button>
										<Button variant="outline" disabled={busy || !commentDraft.trim()} onclick={addComment}>Simpan catatan</Button>
										<Button disabled={busy || Boolean(assessmentSubmitBlockedReason(selectedContract))} onclick={submitContract}>Kirim penilaian</Button>
									</div>
								{:else}
									<div class="workflow-actions">
											{#if assessmentStep === 2}<Button variant="outline" onclick={() => void moveAssessmentStep(1)}>Kembali ke isi KPI</Button>{/if}
											<Button disabled={Boolean(assessmentNextBlockedReason(selectedContract))} onclick={() => void moveAssessmentStep(assessmentStep === 1 ? 2 : 3)}>{assessmentStep === 1 ? 'Lanjut ke perilaku' : 'Periksa penilaian'}</Button>
									</div>
								{/if}
							</section>
						{:else if view !== 'mine' && (selectedContract.status === 'DRAFT' || selectedContract.status === 'REVISION')}
							<section class="workflow-box">
								<label for="comment-draft">Tambah komentar</label>
								<textarea id="comment-draft" class="text-input textarea-input" bind:value={commentDraft} rows="3" placeholder="Tulis usulan atau catatan untuk penilaian"></textarea>
								{#if submitBlockedReason(selectedContract)}<p class="workflow-hint">{submitBlockedReason(selectedContract)}</p>{/if}
								<div class="workflow-actions">
									<Button variant="outline" disabled={busy || !commentDraft.trim()} onclick={addComment}>Kirim komentar</Button>
									{#if selectedContract.subject_id === currentUser.employee_id || hasAccessRole('SDM')}<Button disabled={busy || Boolean(submitBlockedReason(selectedContract))} onclick={submitContract}>Ajukan tinjauan</Button>{/if}
								</div>
							</section>
						{/if}
						{#if selectedContract.status === 'SUBMITTED' && selectedContract.reviewer_employee_id === currentUser.employee_id}
							<section class="workflow-box">
								<label for="review-comment">Catatan keputusan reviewer</label>
								<textarea id="review-comment" class="text-input textarea-input" bind:value={reviewComment} rows="3" placeholder="Catatan wajib disimpan bersama keputusan"></textarea>
								<div class="workflow-actions"><Button disabled={busy || !reviewComment.trim()} onclick={() => reviewContract('VERIFIED')}>{reviewerVerifyLabel(selectedContract)}</Button></div>
							</section>
						{/if}
						{#if selectedContract.status === 'VERIFIED' && hasAccessRole('SDM')}
							<section class="workflow-box">
								<label for="final-comment">Catatan finalisasi</label>
								<textarea id="final-comment" class="text-input textarea-input" bind:value={reviewComment} rows="3" placeholder="Catatan wajib untuk finalisasi"></textarea>
								<div class="workflow-actions"><Button disabled={busy || !reviewComment.trim()} onclick={() => reviewContract('FINAL')}>Finalisasi kontrak</Button></div>
							</section>
						{/if}
					</section>
				{/if}
				{/if}
			</main>
		</div>
	</div>


{/if}

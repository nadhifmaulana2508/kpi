<script lang="ts">
	import { onMount } from 'svelte';
	import { api, ApiError } from '#lib/api.js';
	import { Badge } from '#lib/components/ui/badge/index.js';
	import { Button } from '#lib/components/ui/button/index.js';
	import * as Card from '#lib/components/ui/card/index.js';
	import './page.css';
	let isDark = $state(false);
	onMount(() => {
		const savedTheme = localStorage.getItem('kpi-theme');
		isDark = savedTheme ? savedTheme === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
		document.documentElement.classList.toggle('dark', isDark);
	});
	function toggleTheme(): void {
		isDark = !isDark;
		document.documentElement.classList.toggle('dark', isDark);
		localStorage.setItem('kpi-theme', isDark ? 'dark' : 'light');
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
	type SelfAssessmentStep = 1 | 2 | 3;
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
		subject_type: string;
		subject_id: string;
		employee_name: string | null;
		position_name: string | null;
		org_unit_name: string;
		status: 'DRAFT' | 'SUBMITTED' | 'REVISION' | 'VERIFIED' | 'FINAL';
		reviewer_employee_id: string | null;
		items: ContractItem[];
		comments: ContractComment[];
		score: ScoreSummary | null;
	};
	type EditItem = {
		actual: string;
		evidence: string;
		note: string;
		target: string;
		weightPercent: string;
		reason: string;
	};
	type Envelope<T> = { data: T };

	function demoHistoryForTrack(track: StaffTrackKind): AssessmentHistoryEntry[] {
		const business = track === 'BUSINESS';
		const prefix = business ? 'P' : 'O';
		const names = business
			? ['Akuisisi dana demo', 'Realisasi kredit demo', 'Kualitas portofolio demo']
			: ['SLA layanan operasional demo', 'Akurasi dokumen demo', 'Penyelesaian transaksi demo'];
		const units = business ? ['rekening', 'juta rupiah', '%'] : ['menit', '%', 'transaksi'];
		const makeItems = (actuals: string[], scores: Array<number | null>): HistoryItem[] =>
			names.map((name, index) => ({
				code: `DEMO-${prefix}-${String(index + 1).padStart(2, '0')}`,
				name,
				target: business ? ['100', '50', '95'][index] : ['30', '98', '120'][index],
				actual: actuals[index] ?? 'Belum diisi',
				score: scores[index] ?? null,
				unit: units[index]
			}));
		const reviewer = 'Kepala Cabang Demo 001';

		return [
			{
				id: `DEMO-HISTORY-${prefix}-001`,
				track,
				periodName: 'Triwulan II 2026',
				cadence: '3 bulan',
				startsAt: '2026-04-01',
				endsAt: '2026-06-30',
				status: 'FINAL',
				score: business ? 88.4 : 91.2,
				reviewer,
				finalDate: '2026-07-15',
				submittedDate: '2026-07-08',
				note: 'Contoh penilaian final untuk melihat bentuk histori yang sudah terkunci.',
				items: makeItems(business ? ['118', '56', '96'] : ['28', '99', '126'], business ? [90, 86, 89] : [94, 92, 88])
			},
			{
				id: `DEMO-HISTORY-${prefix}-002`,
				track,
				periodName: 'Triwulan III 2026',
				cadence: '3 bulan',
				startsAt: '2026-07-01',
				endsAt: '2026-09-30',
				status: 'REVISION',
				score: business ? 76.2 : 82.1,
				reviewer,
				finalDate: null,
				submittedDate: '2026-10-08',
				note: 'Reviewer meminta penjelasan tambahan pada indikator yang belum didukung bukti lengkap.',
				items: makeItems(business ? ['91', '42', '88'] : ['33', '96', '110'], business ? [78, 74, 76] : [84, 81, 81])
			},
			{
				id: `DEMO-HISTORY-${prefix}-003`,
				track,
				periodName: 'Triwulan IV 2026',
				cadence: '3 bulan',
				startsAt: '2026-10-01',
				endsAt: '2026-12-31',
				status: 'SUBMITTED',
				score: null,
				reviewer,
				finalDate: null,
				submittedDate: '2026-11-08',
				note: 'Self-assessment sudah diajukan dan menunggu pemeriksaan reviewer.',
				items: makeItems(business ? ['105', '48', '94'] : ['31', '98', '119'], [null, null, null])
			},
			{
				id: `DEMO-HISTORY-${prefix}-004`,
				track,
				periodName: 'Rencana Semester I 2027',
				cadence: '6 bulan',
				startsAt: '2027-01-01',
				endsAt: '2027-06-30',
				status: 'DRAFT',
				score: null,
				reviewer: null,
				finalDate: null,
				submittedDate: null,
				note: 'Draf contoh yang masih dapat diubah sebelum diajukan.',
				items: makeItems(['Belum diisi', 'Belum diisi', 'Belum diisi'], [null, null, null])
			}
		];
	}

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
	let identitySearch = $state('');
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
	let assessmentStep = $state<SelfAssessmentStep>(1);
	let auditEvents = $state<AuditEvent[]>([]);
	let auditLoadFailed = $state(false);
	let edits = $state<Record<string, EditItem>>({});
	let loading = $state(true);
	let busy = $state(false);
	let errorMessage = $state('');
	let successMessage = $state('');
	let view = $state<'overview' | 'mine' | 'reviews' | 'assignment' | 'periods' | 'history' | 'monitoring' | 'catalog' | 'mapping' | 'positions' | 'ssoMappings' | 'audit' | 'comparison'>('overview');
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
	let periodNotes = $state('');
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
	const demoAssessmentHistory: AssessmentHistoryEntry[] = [
		...demoHistoryForTrack('BUSINESS'),
		...demoHistoryForTrack('NON_BUSINESS')
	];
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
			actorName: 'Kepala Cabang Demo 001',
			actorRole: 'Reviewer / Kepala Cabang',
			actedAt: '2026-11-12T09:15:00+07:00',
			employeeName: 'Pemasaran Demo 001',
			positionName: 'Staf Pemasaran',
			periodName: 'Triwulan IV 2026',
			note: 'Target, realisasi, dan bukti demo sudah diperiksa. Snapshot siap masuk proses finalisasi Admin.',
			items: demoReviewItems('BUSINESS', 'first'),
			score: 86.1,
			reviewerName: 'Kepala Cabang Demo 001',
			submittedAt: '2026-11-11',
			finalDate: null
		},
		{
			id: 'DEMO-AUDIT-P-003',
			track: 'BUSINESS',
			status: 'VERIFIED',
			actorName: 'Kepala Cabang Demo 001',
			actorRole: 'Reviewer / Kepala Cabang',
			actedAt: '2026-11-12T14:20:00+07:00',
			employeeName: 'Pemasaran Demo 003',
			positionName: 'Staf Pemasaran',
			periodName: 'Triwulan IV 2026',
			note: 'Penilaian diverifikasi setelah target dan bukti capaian melewati pemeriksaan reviewer.',
			items: demoReviewItems('BUSINESS', 'first'),
			score: 89.2,
			reviewerName: 'Kepala Cabang Demo 001',
			submittedAt: '2026-11-05',
			finalDate: null
		},
		{
			id: 'DEMO-AUDIT-P-002',
			track: 'BUSINESS',
			status: 'FINAL',
			actorName: 'Admin KPI Demo 001',
			actorRole: 'Admin KPI / SDM',
			actedAt: '2026-11-13T15:20:00+07:00',
			employeeName: 'Pemasaran Demo 002',
			positionName: 'Staf Pemasaran',
			periodName: 'Triwulan IV 2026',
			note: 'Snapshot dikunci setelah verifikasi reviewer. Tidak ada perubahan lanjutan pada periode ini.',
			items: demoReviewItems('BUSINESS', 'second'),
			score: 86.1,
			reviewerName: 'Kepala Cabang Demo 001',
			submittedAt: '2026-11-08',
			finalDate: '2026-11-13'
		},
		{
			id: 'DEMO-AUDIT-O-001',
			track: 'NON_BUSINESS',
			status: 'VERIFIED',
			actorName: 'Kepala Cabang Demo 001',
			actorRole: 'Reviewer / Kepala Cabang',
			actedAt: '2026-11-11T13:40:00+07:00',
			employeeName: 'Operasional Demo 001',
			positionName: 'Staf Operasional',
			periodName: 'Triwulan IV 2026',
			note: 'Bukti layanan dan rekonsiliasi demo sesuai dengan catatan proses operasional.',
			items: demoReviewItems('NON_BUSINESS', 'first'),
			score: 91.3,
			reviewerName: 'Kepala Cabang Demo 001',
			submittedAt: '2026-11-09',
			finalDate: null
		},
		{
			id: 'DEMO-AUDIT-O-002',
			track: 'NON_BUSINESS',
			status: 'FINAL',
			actorName: 'Admin KPI Demo 001',
			actorRole: 'Admin KPI / SDM',
			actedAt: '2026-11-12T16:05:00+07:00',
			employeeName: 'Operasional Demo 002',
			positionName: 'Staf Operasional',
			periodName: 'Triwulan IV 2026',
			note: 'Finalisasi demo selesai. Snapshot menjadi referensi read-only untuk histori penilaian.',
			items: demoReviewItems('NON_BUSINESS', 'second'),
			score: 90.4,
			reviewerName: 'Kepala Cabang Demo 001',
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
		{ id: 'DEMO-CATALOG-P-001', code: 'DEMO-P-01', name: 'Akuisisi dana demo', track: 'BUSINESS', group: 'Dana', type: 'Q', unit: 'rekening', target: 100, weight: 0.2, dataSource: 'Rekap core demo', state: 'ACTIVE', demoOnly: true },
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
			orgUnitName: 'Cabang Contoh 001',
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
			orgUnitName: 'Cabang Contoh 001',
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
			orgUnitName: 'Cabang Contoh 001',
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
		CONTRACT_ITEM_UPDATED: 'Item kontrak diperbarui'
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
		const end = new Date(year, month - 1 + cadence, day);
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

	function periodCadenceLabel(months: number): string {
		return periodCadenceNames[months] ?? `${months} bulan`;
	}

	function selectedAdminPeriod(): DemoPeriod | null {
		return adminPeriodFilter === 'ALL' ? null : demoPeriods.find((period) => period.id === adminPeriodFilter) ?? null;
	}

	function selectedAdminPeriodLabel(): string {
		return selectedAdminPeriod()?.name ?? (adminPeriodFilter === 'ALL' ? 'Semua periode' : 'Periode aktif');
	}

	function selectedAdminPeriodDateRange(): string {
		const period = selectedAdminPeriod();
		return period ? `${periodDateLabel(period.startsAt)} - ${periodDateLabel(period.endsAt)}` : 'Seluruh periode demo';
	}

	function periodMatchesAdminFilter(periodName: string): boolean {
		return adminPeriodFilter === 'ALL' || periodName === selectedAdminPeriod()?.name;
	}

	function periodStatusDescription(status: PeriodStatus): string {
		if (status === 'OPEN') return 'Pengisian dan penilaian dapat berjalan sesuai workflow.';
		if (status === 'CLOSED') return 'Pengisian ditutup; proses review dapat dilanjutkan.';
		return 'Konfigurasi masih dapat diperiksa sebelum periode dibuka.';
	}

	function selectDemoPeriod(period: DemoPeriod): void {
		periodName = period.name;
		periodCadenceMonths = period.cadenceMonths;
		periodStartDate = period.startsAt;
		periodStatus = period.status;
		periodNotes = '';
		successMessage = '';
		errorMessage = '';
	}

	function saveDemoPeriod(): void {
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

		const nextNumber = demoPeriods.length + 1;
		const savedPeriod: DemoPeriod = {
			id: `DEMO-PERIOD-${String(nextNumber).padStart(3, '0')}`,
			name: periodName.trim(),
			cadenceMonths: periodCadenceMonths,
			startsAt: periodStartDate,
			endsAt: periodEndDateInput(),
			status: periodStatus
		};
		demoPeriods = [savedPeriod, ...demoPeriods];
		successMessage = `${savedPeriod.name} disimpan sebagai ${periodStatusNames[savedPeriod.status].toLowerCase()} di data demo.`;
	}

	const demoIdentityGroups: Omit<DemoIdentityGroup, 'identities'>[] = [
		{
			key: 'admin',
			label: 'Admin KPI',
			description: 'Pengaturan KPI dan pemantauan demo.',
			ids: ['DEMO-SDM-001']
		},
		{
			key: 'pusat',
			label: 'Pusat · Divisi Operasional',
			description: 'Staf Sistem dan Jaringan TI dengan akses self-assignment dan Admin KPI demo.',
			ids: ['DEMO-STAF-PUSAT-001']
		},
		{
			key: 'cabang',
			label: 'Cabang contoh 001',
			description: 'Kepala Cabang, Pemasaran, dan Operasional.',
			ids: ['DEMO-KACAB-001', 'DEMO-PEMASARAN-001', 'DEMO-OPERASIONAL-001']
		}
	];

	function visibleIdentityGroups(): DemoIdentityGroup[] {
		const query = identitySearch.trim().toLocaleLowerCase('id-ID');
		return demoIdentityGroups
			.map((group) => ({
				...group,
				identities: group.ids
					.map((id) => identities.find((identity) => identity.id === id))
					.filter((identity): identity is DemoIdentity => {
						if (!identity) return false;
						const content = [
							identity.display_name,
							identity.id,
							identity.access_role,
							...identity.access_roles,
							identity.org_unit_name
						]
							.join(' ')
							.toLocaleLowerCase('id-ID');
						return !query || content.includes(query);
					})
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
			identitiesError = error instanceof Error ? error.message : 'Daftar user demo belum dapat dimuat.';
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
		return ['PEGAWAI', 'KEPALA_SUB_BIDANG'].includes(currentUser?.access_role ?? '');
	}

	function staffTrack(): StaffTrack {
		const context = `${currentUser?.org_unit_name ?? ''} ${currentUser?.position_name ?? ''}`.toLocaleLowerCase('id-ID');
		if (context.includes('pemasaran')) return 'BUSINESS';
		if (context.includes('operasional') || context.includes('backoffice')) return 'NON_BUSINESS';
		return null;
	}

	function staffTrackLabel(): string {
		if (staffTrack() === 'BUSINESS') return 'Pemasaran / Bisnis';
		if (staffTrack() === 'NON_BUSINESS') return 'Operasional / Non-bisnis';
		return 'Katalog sesuai jabatan';
	}

	function staffTrackDescription(): string {
		if (staffTrack() === 'BUSINESS') return 'Pilih KPI demo yang menggambarkan dana, kredit, remedial, dan aktivitas nasabah.';
		if (staffTrack() === 'NON_BUSINESS') return 'Pilih KPI demo yang menggambarkan layanan operasional, backoffice, dokumen, dan kepatuhan proses.';
		return 'Pilih indikator yang tersedia untuk jabatan dan unit Anda.';
	}

	function staffAssignmentKey(): string {
		const track = staffTrack();
		if (!currentUser || !track) return '';
		return `${currentUser.employee_id}:${track}`;
	}

	function currentStaffAssignmentRecord(): DemoStaffAssignment | null {
		const key = staffAssignmentKey();
		return key ? staffAssignmentRecords[key] ?? null : null;
	}

	function staffReviewTaskId(key = staffAssignmentKey()): string {
		return key ? `DEMO-SUBMISSION-${key.replace(/[^A-Z0-9]+/gi, '-')}` : '';
	}

	function currentStaffReviewDecision(): DemoReviewDecision | null {
		const taskId = staffReviewTaskId();
		return taskId ? demoReviewDecisions[taskId] ?? null : null;
	}

	function staffReviewDecisionForKey(key: string): DemoReviewDecision | null {
		const taskId = staffReviewTaskId(key);
		return taskId ? demoReviewDecisions[taskId] ?? null : null;
	}

	function staffFinalizationForKey(key: string): DemoStaffFinalization | null {
		const taskId = staffReviewTaskId(key);
		return taskId ? demoStaffFinalizations[taskId] ?? null : null;
	}

	function currentStaffFinalization(): DemoStaffFinalization | null {
		const key = staffAssignmentKey();
		return key ? staffFinalizationForKey(key) : null;
	}

	function effectiveStaffAssignmentStatus(record: DemoStaffAssignment | null, key = staffAssignmentKey()): StaffAssignmentStatus {
		if (!record) return 'NONE';
		if (record.status === 'SUBMITTED' && staffFinalizationForKey(key)) return 'FINAL';
		if (record.status === 'SUBMITTED' && staffReviewDecisionForKey(key)?.decision === 'VERIFIED') return 'VERIFIED';
		if (record.status === 'SUBMITTED' && staffReviewDecisionForKey(key)?.decision === 'REVISION') return 'REVISION';
		return record.status;
	}

	function staffAssignmentStatusLabel(): string {
		if (staffAssignmentStatus === 'SUBMITTED') return 'SUBMITTED · Menunggu reviewer';
		if (staffAssignmentStatus === 'REVISION') return 'REVISION · Perlu diperbaiki';
		if (staffAssignmentStatus === 'DRAFT') return 'DRAFT · Belum diajukan';
		if (staffAssignmentStatus === 'VERIFIED') return 'VERIFIED - Diverifikasi reviewer';
		if (staffAssignmentStatus === 'FINAL') return 'FINAL - Snapshot terkunci';
		return 'Belum disimpan';
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
				periodName: activeMonthlyCycle()?.name ?? 'Periode demo aktif',
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
			errorMessage = 'Identitas staf belum tersedia untuk pengajuan demo.';
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
				employeeName: previous?.employeeName ?? currentUser?.display_name ?? 'Staf demo',
				positionName: previous?.positionName ?? currentUser?.position_name ?? 'Staf cabang',
				periodName: previous?.periodName ?? activeMonthlyCycle()?.name ?? 'Periode demo aktif',
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
		successMessage = 'Self-assessment demo diajukan. Pilihan KPI terkunci sampai reviewer memberi keputusan.';
	}

	function definitionDisplayName(definition: KpiDefinition): string {
		const businessNames = [
			'Akuisisi dana demo',
			'Realisasi kredit demo',
			'Kualitas portofolio demo',
			'Penyelesaian remedial demo',
			'Kepatuhan proses demo',
			'Aktivitas nasabah demo'
		];
		const operationalNames = [
			'SLA layanan operasional demo',
			'Akurasi dokumen demo',
			'Penyelesaian transaksi demo',
			'Kualitas layanan backoffice demo',
			'Kepatuhan prosedur demo',
			'Kesiapan rekonsiliasi demo'
		];
		const index = Number(definition.code.match(/(\d+)$/)?.[1] ?? 0) - 1;
		const names = staffTrack() === 'NON_BUSINESS' ? operationalNames : businessNames;
		return names[index] ?? definition.name;
	}

	function canViewStaffHistory(): boolean {
		return currentUser?.access_role === 'PEGAWAI' && staffTrack() !== null;
	}

	function currentStaffHistoryEntry(): AssessmentHistoryEntry | null {
		const record = currentStaffAssignmentRecord();
		const key = staffAssignmentKey();
		if (!record || !key || record.status !== 'SUBMITTED') return null;
		const decision = staffReviewDecisionForKey(key);
		const finalization = staffFinalizationForKey(key);
		const cycle = activeMonthlyCycle();
		const status = finalization ? 'FINAL' : decision?.decision === 'VERIFIED' ? 'VERIFIED' : decision?.decision === 'REVISION' ? 'REVISION' : 'SUBMITTED';
		return {
			id: `DEMO-LIVE-HISTORY-${key.replace(/[^A-Z0-9]+/gi, '-')}`,
			track: record.track,
			periodName: record.periodName,
			cadence: '1 bulan',
			startsAt: cycle?.starts_at ?? '2026-11-01',
			endsAt: cycle?.ends_at ?? '2026-11-30',
			status,
			score: null,
			reviewer: decision ? 'Kepala Cabang Demo 001' : null,
			finalDate: finalization?.finalizedAt.slice(0, 10) ?? null,
			submittedDate: record.submittedAt?.slice(0, 10) ?? record.savedAt.slice(0, 10),
			note: finalization?.note ?? decision?.note ?? 'Self-assessment sudah diajukan dan menunggu pemeriksaan reviewer.',
			items: record.items
		};
	}

	function visibleHistoryEntries(): AssessmentHistoryEntry[] {
		const track = staffTrack();
		const entries = demoAssessmentHistory.filter((entry) => track === null || entry.track === track);
		const liveEntry = currentStaffHistoryEntry();
		return liveEntry ? [liveEntry, ...entries] : entries;
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
		return currentUser?.access_role === 'KEPALA_CABANG';
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
				actorName: currentUser?.display_name ?? 'Reviewer Demo',
				actorRole: currentUser ? roleNames[currentUser.access_role] ?? currentUser.access_role : 'Reviewer / Kepala Cabang'
			}
		};
		errorMessage = '';
		successMessage = decision === 'VERIFIED' ? 'State demo: kontrak ditandai terverifikasi.' : 'State demo: kontrak ditandai perlu revisi.';
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
				actorName: currentUser?.display_name ?? 'Admin KPI Demo 001',
				actorRole: currentUser ? roleNames[currentUser.access_role] ?? currentUser.access_role : 'Admin KPI / SDM'
			}
		};
		errorMessage = '';
		successMessage = 'State demo: penilaian difinalisasi dan snapshot dikunci.';
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
				reviewerName: entry.reviewerName ?? (entry.status === 'FINAL' ? 'Kepala Cabang Demo 001' : entry.actorName),
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
		successMessage = `${name} ditambahkan sebagai data katalog dummy.`;
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
		successMessage = 'Bobot dummy dibagi rata untuk preview penetapan.';
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
		successMessage = `Penetapan dummy jalur ${catalogTrackLabel(assignmentTrack)} tersimpan untuk preview self-assignment.`;
	}

	function canManageAssignment(): boolean {
		return hasAdminAccess();
	}

	function openAssignmentMapping(): void {
		setView('mapping');
		seedAssignmentForTrack('BUSINESS');
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
		return positionEditorMode === 'CREATE' ? 'Tambah jabatan dummy' : 'Edit jabatan dummy';
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
		positionFormOrgUnit = 'Cabang Contoh 001';
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

	function currentStaffPositionMapping(): DemoPositionMapping | null {
		if (currentUser?.access_role !== 'PEGAWAI') return null;
		if (currentUser.position_name.toLocaleLowerCase('id-ID').includes('sistem dan jaringan ti')) {
			return demoPositionMappings.find((position) => position.id === 'POS-STAFF-PUSAT-OPS') ?? null;
		}
		const track = staffTrack();
		if (track === 'BUSINESS') return demoPositionMappings.find((position) => position.id === 'POS-STAFF-BUSINESS') ?? null;
		if (track === 'NON_BUSINESS') return demoPositionMappings.find((position) => position.id === 'POS-STAFF-NON-BUSINESS') ?? null;
		return null;
	}

	function staffAssignmentMappingState(): 'ACTIVE_MAPPED' | 'ACTIVE_EMPTY' | 'INACTIVE' | 'UNAVAILABLE' {
		const position = currentStaffPositionMapping();
		if (!position) return 'UNAVAILABLE';
		if (position.status !== 'ACTIVE') return 'INACTIVE';
		return positionMappingItems(position).length > 0 ? 'ACTIVE_MAPPED' : 'ACTIVE_EMPTY';
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

	async function openMine(): Promise<void> {
		setView('mine');
		if (!canViewStaffHistory()) return;
		const contract = existingMonthlyContract();
		if (contract) await openContract(contract.id);
	}

	function historyTrackLabel(track: StaffTrackKind): string {
		return track === 'BUSINESS' ? 'Pemasaran / Bisnis' : 'Operasional / Non-bisnis';
	}

	function staffDashboardContract(): PerformanceContract | null {
		return existingMonthlyContract();
	}

	function staffDashboardProgress(): { completed: number; total: number; percent: number } {
		const contract = staffDashboardContract();
		const total = contract?.items.length || 6;
		const completed = contract?.items.filter((item) => ['VALID', 'NOT_APPLICABLE'].includes(item.state)).length ?? 0;
		return { completed, total, percent: total > 0 ? Math.round((completed / total) * 100) : 0 };
	}

	function staffDashboardAssignmentLabel(): string {
		const contract = staffDashboardContract();
		if (!contract) return 'Belum disusun';
		return statusNames[contract.status] ?? contract.status;
	}

	function staffDashboardAssignmentDetail(): string {
		const contract = staffDashboardContract();
		if (!contract) return 'Pilih KPI dari katalog untuk memulai self-assignment.';
		if (contract.status === 'REVISION') return 'Ada catatan reviewer yang perlu diselesaikan.';
		if (contract.status === 'SUBMITTED') return 'Self-assignment sudah diajukan dan menunggu reviewer.';
		if (contract.status === 'FINAL') return 'Penilaian sudah final untuk periode aktif.';
		return 'Draf tersimpan; lengkapi indikator sebelum diajukan.';
	}

	function staffDashboardTaskLabel(): string {
		const contract = staffDashboardContract();
		if (!contract) return 'Susun self-assignment';
		if (contract.status === 'REVISION') return 'Perbaiki KPI yang dikembalikan';
		if (contract.status === 'DRAFT') return 'Lengkapi target dan bukti';
		if (contract.status === 'SUBMITTED') return 'Menunggu keputusan reviewer';
		return 'Tidak ada tugas mendesak';
	}

	function staffDashboardTaskDetail(): string {
		const contract = staffDashboardContract();
		if (!contract) return 'Mulai dari katalog KPI jalur kerja Anda.';
		if (contract.status === 'REVISION') return 'Buka KPI saya untuk membaca catatan dan mengirim perbaikan.';
		if (contract.status === 'DRAFT') return 'Isi realisasi dan bukti pada setiap indikator yang dipilih.';
		if (contract.status === 'SUBMITTED') return 'Tidak ada input yang perlu dilakukan sampai reviewer mengirim keputusan.';
		return 'Periode aktif tidak membutuhkan tindakan baru.';
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

	function currentStaffReadOnlyOutcome(): 'VERIFIED' | 'FINAL' | null {
		const record = currentStaffAssignmentRecord();
		const status = effectiveStaffAssignmentStatus(record);
		return status === 'VERIFIED' || status === 'FINAL' ? status : null;
	}

	function currentStaffReadOnlyRecord(): DemoStaffAssignment | null {
		return currentStaffReadOnlyOutcome() ? currentStaffAssignmentRecord() : null;
	}

	function currentStaffReadOnlyReviewerNote(): string {
		return currentStaffFinalization()?.note ?? currentStaffReviewDecision()?.note ?? 'Reviewer belum menambahkan catatan.';
	}

	function currentStaffReadOnlyFinalDate(): string | null {
		return currentStaffFinalization()?.finalizedAt ?? null;
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
		if (field === 'actual') return edit?.actual ?? (item.actual === null ? '' : String(item.actual));
		return edit?.evidence ?? item.evidence[0]?.reference ?? '';
	}

	function assessmentMissingCounts(contract: PerformanceContract): { actual: number; evidence: number; unsaved: number } {
		let actual = 0;
		let evidence = 0;
		let unsaved = 0;
		for (const item of contract.items) {
			if (!assessmentFieldValue(item, 'actual').trim()) actual += 1;
			if (!assessmentFieldValue(item, 'evidence').trim()) evidence += 1;
			if (itemDirty(item)) unsaved += 1;
		}
		return { actual, evidence, unsaved };
	}

	function assessmentStepHint(): string {
		if (assessmentStep === 1) return 'Isi realisasi untuk setiap indikator, lalu simpan item sebelum lanjut.';
		if (assessmentStep === 2) return 'Tambahkan referensi bukti dan catatan agar data dapat diperiksa reviewer.';
		return 'Periksa ringkasan, pastikan semua indikator lengkap, lalu ajukan ke reviewer.';
	}

	function assessmentNextBlockedReason(contract: PerformanceContract): string | null {
		const counts = assessmentMissingCounts(contract);
		if (assessmentStep === 1 && counts.actual > 0) return `${counts.actual} indikator belum memiliki realisasi.`;
		if (assessmentStep === 2 && counts.evidence > 0) return `${counts.evidence} indikator belum memiliki bukti.`;
		if (assessmentStep === 2 && counts.unsaved > 0) return 'Simpan perubahan indikator sebelum masuk ke ringkasan.';
		return null;
	}

	function assessmentSubmitBlockedReason(contract: PerformanceContract): string | null {
		const counts = assessmentMissingCounts(contract);
		if (counts.actual > 0) return `${counts.actual} indikator belum memiliki realisasi.`;
		if (counts.evidence > 0) return `${counts.evidence} indikator belum memiliki bukti.`;
		if (counts.unsaved > 0) return 'Simpan perubahan indikator sebelum mengajukan.';
		return submitBlockedReason(contract);
	}

	function moveAssessmentStep(next: SelfAssessmentStep): void {
		if (!selectedContract || !isBranchSelfAssessment()) return;
		if (next > assessmentStep) {
			const reason = assessmentNextBlockedReason(selectedContract);
			if (reason) {
				errorMessage = reason;
				return;
			}
		}
		errorMessage = '';
		assessmentStep = next;
	}

	function activeMonthlyCycle(): Cycle | null {
		return cycles.find((cycle) => cycle.status === 'OPEN' && cycle.cadence === 'MONTHLY') ?? null;
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

	function existingMonthlyContract(): PerformanceContract | null {
		const cycle = activeMonthlyCycle();
		return cycle ? existingContractForCycle(cycle) : null;
	}

	function submitBlockedReason(contract: PerformanceContract): string | null {
		if (!contract.reviewer_employee_id) return 'Reviewer belum ditetapkan untuk kontrak ini.';
		if (contract.items.some((item) => !['VALID', 'NOT_APPLICABLE'].includes(item.state))) {
			return 'Lengkapi target, realisasi, dan bukti setiap indikator sebelum pengajuan.';
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
		const activeCycle = cycles.find((cycle) => cycle.status === 'OPEN') ?? cycles[0];
		if (!activeCycle) {
			dashboard = null;
			contracts = [];
			return;
		}

		const query = '?cycle_id=' + encodeURIComponent(activeCycle.id);
		const [dashboardResponse, firstContractPage] = await Promise.all([
			api<Envelope<Dashboard>>('/api/v1/dashboard' + query),
			api<{ data: PerformanceContract[]; meta: Record<string, number> }>(
				'/api/v1/contracts' + query + '&per_page=100'
			)
		]);
		dashboard = dashboardResponse.data;
		const allContracts = [...firstContractPage.data];
		const pageSize = firstContractPage.meta.per_page || 100;
		const total = firstContractPage.meta.total || allContracts.length;
		const pageCount = Math.ceil(total / pageSize);
		for (let page = 2; page <= pageCount; page += 1) {
			const pageResponse = await api<{ data: PerformanceContract[]; meta: Record<string, number> }>(
				'/api/v1/contracts' + query + '&per_page=' + pageSize + '&page=' + page
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

	async function openSelfAssignment(): Promise<void> {
		setView('assignment');
		if (!canSelfAssignRole() || !activeMonthlyCycle() || existingMonthlyContract() || catalogLoading || catalogLoaded) return;

		catalogLoading = true;
		errorMessage = '';
		try {
			const track = staffTrack();
			const positionMapping = currentStaffPositionMapping();
			if (positionMapping || currentUser?.access_role === 'PEGAWAI') {
				const mappedItems = positionMapping?.status === 'ACTIVE' && positionMappingItems(positionMapping).length > 0 ? positionMappingItems(positionMapping) : [];
				kpiDefinitions = positionMapping ? mappedItems.map((item) => demoDefinitionFromPositionMapping(item, positionMapping)) : [];
				targetDrafts = Object.fromEntries(
					kpiDefinitions.map((definition) => [definition.id, String(definition.sample_target)])
				);
				const savedAssignment = currentStaffAssignmentRecord();
				const mappedIds = new Set(kpiDefinitions.map((definition) => definition.id));
				selectedDefinitionIds = savedAssignment
					? savedAssignment.selectedIds.filter((id) => mappedIds.has(id))
					: [];
				staffAssignmentStatus = effectiveStaffAssignmentStatus(savedAssignment);
				catalogLoaded = true;
				return;
			}
			if (track) {
				const mapping = mappingForTrack(track);
				const mappedItems = mappedCatalogItemsForTrack(track);
				kpiDefinitions = mappedItems.map((item) => demoDefinitionFromCatalogItem(item, mapping));
				targetDrafts = Object.fromEntries(
					kpiDefinitions.map((definition) => [definition.id, String(definition.sample_target)])
				);
				const savedAssignment = currentStaffAssignmentRecord();
				const mappedIds = new Set(kpiDefinitions.map((definition) => definition.id));
				selectedDefinitionIds = savedAssignment
					? savedAssignment.selectedIds.filter((id) => mappedIds.has(id))
					: [];
				staffAssignmentStatus = effectiveStaffAssignmentStatus(savedAssignment);
				catalogLoaded = true;
				return;
			}

			const response = await api<Envelope<KpiDefinition[]>>('/api/v1/kpi-definitions?per_page=100');
			kpiDefinitions = response.data;
			targetDrafts = Object.fromEntries(
				response.data.map((definition) => [definition.id, String(definition.sample_target)])
			);
			catalogLoaded = true;
		} catch (error) {
			errorMessage = error instanceof Error ? error.message : 'Katalog KPI belum dapat dimuat.';
		} finally {
			catalogLoading = false;
		}
	}

	async function openExistingMonthlyContract(): Promise<void> {
		const contract = existingMonthlyContract();
		if (!contract) return;
		setView('mine');
		await openContract(contract.id);
	}

	function toggleDefinition(id: string, checked: boolean): void {
		selectedDefinitionIds = checked
			? [...new Set([...selectedDefinitionIds, id])]
			: selectedDefinitionIds.filter((selectedId) => selectedId !== id);
	}

	function updateTargetDraft(id: string, value: string): void {
		targetDrafts = { ...targetDrafts, [id]: value };
	}

	async function createSelfAssignment(): Promise<void> {
		const cycle = activeMonthlyCycle();
		if (!cycle) {
			errorMessage = 'Belum ada siklus KPI bulanan yang terbuka.';
			return;
		}
		if (existingContractForCycle(cycle)) {
			errorMessage = 'Kontrak untuk siklus ini sudah tersedia.';
			return;
		}

		const selectedDefinitions = kpiDefinitions.filter((definition) =>
			selectedDefinitionIds.includes(definition.id)
		);
		if (selectedDefinitions.length === 0) {
			errorMessage = 'Pilih minimal satu indikator KPI.';
			return;
		}

		for (const definition of selectedDefinitions) {
			const target = Number(targetDrafts[definition.id]);
			if (!Number.isFinite(target) || (definition.type === 'Z' ? target !== 0 : target <= 0)) {
				errorMessage = 'Periksa target setiap indikator yang dipilih.';
				return;
			}
			if (['Q', 'L'].includes(definition.type) && target > 100) {
				errorMessage = 'Target indikator tipe Q dan L maksimal 100.';
				return;
			}
		}

		if (staffTrack()) {
			if (staffAssignmentStatus === 'SUBMITTED') {
				errorMessage = 'Self-assessment sudah diajukan dan tidak dapat diubah pada demo ini.';
				return;
			}
			saveStaffAssignmentDraft();
			errorMessage = '';
			successMessage = 'Draf self-assessment demo tersimpan. Periksa ringkasan lalu ajukan ke reviewer.';
			return;
		}

		busy = true;
		errorMessage = '';
		successMessage = '';
		try {
			const response = await api<Envelope<PerformanceContract>>('/api/v1/contracts', {
				method: 'POST',
				body: JSON.stringify({
					cycle_id: cycle.id,
					items: selectedDefinitions.map((definition) => ({
						kpi_definition_id: definition.id,
						target: Number(targetDrafts[definition.id])
					}))
				})
			});
			setView('mine');
			await refreshWorkspace();
			await openContract(response.data.id);
			successMessage = 'Draf KPI tersimpan. Realisasi dapat diisi setelah target disepakati.';
		} catch (error) {
			errorMessage = error instanceof Error ? error.message : 'Draf KPI belum dapat dibuat.';
		} finally {
			busy = false;
		}
	}

	async function login(): Promise<void> {
		if (!selectedIdentityId) {
			errorMessage = 'Pilih identitas demo terlebih dahulu.';
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
		} catch (error) {
			errorMessage = error instanceof Error ? error.message : 'Login demo belum berhasil.';
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

			try {
				const history = await api<{ data: AuditEvent[] }>(
					'/api/v1/audit-events?contract_id=' + encodeURIComponent(id) + '&per_page=100'
				);
				auditEvents = history.data;
			} catch {
				auditLoadFailed = true;
			}
		} catch (error) {
			errorMessage = error instanceof Error ? error.message : 'Detail kontrak belum dapat dimuat.';
		}
	}

	function canEditActual(contract: PerformanceContract): boolean {
		return Boolean(
			['DRAFT', 'REVISION'].includes(contract.status) &&
				currentUser &&
				(contract.subject_id === currentUser.employee_id || hasAccessRole('SDM'))
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
			edit.actual !== (item.actual === null ? '' : String(item.actual)) ||
			edit.evidence !== (item.evidence[0]?.reference ?? '') ||
			edit.note !== (item.note ?? '') ||
			Number(edit.target) !== item.target ||
			Number(edit.weightPercent) / 100 !== item.weight
		);
	}

	async function saveItem(item: ContractItem): Promise<void> {
		if (!selectedContract) return;
		const edit = edits[item.id];
		if (!edit) return;
		const payload: Record<string, unknown> = {};

		if (canEditActual(selectedContract)) {
			payload.actual = edit.actual.trim() === '' ? null : Number(edit.actual);
			payload.note = edit.note;
			payload.evidence = edit.evidence.trim()
				? [{ label: 'Referensi demo', reference: edit.evidence.trim() }]
				: [];
			payload.state = edit.actual.trim() !== '' && edit.evidence.trim() ? 'VALID' : 'PENDING';
		}
		if (canEditTarget(selectedContract)) {
			if (Number(edit.target) !== item.target) payload.target = Number(edit.target);
			if (Number(edit.weightPercent) / 100 !== item.weight) {
				payload.weight = Number(edit.weightPercent) / 100;
			}
			if ('target' in payload || 'weight' in payload) payload.reason = edit.reason;
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
			successMessage = 'Perubahan item tersimpan.';
			await refreshWorkspace();
		} catch (error) {
			errorMessage = error instanceof Error ? error.message : 'Perubahan belum tersimpan.';
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

	function setView(next: 'overview' | 'mine' | 'reviews' | 'assignment' | 'periods' | 'history' | 'monitoring' | 'catalog' | 'mapping' | 'positions' | 'ssoMappings' | 'audit' | 'comparison'): void {
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
	}

	function pageTitle(): string {
		if (view === 'mine') return 'KPI saya';
		if (view === 'reviews') {
			if (canReviewInbox()) return 'Inbox evaluasi';
			return hasAccessRole('SDM') ? 'Finalisasi dan tinjauan' : 'Tinjauan kontrak';
		}
		if (view === 'assignment') return staffTrack() ? `Susun KPI · ${staffTrackLabel()}` : 'Susun KPI saya';
		if (view === 'periods') return 'Atur periode';
		if (view === 'monitoring') return 'Monitoring Admin KPI';
		if (view === 'audit') return 'Audit trail keputusan';
		if (view === 'comparison') return 'Banding periode';
		if (view === 'catalog') return 'Katalog KPI dan target';
		if (view === 'mapping') return 'Penetapan KPI ke jabatan';
		if (view === 'positions') return 'Master jabatan';
		if (view === 'ssoMappings') return 'Mapping identitas SSO';
		if (view === 'history') return 'Riwayat Penilaian';
		if (view === 'overview' && canViewStaffHistory()) return 'Dashboard saya';
		return 'Ringkasan kinerja';
	}

	onMount(() => {
		void initialize();
	});
</script>

<svelte:head>
	<title>KPI Demo Perbankan</title>
	<meta name="description" content="Demo alur KPI dengan data organisasi dan capaian sintetis." />
</svelte:head>

{#if loading}
	<main class="loading-screen" aria-live="polite">
		<div class="loading-mark" aria-hidden="true"></div>
		<p>Memuat ruang demo…</p>
	</main>
{:else if !currentUser}
	<main class="login-page">
		<div class="login-frame">
			<section class="login-context" aria-labelledby="login-title">
				<div class="brand-line">
					<span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 40 40"><path d="M8 29V11h5l7 9 7-9h5v18h-5V18l-7 9-7-9v11z"/><path class="brand-accent" d="M31 8h4v4h-4z"/></svg></span>
					<div><strong>KPI Perbankan</strong><span>Ruang demo internal</span></div>
				</div>

				<div class="login-context-copy">
					<p class="login-step">RUANG KERJA KPI</p>
					<h1 id="login-title">Masuk dengan akun kerja Anda</h1>
					<p>Profil kerja menentukan jabatan dan akses yang dipakai di ruang KPI.</p>
				</div>

				<dl class="login-facts">
					<div><dt>Periode</dt><dd>Diatur Admin</dd></div>
					<div><dt>Cakupan awal</dt><dd>Pusat, Korwil, satu cabang</dd></div>
					<div><dt>Dataset</dt><dd>Data sintetis</dd></div>
				</dl>

				<p class="login-context-note">Periode tersedia 1, 3, 6, atau 12 bulan. Default rancangan mengikuti triwulan PRD.</p>
			</section>

			<section class="login-card" aria-labelledby="identity-title">
			<div class="login-copy">
				<Badge variant="outline" class="demo-badge">Data KPI sintetis</Badge>
				<h2 id="identity-title">Masuk ke ruang KPI</h2>
				<p>Gunakan akun kerja atau pilih identitas demo lokal untuk mencoba peran.</p>
			</div>
			<div class="login-mode-switch" role="group" aria-label="Metode masuk">
				<button type="button" class:active={loginMode === 'SSO'} aria-pressed={loginMode === 'SSO'} onclick={() => { loginMode = 'SSO'; errorMessage = ''; }}>Akun kerja SSO</button>
				<button type="button" class:active={loginMode === 'DEMO'} aria-pressed={loginMode === 'DEMO'} onclick={() => { loginMode = 'DEMO'; errorMessage = ''; }}>User demo</button>
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

			<label class="field-label" for="identity-search">Cari nama, peran, atau unit</label>
			<input
				id="identity-search"
				class="text-input search-input"
				type="search"
				bind:value={identitySearch}
				placeholder="Contoh: Kepala Cabang atau Pemasaran"
				autocomplete="off"
			/>
			<div class="identity-groups" aria-label="Kelompok user dummy">
				{#if identitiesLoading}
					<p class="empty-inline" role="status">Memuat user demo…</p>
				{:else if identitiesError}
					<div class="identity-load-state" role="alert">
						<p>{identitiesError}</p>
						<button type="button" class="identity-reload" onclick={() => void loadDemoIdentities()}>Muat ulang daftar</button>
					</div>
				{:else if visibleIdentityGroups().length === 0}
					<p class="empty-inline">Tidak ada user demo yang cocok dengan pencarian.</p>
				{:else}
					{#each visibleIdentityGroups() as group (group.key)}
						<section class="identity-group" aria-labelledby={'identity-group-' + group.key}>
							<div class="identity-group-heading">
								<h3 id={'identity-group-' + group.key}>{group.label}</h3>
								<p>{group.description}</p>
							</div>
							<div class="identity-list identity-group-list">
								{#each group.identities as identity (identity.id)}
									<button
										type="button"
										class="identity-option"
										class:selected={selectedIdentityId === identity.id}
										aria-pressed={selectedIdentityId === identity.id}
										onclick={() => (selectedIdentityId = identity.id)}
									>
										<span class="identity-avatar">{initials(identity.display_name)}</span>
										<span class="identity-copy">
											<strong>{identity.display_name}</strong>
										<small>{identityRoleLabel(identity)} · {identity.org_unit_name}{identity.self_assignment_available ? ' · Bisa susun KPI baru' : ''}</small>
										</span>
									</button>
								{/each}
							</div>
						</section>
					{/each}
				{/if}
			</div>

			{#if selectedIdentity()}
				<div class="identity-selection" role="status" aria-live="polite">
					<span class="selection-label">IDENTITAS DIPILIH</span>
					<strong>{selectedIdentity()?.display_name}</strong>
					<span>{identityRoleLabel(selectedIdentity())} · {selectedIdentity()?.org_unit_name}</span>
				</div>
			{/if}

			<Button class="login-button" size="lg" disabled={busy || !selectedIdentityId} onclick={login}>
				{busy ? 'Menyiapkan sesi…' : 'Masuk ke demo'}
			</Button>
			{/if}
			</section>
		</div>
	</main>
{:else}
	<div class="app-shell">
		<aside class="sidebar" aria-label="Navigasi utama">
			<div class="sidebar-brand">
				<span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 40 40"><path d="M8 29V11h5l7 9 7-9h5v18h-5V18l-7 9-7-9v11z"/><path class="brand-accent" d="M31 8h4v4h-4z"/></svg></span>
				<div><strong>KPI Perbankan</strong><span>Performance workspace</span></div>
			</div>
			<p class="nav-caption">RUANG KERJA</p>
			<nav class="main-nav">
				<button class:active={view === 'overview'} aria-current={view === 'overview' ? 'page' : undefined} onclick={() => setView('overview')}>
					<span class="nav-symbol" aria-hidden="true">▦</span><span>{canViewStaffHistory() ? 'Dashboard saya' : 'Ringkasan'}</span>
				</button>
				<button class:active={view === 'mine'} aria-current={view === 'mine' ? 'page' : undefined} onclick={() => void openMine()}>
					<span class="nav-symbol" aria-hidden="true">◎</span><span>KPI saya</span>
				</button>
				{#if canSelfAssignRole()}
					<button class:active={view === 'assignment'} aria-current={view === 'assignment' ? 'page' : undefined} onclick={() => void openSelfAssignment()}>
						<span class="nav-symbol" aria-hidden="true">+</span><span>Susun KPI</span>
					</button>
				{/if}
				{#if canViewStaffHistory()}
					<button class:active={view === 'history'} aria-current={view === 'history' ? 'page' : undefined} onclick={openHistory}>
						<span class="nav-symbol" aria-hidden="true">▤</span><span>Riwayat</span>
					</button>
				{/if}
				<button class:active={view === 'reviews'} aria-current={view === 'reviews' ? 'page' : undefined} onclick={() => (canReviewInbox() ? openReviews() : setView('reviews'))}>
					<span class="nav-symbol" aria-hidden="true">✓</span><span>{canReviewInbox() ? 'Inbox evaluasi' : 'Tinjauan'}</span>
					{#if dashboard && dashboard.contracts_needing_action > 0}<span class="nav-count">{dashboard.contracts_needing_action}</span>{/if}
				</button>
				{#if hasAdminAccess()}
					<button class:active={view === 'monitoring'} aria-current={view === 'monitoring' ? 'page' : undefined} onclick={openMonitoring}>
						<span class="nav-symbol" aria-hidden="true">◴</span><span>Monitoring</span>
					</button>
					<button class:active={view === 'audit'} aria-current={view === 'audit' ? 'page' : undefined} onclick={openAuditTrail}>
						<span class="nav-symbol" aria-hidden="true">A</span><span>Audit trail</span>
					</button>
					<button class:active={view === 'comparison'} aria-current={view === 'comparison' ? 'page' : undefined} onclick={openPeriodComparison}>
						<span class="nav-symbol" aria-hidden="true">%</span><span>Banding periode</span>
					</button>
					<button class:active={view === 'catalog'} aria-current={view === 'catalog' ? 'page' : undefined} onclick={openCatalog}>
						<span class="nav-symbol" aria-hidden="true">≡</span><span>Katalog KPI</span>
					</button>
					<button class:active={view === 'mapping'} aria-current={view === 'mapping' ? 'page' : undefined} onclick={openAssignmentMapping}>
						<span class="nav-symbol" aria-hidden="true">↳</span><span>Penetapan</span>
					</button>
					<button class:active={view === 'positions'} aria-current={view === 'positions' ? 'page' : undefined} onclick={openPositions}>
						<span class="nav-symbol" aria-hidden="true">J</span><span>Master jabatan</span>
					</button>
					<button class:active={view === 'ssoMappings'} aria-current={view === 'ssoMappings' ? 'page' : undefined} onclick={openSsoMappings}>
						<span class="nav-symbol" aria-hidden="true">S</span><span>Mapping SSO</span>
					</button>
					<button class:active={view === 'periods'} aria-current={view === 'periods' ? 'page' : undefined} onclick={() => setView('periods')}>
						<span class="nav-symbol" aria-hidden="true">◷</span><span>Periode KPI</span>
					</button>
				{/if}
			</nav>
			<div class="sidebar-foot">
				<span class="demo-indicator"></span>
				<div><strong>Lingkungan demo</strong><small>Seluruh data bersifat sintetis</small></div>
			</div>
		</aside>

		<div class="workspace">
			<header class="topbar">
				<button class="theme-toggle" type="button" aria-label={isDark ? 'Gunakan tema terang' : 'Gunakan tema gelap'} onclick={toggleTheme}>{isDark ? '☀' : '◐'} <span>{isDark ? 'Terang' : 'Gelap'}</span></button>
				<div class="breadcrumb">KPI Perbankan <span>/</span> {pageTitle()}</div>
				<div class="user-menu">
					<div class="user-avatar">{initials(currentUser.display_name)}</div>
					<div class="user-details"><strong>{currentUser.display_name}</strong><span>{roleListLabel(currentUser.access_role, currentUser.access_roles)}</span></div>
					<Button variant="outline" size="sm" disabled={busy} onclick={logout}>Keluar</Button>
				</div>
			</header>

			<main class="page-content">
				<div class="page-heading">
					<div>
						<p class="eyebrow">{view === 'periods' ? 'KONFIGURASI ADMIN' : view === 'ssoMappings' ? 'AKSES AKUN KERJA' : view === 'mapping' ? 'PENETAPAN KPI' : view === 'positions' ? 'MASTER JABATAN' : view === 'catalog' ? 'KATALOG KPI' : view === 'monitoring' ? 'MONITORING ADMIN' : view === 'audit' ? 'AUDIT TRAIL' : view === 'comparison' ? 'PERBANDINGAN PERIODE' : view === 'history' ? 'RIWAYAT PENILAIAN' : view === 'reviews' && canReviewInbox() ? 'INBOX EVALUASI' : view === 'assignment' ? (activeMonthlyCycle()?.name ?? 'PERIODE AKTIF') : (dashboard?.cycle.name ?? 'DEMO LOKAL')}</p>
						<h1>{pageTitle()}</h1>
						<p class="page-subtitle">{currentUser.org_unit_name} · {currentUser.position_name}</p>
					</div>
					{#if dashboard?.cycle && view !== 'assignment' && view !== 'periods' && view !== 'history' && view !== 'monitoring' && view !== 'catalog' && view !== 'mapping' && view !== 'positions' && view !== 'ssoMappings' && view !== 'audit' && view !== 'comparison'}
						<div class="period-pill"><span class="period-dot"></span>{formatDate(dashboard.cycle.starts_at)} – {formatDate(dashboard.cycle.ends_at)}</div>
					{/if}
				</div>

				<div class="notice demo-notice" role="note">
					<span class="notice-mark" aria-hidden="true">i</span>
					<div><strong>Data dan rumus demo</strong><p>Target, realisasi, dan skor dibuat untuk simulasi. Nilai perilaku, nilai akhir, dan rating belum digunakan.</p></div>
				</div>
				{#if errorMessage}<p class="alert error-alert" role="alert">{errorMessage}</p>{/if}
				{#if successMessage}<p class="alert success-alert" role="status">{successMessage}</p>{/if}

				{#if view === 'overview' && dashboard}
					{#if canViewStaffHistory()}
						<section class="staff-dashboard" aria-labelledby="staff-dashboard-title">
							<div class="staff-dashboard-heading">
								<div>
									<p class="eyebrow">DASHBOARD SAYA · {staffTrackLabel()}</p>
									<h2 id="staff-dashboard-title">Fokus kerja periode aktif</h2>
									<p class="muted-copy">Satu tempat untuk melihat apa yang sudah selesai dan tindakan KPI berikutnya.</p>
								</div>
								<span class="staff-track-chip">Cabang Contoh 001</span>
							</div>

							<div class="staff-dashboard-top">
								<section class="staff-period-card" aria-labelledby="staff-period-title">
									<div class="staff-card-heading"><span class="dashboard-label">PERIODE AKTIF</span><span class="period-status-dot"></span></div>
									<h3 id="staff-period-title">{dashboard.cycle.name}</h3>
									<p>{formatDate(dashboard.cycle.starts_at)} – {formatDate(dashboard.cycle.ends_at)}</p>
									<div class="staff-period-meta"><span>Jalur</span><strong>{staffTrackLabel()}</strong></div>
								</section>
								<section class="staff-task-card" aria-labelledby="staff-task-title">
									<div class="staff-card-heading"><span class="dashboard-label">TUGAS BERIKUTNYA</span><span class="task-urgency">Prioritas</span></div>
									<h3 id="staff-task-title">{staffDashboardTaskLabel()}</h3>
									<p>{staffDashboardTaskDetail()}</p>
									<div class="staff-task-footer"><span>Deadline demo</span><strong>{formatDate(dashboard.cycle.ends_at)}</strong></div>
									{#if !staffDashboardContract() || ['DRAFT', 'REVISION'].includes(staffDashboardContract()?.status ?? '')}
										<Button class="staff-task-button" size="sm" onclick={() => void openSelfAssignment()}>{staffDashboardContract()?.status === 'REVISION' ? 'Buka revisi' : staffDashboardContract() ? 'Lanjutkan KPI' : 'Susun KPI sekarang'}</Button>
									{/if}
								</section>
							</div>

							<div class="staff-dashboard-signals" aria-label="Status KPI saya">
								<section class="staff-signal-card">
									<span class="dashboard-label">SELF-ASSIGNMENT</span>
									<strong>{staffDashboardAssignmentLabel()}</strong>
									<p>{staffDashboardAssignmentDetail()}</p>
									{#if staffDashboardContract()}<Badge variant={statusVariant(staffDashboardContract()?.status ?? 'DRAFT')}>{statusNames[staffDashboardContract()?.status ?? 'DRAFT']}</Badge>{/if}
								</section>
								<section class="staff-signal-card">
									<div class="staff-signal-heading"><span class="dashboard-label">PROGRES KPI</span><strong>{staffDashboardProgress().percent}%</strong></div>
									<div class="dashboard-progress" aria-label={'Progres KPI ' + staffDashboardProgress().percent + ' persen'}><span style={'width: ' + staffDashboardProgress().percent + '%'}></span></div>
									<p>{staffDashboardProgress().completed} dari {staffDashboardProgress().total} indikator lengkap.</p>
								</section>
								<section class="staff-signal-card">
									<span class="dashboard-label">TARGET VS REALISASI</span>
									<strong>{staffDashboardRealizationSummary()}</strong>
									<p>{staffDashboardContract() ? 'Ringkasan pengisian periode aktif.' : 'Belum ada snapshot pada periode aktif.'}</p>
								</section>
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
							<Card.Content><span class="stat-note">KPI lengkap pada kontrak demo</span></Card.Content>
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

				{#if view === 'mapping' && canManageAssignment()}
					<section class="mapping-panel" aria-labelledby="mapping-title">
						<div class="mapping-heading">
							<div>
								<p class="eyebrow">ADMIN KPI / SDM</p>
								<h2 id="mapping-title">Penetapan KPI ke jabatan</h2>
								<p class="muted-copy">Pilih indikator Aktif dari katalog, sesuaikan target dan bobot, lalu periksa paket yang akan muncul pada self-assignment.</p>
							</div>
							<div class="mapping-state-note"><strong>{assignmentSaveState === 'SAVED' ? 'Tersimpan dummy' : 'Draf penetapan'}</strong><span>Belum mengubah API Laravel.</span></div>
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
								<div class="mapping-section-heading"><div><p class="eyebrow">PREVIEW SELF-ASSIGNMENT</p><h3 id="mapping-preview-title">Paket KPI {catalogTrackLabel(assignmentTrack)}</h3></div><span>{assignmentSaveState === 'SAVED' ? 'Dummy tersimpan' : 'Belum disimpan'}</span></div>
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
								<Button class="mapping-save-button" disabled={selectedAssignmentItems().length === 0 || Math.abs(assignmentWeightTotal() - 100) >= 0.01} onclick={saveDemoAssignmentMapping}>Simpan penetapan dummy</Button>
							</aside>
						</div>
						<p class="mapping-footnote">Penetapan ini menjadi preview kebijakan per jabatan. Data demo belum memengaruhi self-assignment staf sampai backend dan aturan inheritance dikunci.</p>
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
									<label for="sso-target-employee">User KPI demo
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
								<p class="muted-copy">Pilih jabatan untuk membaca organisasi dan indikator Aktif yang sudah dipetakan pada data demo.</p>
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
									<div class="position-deactivate-confirm" role="alert"><div><strong>Nonaktifkan jabatan ini?</strong><p>Jabatan tetap tersimpan di data demo, tetapi tidak lagi menjadi pilihan aktif untuk mapping berikutnya.</p></div><div class="position-confirm-actions"><Button variant="outline" size="sm" onclick={cancelPositionDeactivation}>Batal</Button><Button variant="destructive" size="sm" onclick={confirmPositionDeactivation}>Ya, nonaktifkan</Button></div></div>
								{/if}
								<p class="position-detail-footnote">Target dan bobot di atas adalah inherited read-only. Perubahan kebijakan dilakukan Admin melalui Penetapan KPI dan akan menjadi langkah berikutnya untuk API.</p>
							</article>
						</div>

						{#if positionEditorOpen}
							<section class="position-editor-panel" aria-labelledby="position-editor-title">
								<div class="position-editor-heading"><div><p class="eyebrow">{positionEditorMode === 'CREATE' ? 'CREATE DUMMY' : 'UPDATE DUMMY'}</p><h3 id="position-editor-title">{positionEditorTitle()}</h3><p>Perubahan hanya tersimpan di browser selama sesi demo.</p></div><Button variant="outline" size="sm" onclick={closePositionEditor}>Tutup editor</Button></div>
								<form class="position-editor-form" onsubmit={(event) => { event.preventDefault(); savePositionDemo(); }}>
									<div class="position-editor-fields"><label>Nama jabatan<input id="position-form-name" class="text-input" bind:value={positionFormName} placeholder="Contoh: Kepala Sub Bidang Dana" required /></label><label>Level<input class="text-input" bind:value={positionFormLevel} placeholder="Contoh: Supervisor 2" required /></label><label>Organisasi<input class="text-input" bind:value={positionFormOrgUnit} placeholder="Contoh: Cabang Contoh 001" required /></label><label>Cakupan kerja<input class="text-input" bind:value={positionFormScope} placeholder="Contoh: Pengawasan dana cabang" required /></label><label>Jalur mapping<select class="text-input" value={positionFormTrack} onchange={(event) => changePositionFormTrack(event.currentTarget.value as DemoPositionTrack)}><option value="BUSINESS">Pemasaran / Bisnis</option><option value="NON_BUSINESS">Operasional / Non-bisnis</option><option value="BOTH">Gabungan Pemasaran + Operasional</option></select></label><label>Status<select class="text-input" bind:value={positionFormStatus}><option value="ACTIVE">Aktif</option><option value="INACTIVE">Nonaktif</option></select></label></div>
									<div class="position-editor-mapping"><div class="position-editor-section-heading"><div><p class="eyebrow">MAPPING KPI</p><h4>Pilih indikator Aktif</h4></div><span>{positionFormSelectedIds.length} dipilih</span></div>{#if positionFormCatalogItems().length === 0}<div class="position-editor-empty" role="status"><strong>Belum ada indikator Aktif</strong><p>Aktifkan indikator dari Katalog KPI sebelum membuat mapping untuk jabatan ini.</p></div>{:else}<div class="position-editor-kpi-list">{#each positionFormCatalogItems() as item (item.id)}<article class="position-editor-kpi" class:selected={positionFormSelectedIds.includes(item.id)}><label class="position-editor-kpi-choice"><input type="checkbox" checked={positionFormSelectedIds.includes(item.id)} onchange={(event) => togglePositionFormItem(item.id, event.currentTarget.checked)} /><span><strong>{item.name}</strong><small>{item.code} / {catalogTrackLabel(item.track)} / {item.unit}</small></span></label>{#if positionFormSelectedIds.includes(item.id)}<div class="position-editor-kpi-fields"><label>Target<input class="text-input" type="number" min="0.01" step="any" value={positionFormTargets[item.id] ?? String(item.target)} oninput={(event) => updatePositionFormTarget(item.id, event.currentTarget.value)} /></label><label>Bobot (%)<input class="text-input" type="number" min="0.01" max="100" step="0.01" value={positionFormWeights[item.id] ?? String(Math.round(item.weight * 10000) / 100)} oninput={(event) => updatePositionFormWeight(item.id, event.currentTarget.value)} /></label></div>{/if}</article>{/each}</div>{/if}</div>
									<div class="position-editor-preview" role="status" aria-live="polite"><div class="position-editor-section-heading"><div><p class="eyebrow">PREVIEW SELF-ASSIGNMENT</p><h4>Yang akan tampil ke staf</h4></div><span>{positionFormSelectedItems().length} KPI</span></div>{#if positionFormSelectedItems().length === 0}<p class="position-editor-preview-empty">Belum ada indikator dipilih. Setelah disimpan, staf akan melihat empty state mapping.</p>{:else}<div class="position-editor-preview-list">{#each positionFormSelectedItems() as item (item.id)}<div><span><strong>{item.name}</strong><small>{item.unit}</small></span><span>Target {positionFormTargets[item.id] ?? item.target} · Bobot {positionFormWeights[item.id] ?? Math.round(item.weight * 10000) / 100}%</span></div>{/each}</div>{/if}<p class="position-editor-preview-footnote">Preview ini mengikuti perubahan form saat ini dan baru menjadi sumber self-assignment setelah tombol simpan digunakan.</p></div>
									<div class="position-editor-footer"><p><strong>{positionFormWeightStatus()}</strong><span>Total bobot {formatNumber(positionFormWeightTotal(), 2)}% dari 100%.</span><small class="position-editor-preview-note">Preview self-assignment berubah setelah disimpan.</small></p><div><Button type="button" variant="outline" onclick={closePositionEditor}>Batal</Button><Button type="submit">Simpan jabatan dummy</Button></div></div>
								</form>
							</section>
						{/if}
						<p class="position-mapping-footnote">Master jabatan ini membatasi konteks organisasi pada Cabang Contoh 001 dan dua divisi demo. Semua data masih sintetis untuk validasi UI/UX.</p>
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
							<div class="catalog-demo-note"><strong>Data sintetis</strong><span>Perubahan hanya tersimpan di browser.</span></div>
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
								<div class="catalog-section-heading"><div><p class="eyebrow">KATALOG DEMO</p><h3 id="catalog-list-title">Indikator tersedia</h3></div><span>Target dan bobot contoh</span></div>
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
								<div class="catalog-section-heading"><div><p class="eyebrow">TAMBAH DUMMY</p><h3>Tambah indikator</h3></div><span>Frontend-only</span></div>
								<label class="catalog-field">Jalur<select class="text-input" bind:value={catalogFormTrack}><option value="BUSINESS">Pemasaran / Bisnis</option><option value="NON_BUSINESS">Operasional / Non-bisnis</option></select></label>
								<div class="catalog-field-grid"><label class="catalog-field">Kode <span class="catalog-optional">Opsional</span><input class="text-input" bind:value={catalogFormCode} placeholder="Contoh: DEMO-P-05" /></label><label class="catalog-field">Kelompok<input class="text-input" bind:value={catalogFormGroup} placeholder="Contoh: Dana" required /></label></div>
								<label class="catalog-field">Nama indikator<input class="text-input" bind:value={catalogFormName} placeholder="Contoh: Aktivitas nasabah demo" required /></label>
								<div class="catalog-field-grid"><label class="catalog-field">Tipe<select class="text-input" bind:value={catalogFormType}><option value="Q">Kuantitatif</option><option value="N">Numerik durasi</option><option value="L">Batas maksimum</option><option value="Z">Zero tolerance</option></select></label><label class="catalog-field">Unit<input class="text-input" bind:value={catalogFormUnit} placeholder="%, rekening, menit" required /></label></div>
								<div class="catalog-field-grid"><label class="catalog-field">Target<input class="text-input" type="number" min={catalogFormType === 'Z' ? 0 : 0.01} max={['Q', 'L'].includes(catalogFormType) ? 100 : undefined} step="any" bind:value={catalogFormTarget} placeholder="100" required /></label><label class="catalog-field">Bobot (%)<input class="text-input" type="number" min="0.01" max="100" step="0.01" bind:value={catalogFormWeight} placeholder="20" required /></label></div>
								<label class="catalog-field">Sumber data demo<input class="text-input" bind:value={catalogFormSource} placeholder="Contoh: Rekap core demo" required /></label>
								<label class="catalog-field">State awal<select class="text-input" bind:value={catalogFormState}><option value="DRAFT">Draft</option><option value="ACTIVE">Aktif</option></select></label>
								<div class="catalog-form-footer"><p><strong>Belum tersambung API</strong><span>Simpan hanya untuk sesi demo ini.</span></p><Button type="submit">Simpan indikator dummy</Button></div>
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
							<div class="monitoring-stat-card"><span>Total kontrak</span><strong>{monitoringRecordsForPeriod().length}</strong><small>Cabang Contoh 001 · {selectedAdminPeriodLabel()}</small></div>
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
								<p class="monitoring-card-footnote">Cakupan demo saat ini hanya Cabang Contoh 001.</p>
							</section>
						</div>

						<section class="monitoring-table-panel" aria-labelledby="monitoring-table-title">
							<div class="monitoring-table-heading"><div><p class="eyebrow">TINDAKAN ADMIN</p><h3 id="monitoring-table-title">Kontrak terfilter</h3><p class="muted-copy">Gunakan filter untuk melihat kontrak dari periode, jalur, dan status yang dipilih.</p></div><span class="monitoring-table-count">{visibleMonitoringRecords().length} kontrak</span></div>
							<div class="monitoring-filters" aria-label="Filter monitoring kontrak">
								<label>Jalur<select class="text-input" bind:value={monitoringTrackFilter}><option value="ALL">Semua jalur</option><option value="BUSINESS">Pemasaran / Bisnis</option><option value="NON_BUSINESS">Operasional / Non-bisnis</option></select></label>
								<label>Status<select class="text-input" bind:value={monitoringStatusFilter}><option value="ALL">Semua status aktif</option><option value="DRAFT">Draf</option><option value="SUBMITTED">Diajukan</option><option value="REVISION">Revisi</option><option value="VERIFIED">Terverifikasi</option><option value="FINAL">Final</option></select></label>
							</div>
							{#if visibleMonitoringRecords().length === 0}
								<div class="monitoring-empty" role="status"><strong>Tidak ada kontrak sesuai filter</strong><p>Periode, jalur, atau status ini belum memiliki kontrak pada data demo.</p></div>
							{:else}
								<div class="table-scroll monitoring-table-scroll">
									<table class="monitoring-table"><thead><tr><th scope="col">Pegawai</th><th scope="col">Jalur / periode</th><th scope="col">Status</th><th scope="col">Deadline</th><th scope="col">Perhatian</th><th scope="col"><span class="sr-only">Audit</span></th></tr></thead><tbody>
										{#each visibleMonitoringRecords() as record (record.id)}
											<tr><td><strong class="table-primary">{record.employeeName}</strong><span class="table-secondary">{record.positionName}</span></td><td><strong class="table-primary">{monitoringTrackLabel(record.track)}</strong><span class="table-secondary">{record.periodName}</span></td><td><Badge variant={statusVariant(record.status)}>{statusNames[record.status]}</Badge></td><td><span class={'monitoring-deadline ' + monitoringAgeTone(record)}>{formatDate(record.dueAt)}</span><span class="table-secondary">{record.submittedAt ? 'Diajukan ' + formatDate(record.submittedAt) : 'Belum diajukan'}</span></td><td><strong class="monitoring-attention">{record.attention}</strong><span class="table-secondary">{record.score === null ? 'Skor belum dihitung' : 'Skor demo ' + formatNumber(record.score, 1)}</span></td><td class="monitoring-table-action"><Button variant="outline" size="sm" aria-label={'Lihat audit ' + record.employeeName} onclick={() => openAuditForMonitoring(record)}>Lihat audit</Button></td></tr>
										{/each}
									</tbody></table>
								</div>
							{/if}
						</section>
						<p class="monitoring-footnote">Data monitoring ini sintetis dan belum menggantikan laporan resmi. Admin hanya melihat ringkasan operasional pada step ini.</p>
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
							<div class="audit-trail-tools"><label class="admin-period-selector">Filter periode<select class="text-input" bind:value={adminPeriodFilter}><option value="ALL">Semua periode</option>{#each demoPeriods as period (period.id)}<option value={period.id}>{period.name}</option>{/each}</select></label><div class="audit-trail-context"><span>PERIODE TERPILIH</span><strong>{visibleAuditTrailEntries().length} kejadian</strong><small>{selectedAdminPeriodLabel()} · Cabang Contoh 001</small></div></div>
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
									<div><span>Skor demo</span><strong>{auditContractSummary()?.score === null ? 'Belum dihitung' : formatNumber(auditContractSummary()?.score ?? null, 1)}</strong></div>
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
							<div class="audit-trail-empty" role="status"><strong>Belum ada audit trail sesuai filter</strong><p>Perubahan filter akan menampilkan kejadian demo yang tersedia.</p></div>
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
										<p class="audit-trail-footnote">Audit trail ini hanya untuk membaca jejak keputusan demo. Perubahan data dilakukan melalui workflow yang berwenang.</p>
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
								<p class="muted-copy">Baca perubahan status kontrak dan jumlah final antar-periode demo tanpa mengubah data penilaian.</p>
							</div>
							<span class="period-comparison-badge">READ-ONLY · DATA DEMO</span>
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
									<div class="period-comparison-card-heading"><div><p class="eyebrow">PERIODE {index === 0 ? 'A' : 'B'}</p><h3>{comparisonPeriodFor(periodId)?.name ?? 'Periode belum tersedia'}</h3><span>{comparisonPeriodFor(periodId) ? `${periodDateLabel(comparisonPeriodFor(periodId)?.startsAt ?? '')} - ${periodDateLabel(comparisonPeriodFor(periodId)?.endsAt ?? '')}` : 'Belum ada snapshot demo'}</span></div>{#if comparisonPeriodFor(periodId)}<span class={'period-status-chip period-status-' + (comparisonPeriodFor(periodId)?.status ?? 'DRAFT').toLowerCase()}>{periodStatusNames[comparisonPeriodFor(periodId)?.status ?? 'DRAFT']}</span>{/if}</div>
									{#if comparisonSummaryFor(periodId)}
										<div class="period-comparison-total"><span>Total kontrak</span><strong>{comparisonSummaryFor(periodId)?.totalContracts}</strong><small>Snapshot dummy pada periode ini</small></div>
										<div class="period-comparison-section"><div class="period-comparison-section-heading"><h4>Distribusi status</h4><span>Kontrak</span></div><div class="period-comparison-status-list">{#each ['DRAFT', 'SUBMITTED', 'REVISION', 'VERIFIED', 'FINAL'] as status}<div><span><i class={'monitoring-status-dot monitoring-status-' + status.toLowerCase()}></i>{statusNames[status]}</span><strong>{comparisonStatusCount(periodId, status as MonitoringStatus)}</strong><Button class="period-comparison-action" variant="outline" size="sm" onclick={() => openMonitoringDrilldown(periodId, status as MonitoringStatus)}>Lihat kontrak</Button></div>{/each}</div></div>
										<div class="period-comparison-section"><div class="period-comparison-section-heading"><h4>Final per jalur</h4><span>{comparisonStatusCount(periodId, 'FINAL')} total</span></div><div class="period-comparison-final-list"><div><span>Pemasaran / Bisnis</span><strong>{comparisonFinalCount(periodId, 'BUSINESS')}</strong><Button class="period-comparison-action" variant="outline" size="sm" onclick={() => openMonitoringDrilldown(periodId, 'FINAL', 'BUSINESS')}>Lihat kontrak</Button></div><div><span>Operasional / Non-bisnis</span><strong>{comparisonFinalCount(periodId, 'NON_BUSINESS')}</strong><Button class="period-comparison-action" variant="outline" size="sm" onclick={() => openMonitoringDrilldown(periodId, 'FINAL', 'NON_BUSINESS')}>Lihat kontrak</Button></div></div></div>
									{:else}
										<div class="period-comparison-empty"><strong>Belum ada snapshot perbandingan</strong><p>Periode ini tersedia pada katalog periode, tetapi belum memiliki ringkasan demo.</p></div>
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
								<p class="muted-copy">Tentukan cadence, rentang tanggal, dan status awal sebelum periode dibuka untuk Pusat, Korwil, dan Cabang Contoh 001.</p>
							</div>
							<div class="period-admin-note"><strong>Default rancangan</strong><span>3 bulan · triwulan</span></div>
						</div>

						<div class="period-admin-grid">
							<form class="period-form" onsubmit={(event) => { event.preventDefault(); saveDemoPeriod(); }}>
								<div class="period-form-section">
									<label class="field-label" for="period-name">Nama periode</label>
									<input id="period-name" class="text-input" type="text" bind:value={periodName} placeholder="Contoh: Triwulan I 2027" />
									<p class="field-help">Nama ini akan tampil pada header periode dan laporan demo.</p>
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

								<div class="period-form-section">
									<label class="field-label" for="period-notes">Catatan admin <span class="optional-label">Opsional</span></label>
									<textarea id="period-notes" class="text-input textarea-input" rows="3" bind:value={periodNotes} placeholder="Contoh: periode pembuka untuk pilot cabang contoh"></textarea>
								</div>

								<div class="period-form-footer">
									<p><strong>Simpan sebagai data demo</strong><span>Belum dikirim ke API Laravel.</span></p>
									<Button type="submit" class="period-save-button">Simpan periode dummy</Button>
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
								<p class="period-preview-footnote">Preview ini hanya mengubah state frontend sampai halaman direfresh.</p>
							</aside>
						</div>

						<section class="period-history" aria-labelledby="period-history-title">
							<div class="period-history-heading">
								<div><p class="eyebrow">DATA DEMO</p><h3 id="period-history-title">Periode yang tersedia</h3></div>
								<span class="period-history-count">{demoPeriods.length} contoh</span>
							</div>
							<div class="period-history-list">
								{#each demoPeriods as period (period.id)}
									<button type="button" class="period-history-row" onclick={() => selectDemoPeriod(period)}>
										<span class="period-history-copy"><strong>{period.name}</strong><small>{periodCadenceLabel(period.cadenceMonths)} · {periodDateLabel(period.startsAt)} – {periodDateLabel(period.endsAt)}</small></span>
										<span class={'period-status-chip period-status-' + period.status.toLowerCase()}>{periodStatusNames[period.status]}</span>
									</button>
								{/each}
							</div>
						</section>
					</section>
				{:else if view === 'history'}
					<section class="history-panel" aria-labelledby="history-title">
						<div class="history-heading">
							<div>
								<p class="eyebrow">DATA DEMO · READ-ONLY</p>
								<h2 id="history-title">Riwayat penilaian</h2>
								<p class="muted-copy">Lihat jejak periode {staffTrackLabel().toLocaleLowerCase('id-ID')} tanpa mengubah nilai atau keputusan yang sudah tercatat.</p>
							</div>
							<span class="history-scope">{visibleHistoryEntries().length} periode contoh</span>
						</div>

						{#if visibleHistoryEntries().length === 0}
							<div class="history-empty" role="status">
								<strong>Belum ada riwayat untuk jalur ini</strong>
								<p>Riwayat akan muncul setelah self-assignment dan proses penilaian tersedia.</p>
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
											<div class="history-entry-meta"><span>Skor {entry.score === null ? 'belum dihitung' : formatNumber(entry.score, 1)}</span><span>{entry.finalDate ? 'Final ' + formatDate(entry.finalDate) : entry.submittedDate ? 'Diajukan ' + formatDate(entry.submittedDate) : 'Belum diajukan'}</span></div>
										</button>
									{/each}
								</div>

								{#if selectedHistory()}
									<article class="history-detail" aria-labelledby="history-detail-title">
										<div class="history-detail-heading">
											<div><p class="eyebrow">DETAIL PERIODE</p><h3 id="history-detail-title">{selectedHistory()?.periodName}</h3></div>
											<Badge variant={statusVariant(selectedHistory()?.status ?? 'DRAFT')}>{statusNames[selectedHistory()?.status ?? 'DRAFT']}</Badge>
										</div>
										<div class="history-facts">
											<div><span>Jalur</span><strong>{historyTrackLabel(selectedHistory()?.track ?? 'BUSINESS')}</strong></div>
											<div><span>Rentang</span><strong>{formatDate(selectedHistory()?.startsAt ?? '')} – {formatDate(selectedHistory()?.endsAt ?? '')}</strong></div>
											<div><span>Skor demo</span><strong>{selectedHistory()?.score === null ? 'Belum dihitung' : formatNumber(selectedHistory()?.score ?? null, 1)}</strong></div>
											<div><span>Reviewer</span><strong>{selectedHistory()?.reviewer ?? 'Belum ditetapkan'}</strong></div>
										</div>
										<div class="history-readonly-note"><strong>Catatan proses</strong><p>{selectedHistory()?.note}</p></div>
										<div class="history-items" aria-label="Indikator pada penilaian">
											<div class="history-items-heading"><span>Indikator</span><span>Target</span><span>Realisasi</span><span>Skor</span></div>
											{#each selectedHistory()?.items ?? [] as item (item.code)}
												<div class="history-item-row"><div><strong>{item.name}</strong><small>{item.code} · {item.unit}</small></div><span>{item.target}</span><span>{item.actual}</span><strong>{item.score === null ? '—' : formatNumber(item.score, 1)}</strong></div>
											{/each}
										</div>
										<p class="history-detail-footnote">Detail ini hanya untuk membaca snapshot demo. Perubahan dilakukan dari alur KPI aktif, bukan dari riwayat.</p>
									</article>
								{/if}
							</div>
						{/if}
					</section>
				{:else if view === 'assignment'}
					<section class="assignment-panel" aria-labelledby="assignment-title">
						<div class="assignment-heading">
							<div>
								<p class="eyebrow">SELF-ASSIGNMENT · {staffTrackLabel()}</p>
								<h2 id="assignment-title">Susun KPI untuk diri sendiri</h2>
								<p class="muted-copy">{staffTrackDescription()} KPI baru tidak dibuat bebas; gunakan katalog yang sudah disiapkan Admin.</p>
							</div>
							{#if activeMonthlyCycle()}
								<div class="assignment-period"><strong>{activeMonthlyCycle()?.name}</strong><span>{monthlyCycleDateRange()}</span></div>
							{/if}
						</div>

						<div class="assignment-steps" aria-label="Tahap self-assignment">
							<div class="assignment-step active"><span>1</span><strong>Pilih KPI</strong><small>Katalog yang ditetapkan Admin</small></div>
							<div class="assignment-step"><span>2</span><strong>Lengkapi realisasi</strong><small>Setelah draf tersimpan</small></div>
							<div class="assignment-step"><span>3</span><strong>Ajukan</strong><small>Menunggu reviewer</small></div>
						</div>

						{#if !activeMonthlyCycle()}
							<div class="assignment-state" role="note">
								<strong>Belum ada periode KPI yang dibuka</strong>
								<p>Self-assignment dapat disusun setelah Admin membuka periode KPI aktif.</p>
							</div>
						{:else if existingMonthlyContract()}
							<div class="assignment-state">
								<strong>Kontrak untuk periode ini sudah tersedia</strong>
								<p>Buka kontrak yang ada untuk melihat KPI dan melanjutkan pengisian.</p>
								<Button variant="outline" onclick={() => void openExistingMonthlyContract()}>Buka kontrak</Button>
							</div>
						{:else if catalogLoading}
							<div class="assignment-state" role="status"><p>Memuat katalog KPI...</p></div>
						{:else if !catalogLoaded}
							<div class="assignment-state">
								<strong>Katalog KPI belum dimuat</strong>
								<p>Ambil daftar indikator aktif untuk jabatan Anda.</p>
								<Button variant="outline" onclick={() => void openSelfAssignment()}>Muat katalog KPI</Button>
							</div>
						{:else if staffAssignmentMappingState() === 'INACTIVE'}
							<div class="assignment-state assignment-state-warning" role="status">
								<strong>Mapping jabatan sedang nonaktif</strong>
								<p>Admin menonaktifkan mapping untuk {currentStaffPositionMapping()?.name}. Self-assignment tidak dapat menampilkan KPI sampai jabatan diaktifkan kembali.</p>
							</div>
						{:else if staffAssignmentMappingState() === 'ACTIVE_EMPTY' || kpiDefinitions.length === 0}
							<div class="assignment-state">
								<strong>Belum ada mapping KPI Aktif untuk jabatan ini</strong>
								<p>{currentStaffPositionMapping()?.name ?? 'Jabatan Anda'} belum memiliki indikator Aktif. Admin dapat memilih KPI dan menyimpan mapping dari Master Jabatan.</p>
							</div>
						{:else}
							<form class="assignment-form" onsubmit={(event) => { event.preventDefault(); void createSelfAssignment(); }}>
								<div class="assignment-guidance">
									<p>Pilih indikator yang paling sesuai dengan pekerjaan Anda dari mapping Admin. KPI Draft atau indikator di luar jalur ini tidak ditampilkan.</p>
									<p>Target dan bobot inherited dari penetapan Admin, jadi staf hanya memilih indikator dan tidak mengubah nilainya.</p>
								</div>
								<div class="assignment-inherited-note" role="note">
									<strong>Penetapan aktif</strong>
									<span>{staffTrackLabel()} · target dan bobot read-only · sumber data mengikuti katalog Admin</span>
								</div>
								{#if currentStaffPositionMapping()}
									<div class="assignment-position-context" role="note">
										<div>
											<span class="dashboard-label">MAPPING JABATAN</span>
											<strong>{currentStaffPositionMapping()?.name}</strong>
											<p>{currentStaffPositionMapping()?.orgUnitName} · {positionTrackLabel(currentStaffPositionMapping()?.track ?? 'BUSINESS')}</p>
										</div>
										<span class={'assignment-position-status assignment-position-status-' + currentStaffPositionMapping()?.status.toLowerCase()}>{positionStatusLabel(currentStaffPositionMapping()?.status ?? 'INACTIVE')}</span>
									</div>
								{/if}
								{#if staffAssignmentStatus !== 'NONE'}
									<div class="assignment-workflow-summary" role="status" aria-live="polite">
										<div><span class="dashboard-label">STATUS SELF-ASSESSMENT</span><strong>{staffAssignmentStatusLabel()}</strong></div>
										<p>{staffAssignmentStatus === 'SUBMITTED' ? 'Pilihan KPI terkunci sampai reviewer memberi keputusan.' : staffAssignmentStatus === 'REVISION' ? 'Reviewer meminta perbaikan. Buka ulang draf untuk mengubah pilihan sebelum diajukan ulang.' : staffAssignmentStatus === 'VERIFIED' ? 'Reviewer sudah memverifikasi snapshot. Tidak ada input yang dapat diubah.' : staffAssignmentStatus === 'FINAL' ? 'Penilaian sudah final. Snapshot ini hanya dapat dibaca.' : 'Draf tersimpan di browser demo. Ajukan saat pilihan sudah siap.'}</p>
									</div>
								{/if}
								{#if staffAssignmentStatus === 'REVISION' && currentStaffReviewDecision()}
									<div class="assignment-revision-note" role="alert">
										<div><span class="dashboard-label">CATATAN REVIEWER</span><strong>Perlu diperbaiki sebelum diajukan ulang</strong></div>
										<p>{currentStaffReviewDecision()?.note}</p>
										<Button variant="outline" onclick={reopenStaffAssignment}>Buka ulang draf</Button>
									</div>
								{/if}

								<div class="kpi-selection-list" aria-label="Katalog KPI sesuai jabatan">
									{#each kpiDefinitions as definition (definition.id)}
										<article class="kpi-choice-row" class:selected={selectedDefinitionIds.includes(definition.id)}>
											<label class="kpi-selector">
												<input
													type="checkbox"
													checked={selectedDefinitionIds.includes(definition.id)}
																							disabled={['SUBMITTED', 'REVISION', 'VERIFIED', 'FINAL'].includes(staffAssignmentStatus)}
													onchange={(event) => toggleDefinition(definition.id, event.currentTarget.checked)}
												/>
												<span class="kpi-selector-copy">
															<strong>{definitionDisplayName(definition)}</strong>
													<span>{definition.group} | {definition.code} | {definition.unit}</span>
													<small>{definition.definition}</small>
												</span>
											</label>
											{#if selectedDefinitionIds.includes(definition.id)}
												<label class="target-entry">
													<span>Target inherited <small>{definition.unit}</small></span>
													<input
														class="text-input"
														type="number"
														min={definition.type === 'Z' ? 0 : 0.0001}
														max={['Q', 'L'].includes(definition.type) ? 100 : undefined}
														step="any"
														required
														readonly
														value={targetDrafts[definition.id] ?? ''}
													/>
												</label>
												<p class="inherited-weight">Bobot inherited <strong>{formatNumber(definition.default_weight * 100, 2)}%</strong></p>
											{:else}
												<p class="target-sample">Target {formatNumber(definition.sample_target)} {definition.unit} · Bobot {formatNumber(definition.default_weight * 100, 2)}%</p>
											{/if}
										</article>
									{/each}
								</div>

								<div class="assignment-footer">
									<p><strong>{selectedDefinitionIds.length} indikator dipilih</strong><span>{staffAssignmentStatus === 'SUBMITTED' ? 'Self-assessment sudah diajukan.' : staffAssignmentStatus === 'REVISION' ? 'Draf terkunci sampai dibuka ulang.' : staffAssignmentStatus === 'VERIFIED' ? 'Self-assessment sudah diverifikasi reviewer.' : staffAssignmentStatus === 'FINAL' ? 'Snapshot final tersimpan sebagai read-only.' : staffAssignmentStatus === 'DRAFT' ? 'Draf tersimpan. Anda masih dapat mengubah pilihan.' : 'Bobot mengikuti penetapan Admin.'}</span></p>
									{#if ['SUBMITTED', 'VERIFIED', 'FINAL'].includes(staffAssignmentStatus)}
										<span class="assignment-submitted-lock">{staffAssignmentStatus === 'FINAL' ? 'Final terkunci' : staffAssignmentStatus === 'VERIFIED' ? 'Terverifikasi, menunggu finalisasi' : 'Terkunci untuk review'}</span>
									{:else if staffAssignmentStatus === 'REVISION'}
										<Button class="assignment-submit" variant="outline" onclick={reopenStaffAssignment}>Buka ulang draf</Button>
									{:else}
										<div class="assignment-footer-actions">
											<Button class="assignment-submit" disabled={busy || selectedDefinitionIds.length === 0} onclick={createSelfAssignment}>
												{busy ? 'Menyimpan draf...' : staffAssignmentStatus === 'DRAFT' ? 'Simpan ulang draf' : 'Simpan draf KPI'}
											</Button>
													<Button class="assignment-submit assignment-submit-secondary" variant="outline" disabled={busy || staffAssignmentStatus !== 'DRAFT' || selectedDefinitionIds.length === 0} onclick={submitSelfAssessment}>{currentStaffReviewDecision()?.decision === 'REVISION' ? 'Ajukan ulang self-assessment' : 'Ajukan self-assessment'}</Button>
										</div>
									{/if}
								</div>
							</form>
						{/if}
					</section>
				{:else if view === 'mine' && canViewStaffHistory()}
					<section class="kpi-work-panel" aria-labelledby="kpi-work-title">
						<div class="kpi-work-heading">
							<div>
								<p class="eyebrow">KPI SAYA · {staffTrackLabel()}</p>
								<h2 id="kpi-work-title">Ruang kerja indikator aktif</h2>
								<p class="muted-copy">Isi realisasi, bukti, dan catatan dari periode yang sedang berjalan. Nilai di bawah masih bersifat demo.</p>
							</div>
							{#if dashboard?.cycle}<span class="kpi-work-period">{dashboard.cycle.name}</span>{/if}
						</div>

						{#if currentStaffReadOnlyOutcome()}
							<section class="kpi-readonly-panel" aria-labelledby="kpi-readonly-title">
								<div class="kpi-readonly-header">
									<div>
										<p class="eyebrow">SNAPSHOT PENILAIAN Â· READ-ONLY</p>
										<h3 id="kpi-readonly-title">KPI periode ini sudah {currentStaffReadOnlyOutcome() === 'FINAL' ? 'final' : 'terverifikasi'}</h3>
										<p>Nilai dan bukti yang tampil adalah salinan saat reviewer mengambil keputusan. Tidak ada input yang dapat diubah dari halaman ini.</p>
									</div>
									<Badge variant="secondary">{currentStaffReadOnlyOutcome() === 'FINAL' ? 'FINAL' : 'VERIFIED'}</Badge>
								</div>
								<div class="kpi-readonly-facts">
									<div><span>Periode</span><strong>{currentStaffReadOnlyRecord()?.periodName}</strong></div>
									<div><span>Jalur</span><strong>{staffTrackLabel()}</strong></div>
									<div><span>Reviewer</span><strong>Kepala Cabang Demo 001</strong></div>
									<div><span>{currentStaffReadOnlyOutcome() === 'FINAL' ? 'Difinalisasi' : 'Diverifikasi'}</span><strong>{formatDateTime(currentStaffReadOnlyFinalDate() ?? currentStaffReviewDecision()?.savedAt ?? null)}</strong></div>
								</div>
								<div class="kpi-readonly-note"><strong>Catatan keputusan</strong><p>{currentStaffReadOnlyReviewerNote()}</p></div>
								<div class="kpi-readonly-items" aria-label="Snapshot indikator KPI saya">
									<div class="kpi-readonly-items-heading"><span>Indikator</span><span>Target</span><span>Realisasi</span><span>Skor</span></div>
									{#each currentStaffAssignmentRecord()?.items ?? [] as item (item.code)}
										<div class="kpi-readonly-item">
											<div><strong>{item.name}</strong><small>{item.code} Â· {item.unit}</small></div>
											<span>{item.target}</span><span>{item.actual}</span><strong>{item.score === null ? 'Belum dihitung' : formatNumber(item.score, 1)}</strong>
										</div>
									{/each}
								</div>
								<div class="kpi-readonly-footer"><span>Snapshot terkunci untuk menjaga jejak keputusan reviewer.</span><Button variant="outline" onclick={openHistory}>Lihat riwayat penilaian</Button></div>
							</section>
						{:else if !staffDashboardContract()}
							<div class="kpi-work-empty" role="status">
								<strong>Belum ada KPI aktif untuk periode ini</strong>
								<p>Mulai dari self-assignment untuk memilih indikator dan mengusulkan target sesuai jalur kerja Anda.</p>
								<Button onclick={() => void openSelfAssignment()}>Susun KPI sekarang</Button>
							</div>
						{:else}
							<div class="kpi-work-summary" aria-label="Ringkasan kontrak KPI saya">
								<div><span>Status</span><strong>{staffDashboardAssignmentLabel()}</strong><Badge variant={statusVariant(staffDashboardContract()?.status ?? 'DRAFT')}>{statusNames[staffDashboardContract()?.status ?? 'DRAFT']}</Badge></div>
								<div><span>Progres indikator</span><strong>{staffDashboardProgress().completed} dari {staffDashboardProgress().total}</strong><small>{staffDashboardProgress().percent}% lengkap</small></div>
								<div><span>Realisasi</span><strong>{staffDashboardRealizationSummary()}</strong><small>Target dan bukti per indikator</small></div>
							</div>
							{#if !selectedContract}
								<div class="kpi-work-open-state"><p>Detail KPI belum dibuka.</p><Button class="kpi-work-open-button" variant="outline" onclick={() => void openMine()}>Buka indikator</Button></div>
							{/if}
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
											<div><span>Skor demo</span><strong>{selectedReviewTask()?.score === null ? 'Belum dihitung' : formatNumber(selectedReviewTask()?.score ?? null, 1)}</strong></div>
											<div><span>Deadline</span><strong>{formatDate(selectedReviewTask()?.dueAt ?? '')}</strong></div>
										</div>
										<div class="review-task-note"><strong>{selectedReviewTask()?.lastAction}</strong><p>Periksa capaian, bukti, dan penjelasan staf sebelum memilih keputusan. Semua angka di panel ini masih dummy.</p></div>
										<div class="review-item-detail-list" aria-label="Snapshot indikator dan bukti staf">
											{#each selectedReviewTask()?.items ?? [] as item (item.code)}
												<article class="review-item-detail">
													<div class="review-item-heading"><div><span class="review-item-code">{item.code} / {item.unit}</span><h4>{item.name}</h4></div><strong>{item.score === null ? 'Belum dihitung' : formatNumber(item.score, 1)}<small>skor demo</small></strong></div>
													<div class="review-item-facts">
														<div><span>Target</span><strong>{item.target} {item.unit}</strong></div>
														<div><span>Realisasi</span><strong>{item.actual} {item.unit}</strong></div>
														<div><span>Bukti</span><strong>{item.evidence ?? 'Referensi demo belum dicatat'}</strong></div>
													</div>
													<div class="review-item-note"><span>Catatan staf</span><p>{item.note ?? 'Staf belum menambahkan catatan.'}</p></div>
												</article>
											{/each}
										</div>

										<div class="review-decision-box">
											<div class="review-decision-heading"><div><p class="eyebrow">KEPUTUSAN DUMMY</p><h4>Catat keputusan reviewer</h4></div>{#if selectedReviewFinalization()}<Badge variant="secondary">FINAL</Badge>{:else if selectedReviewDecision()}<Badge variant={statusVariant(selectedReviewDecision()?.decision ?? 'VERIFIED')}>{statusNames[selectedReviewDecision()?.decision ?? 'VERIFIED']}</Badge>{/if}</div>
											<label class="review-decision-field" for="review-decision-note">Catatan keputusan reviewer<textarea id="review-decision-note" class="text-input textarea-input" bind:value={reviewDecisionNote} rows="4" placeholder="Tulis alasan yang bisa ditindaklanjuti staf." readonly={Boolean(selectedReviewFinalization())}></textarea></label>
											<p class="review-decision-hint">Catatan wajib diisi. Tombol di bawah hanya menyimpan state demo di browser.</p>
											{#if !selectedReviewFinalization()}<div class="review-decision-actions"><Button variant="destructive" disabled={!reviewDecisionNote.trim()} onclick={() => saveDemoReviewDecision('REVISION')}>Minta revisi</Button><Button disabled={!reviewDecisionNote.trim()} onclick={() => saveDemoReviewDecision('VERIFIED')}>Verifikasi</Button></div>{/if}
											{#if selectedReviewDecision()}<div class="review-decision-result" role="status"><strong>State dummy tersimpan</strong><p>{selectedReviewDecision()?.note}</p><small>Disimpan {formatDateTime(selectedReviewDecision()?.savedAt ?? null)}</small></div>{/if}
											{#if selectedReviewDecision()?.decision === 'VERIFIED' && !selectedReviewFinalization()}<div class="review-finalize-actions"><Button onclick={saveDemoFinalization}>Finalisasi dan kunci snapshot</Button><span>Setelah finalisasi, keputusan dan snapshot tidak dapat diubah.</span></div>{/if}
											{#if selectedReviewFinalization()}<div class="review-finalization-result" role="status"><strong>Snapshot sudah final dan terkunci</strong><p>{selectedReviewFinalization()?.note}</p><small>Difinalisasi {formatDateTime(selectedReviewFinalization()?.finalizedAt ?? null)}</small></div>{/if}
										</div>
									</article>
								{/if}
							</div>
						{/if}
					</section>
				{:else}
				<section class="contract-panel" aria-labelledby="contract-list-title">
					<div class="panel-heading">
						<div>
							<p class="eyebrow">SIKLUS DEMO</p>
							<h2 id="contract-list-title">{view === 'mine' ? 'Kontrak milik Anda' : view === 'reviews' ? 'Antrean tindakan' : 'Kontrak KPI'}</h2>
							<p class="muted-copy">{view === 'reviews' ? 'Kontrak yang menjadi tanggung jawab reviewer aktif.' : 'Daftar mengikuti identitas dan cakupan akses saat ini.'}</p>
						</div>
						{#if cycles.length > 0}<Badge variant="outline" class="cycle-badge">{cycles.find((cycle) => cycle.status === 'OPEN')?.name ?? cycles[0].name}</Badge>{/if}
					</div>

					{#if visibleContracts().length > 0}
						<div class="table-scroll">
							<table>
								<thead><tr><th scope="col">Pegawai</th><th scope="col">Jabatan dan unit</th><th scope="col">Status</th><th scope="col">Skor KPI demo</th><th scope="col"><span class="sr-only">Detail</span></th></tr></thead>
								<tbody>
									{#each visibleContracts() as contract (contract.id)}
										<tr>
											<td><strong class="table-primary">{contract.employee_name ?? contract.org_unit_name}</strong><span class="table-secondary">{contract.id}</span></td>
											<td><strong class="table-primary">{contract.position_name ?? 'Unit kerja'}</strong><span class="table-secondary">{contract.org_unit_name}</span></td>
											<td><Badge variant={statusVariant(contract.status)}>{statusNames[contract.status]}</Badge></td>
											<td><strong class="score-cell">{formatNumber(contract.score?.kpi_score ?? null, 1)}</strong></td>
											<td class="table-action"><Button variant="outline" size="sm" onclick={() => openContract(contract.id)}>Buka</Button></td>
										</tr>
									{/each}
								</tbody>
							</table>
						</div>
					{:else}
						<div class="empty-state">
							<strong>{view === 'reviews' ? 'Tidak ada kontrak di antrean ini' : 'Belum ada kontrak di cakupan ini'}</strong>
							<p>Ganti identitas demo atau buka bagian KPI saya untuk melihat contoh alur lain.</p>
							{#if view !== 'overview'}<Button variant="outline" onclick={() => setView('overview')}>Lihat ringkasan</Button>{/if}
						</div>
					{/if}
				</section>

				{#if selectedContract}
					<section class="detail-panel" aria-labelledby="contract-detail-title">
						<div class="panel-heading detail-heading">
							<div><p class="eyebrow">DETAIL KONTRAK · {selectedContract.id}</p><h2 id="contract-detail-title">{selectedContract.employee_name ?? selectedContract.org_unit_name}</h2><p class="muted-copy">{selectedContract.position_name} · {selectedContract.org_unit_name}</p></div>
							<div class="detail-heading-actions"><Badge variant={statusVariant(selectedContract.status)}>{statusNames[selectedContract.status]}</Badge><Button variant="ghost" size="sm" onclick={() => (selectedContract = null)}>Tutup</Button></div>
						</div>

						{#if isBranchSelfAssessment()}
							<section class="assessment-flow" aria-labelledby="assessment-flow-title">
								<div class="assessment-flow-heading"><div><p class="eyebrow">SELF-ASSESSMENT BERTAHAP</p><h3 id="assessment-flow-title">Lengkapi sebelum diajukan</h3></div><span class="assessment-flow-count">Tahap {assessmentStep} dari 3</span></div>
								<div class="assessment-stepper" role="list" aria-label="Tahap self-assessment">
									<button type="button" class:active={assessmentStep === 1} class:complete={assessmentStep > 1} onclick={() => moveAssessmentStep(1)}><span>1</span><strong>Realisasi</strong><small>Isi capaian</small></button>
									<button type="button" class:active={assessmentStep === 2} class:complete={assessmentStep > 2} onclick={() => moveAssessmentStep(2)}><span>2</span><strong>Bukti</strong><small>Referensi & catatan</small></button>
									<button type="button" class:active={assessmentStep === 3} onclick={() => moveAssessmentStep(3)}><span>3</span><strong>Review</strong><small>Ringkasan submit</small></button>
								</div>
								<p class="assessment-flow-hint">{assessmentStepHint()}</p>
							</section>
						{/if}

						<div class="score-strip">
							<div><span>Skor KPI demo</span><strong>{formatNumber(selectedContract.score?.kpi_score ?? null, 1)}</strong></div>
							<div><span>Bobot valid</span><strong>{formatNumber((selectedContract.score?.valid_weight ?? 0) * 100)}%</strong></div>
							<div><span>Nilai akhir</span><strong>Belum dihitung</strong></div>
							<p>{selectedContract.score?.message ?? 'Formula versi ' + (selectedContract.score?.formula_version ?? 'demo-prototype-v1') + ' hanya digunakan untuk demo.'}</p>
						</div>

						<div class="metric-list">
							{#each selectedContract.items as item (item.id)}
								<article class="metric-card">
									<div class="metric-heading">
										<div><span class="metric-code">{item.code} · {item.score_type}</span><h3>{item.name}</h3></div>
										<div class="metric-result"><strong>{formatNumber(item.score, 1)}</strong><span>skor demo</span></div>
									</div>
									<div class="metric-facts">
										<div><span>Target</span><strong>{formatNumber(item.target, 2)} {item.unit}</strong></div>
										<div><span>Realisasi</span><strong>{item.actual === null ? 'Belum diisi' : formatNumber(item.actual, 2) + ' ' + item.unit}</strong></div>
										<div><span>Bobot</span><strong>{formatNumber(item.weight * 100)}%</strong></div>
										<div><span>Status data</span><Badge variant={item.state === 'VALID' ? 'secondary' : 'outline'}>{item.state === 'VALID' ? 'Lengkap' : item.state === 'NOT_APPLICABLE' ? 'Tidak berlaku' : 'Belum lengkap'}</Badge></div>
									</div>

									{#if (canEditActual(selectedContract) || canEditTarget(selectedContract)) && (!isBranchSelfAssessment() || assessmentStep !== 3)}
										<div class="metric-edit">
											{#if canEditActual(selectedContract) && (!isBranchSelfAssessment() || assessmentStep === 1)}
												<label><span>Realisasi</span><input class="text-input" type="number" step="any" bind:value={edits[item.id].actual} /></label>
											{/if}
											{#if canEditActual(selectedContract) && (!isBranchSelfAssessment() || assessmentStep === 2)}
												<label><span>Referensi bukti demo</span><input class="text-input" type="text" bind:value={edits[item.id].evidence} placeholder="Contoh: DEMO-EVIDENCE-0001" /></label>
												<label class="wide-field"><span>Catatan</span><input class="text-input" type="text" bind:value={edits[item.id].note} /></label>
											{/if}
											{#if canEditTarget(selectedContract)}
												<label><span>Target baru</span><input class="text-input" type="number" step="any" bind:value={edits[item.id].target} /></label>
												<label><span>Bobot (%)</span><input class="text-input" type="number" min="0.01" max="100" step="0.01" bind:value={edits[item.id].weightPercent} /></label>
												<label class="wide-field"><span>Alasan perubahan target/bobot</span><input class="text-input" type="text" bind:value={edits[item.id].reason} placeholder="Wajib jika target atau bobot diubah" /></label>
											{/if}
											<div class="metric-save"><Button size="sm" disabled={busy || !itemDirty(item)} onclick={() => saveItem(item)}>Simpan item</Button></div>
										</div>
									{/if}
								</article>
							{/each}
						</div>

						{#if isBranchSelfAssessment() && assessmentStep === 3}
							<section class="assessment-review-summary" aria-labelledby="assessment-review-title">
								<div class="assessment-review-heading"><div><p class="eyebrow">RINGKASAN PENGAJUAN</p><h3 id="assessment-review-title">Periksa sebelum submit</h3></div><span>Read-only</span></div>
								<div class="assessment-review-facts">
									<div><span>Indikator</span><strong>{selectedContract.items.length} KPI</strong></div>
									<div><span>Realisasi</span><strong>{selectedContract.items.filter((item) => item.actual !== null).length}/{selectedContract.items.length} terisi</strong></div>
									<div><span>Bukti</span><strong>{selectedContract.items.filter((item) => item.evidence.length > 0).length}/{selectedContract.items.length} tersedia</strong></div>
									<div><span>Reviewer</span><strong>{selectedContract.reviewer_employee_id ? 'Sudah ditetapkan' : 'Belum ditetapkan'}</strong></div>
								</div>
								{#if assessmentSubmitBlockedReason(selectedContract)}<p class="assessment-review-warning">{assessmentSubmitBlockedReason(selectedContract)}</p>{/if}
							</section>
						{/if}

						{#if selectedContract.comments.length > 0}
							<section class="comments-section" aria-labelledby="comments-title">
								<h3 id="comments-title">Catatan kontrak</h3>
								{#each selectedContract.comments as comment (comment.id)}
									<div class="comment-row"><div class="comment-avatar">{initials(comment.actor_name)}</div><div><strong>{comment.actor_name}</strong><p>{comment.message}</p></div></div>
								{/each}
							</section>
						{/if}

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

						{#if isBranchSelfAssessment()}
							<section class="workflow-box assessment-workflow-box">
								{#if assessmentStep === 3}
									<label for="comment-draft">Catatan untuk reviewer <span class="optional-label">Opsional</span></label>
									<textarea id="comment-draft" class="text-input textarea-input" bind:value={commentDraft} rows="3" placeholder="Tambahkan konteks singkat sebelum pengajuan"></textarea>
									{#if assessmentSubmitBlockedReason(selectedContract)}<p class="workflow-hint">{assessmentSubmitBlockedReason(selectedContract)}</p>{/if}
									<div class="workflow-actions">
										<Button variant="outline" onclick={() => moveAssessmentStep(2)}>Kembali ke bukti</Button>
										<Button variant="outline" disabled={busy || !commentDraft.trim()} onclick={addComment}>Simpan catatan</Button>
										<Button disabled={busy || Boolean(assessmentSubmitBlockedReason(selectedContract))} onclick={submitContract}>Ajukan self-assessment</Button>
									</div>
								{:else}
									<p class="workflow-hint">{assessmentStepHint()}</p>
									<div class="workflow-actions">
										{#if assessmentStep === 2}<Button variant="outline" onclick={() => moveAssessmentStep(1)}>Kembali ke realisasi</Button>{/if}
										<Button disabled={Boolean(assessmentNextBlockedReason(selectedContract))} onclick={() => moveAssessmentStep(assessmentStep === 1 ? 2 : 3)}>{assessmentStep === 1 ? 'Lanjut ke bukti' : 'Lihat ringkasan'}</Button>
									</div>
								{/if}
							</section>
						{:else if selectedContract.status === 'DRAFT' || selectedContract.status === 'REVISION'}
							<section class="workflow-box">
								<label for="comment-draft">Tambah komentar</label>
								<textarea id="comment-draft" class="text-input textarea-input" bind:value={commentDraft} rows="3" placeholder="Tulis usulan atau catatan untuk kontrak demo"></textarea>
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
								<div class="workflow-actions"><Button variant="outline" disabled={busy || !reviewComment.trim()} onclick={() => reviewContract('REVISION')}>Minta revisi</Button><Button disabled={busy || !reviewComment.trim()} onclick={() => reviewContract('VERIFIED')}>Verifikasi</Button></div>
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

	<nav class="mobile-nav" class:with-assignment={canSelfAssignRole()} class:with-admin={hasAdminAccess()} class:with-history={canViewStaffHistory()} aria-label="Navigasi ringkas">
		<button class:active={view === 'overview'} aria-current={view === 'overview' ? 'page' : undefined} onclick={() => setView('overview')}><span aria-hidden="true">▦</span><small>{canViewStaffHistory() ? 'Dashboard' : 'Ringkasan'}</small></button>
		<button class:active={view === 'mine'} aria-current={view === 'mine' ? 'page' : undefined} onclick={() => void openMine()}><span aria-hidden="true">◎</span><small>KPI saya</small></button>
		{#if canSelfAssignRole()}<button class:active={view === 'assignment'} aria-current={view === 'assignment' ? 'page' : undefined} onclick={() => void openSelfAssignment()}><span aria-hidden="true">+</span><small>Susun KPI</small></button>{/if}
		{#if canViewStaffHistory()}<button class:active={view === 'history'} aria-current={view === 'history' ? 'page' : undefined} onclick={openHistory}><span aria-hidden="true">▤</span><small>Riwayat</small></button>{/if}
		<button class:active={view === 'reviews'} aria-current={view === 'reviews' ? 'page' : undefined} onclick={() => (canReviewInbox() ? openReviews() : setView('reviews'))}><span aria-hidden="true">✓</span><small>{canReviewInbox() ? 'Inbox' : 'Tinjauan'}</small></button>
		{#if hasAdminAccess()}<button class:active={view === 'monitoring'} aria-current={view === 'monitoring' ? 'page' : undefined} onclick={openMonitoring}><span aria-hidden="true">◴</span><small>Monitor</small></button><button class:active={view === 'catalog'} aria-current={view === 'catalog' ? 'page' : undefined} onclick={openCatalog}><span aria-hidden="true">≡</span><small>Katalog</small></button><button class:active={view === 'mapping'} aria-current={view === 'mapping' ? 'page' : undefined} onclick={openAssignmentMapping}><span aria-hidden="true">↳</span><small>Set KPI</small></button><button class:active={view === 'positions'} aria-current={view === 'positions' ? 'page' : undefined} onclick={openPositions}><span aria-hidden="true">J</span><small>Jabatan</small></button><button class:active={view === 'periods'} aria-current={view === 'periods' ? 'page' : undefined} onclick={() => setView('periods')}><span aria-hidden="true">◷</span><small>Periode</small></button>{/if}
		{#if hasAdminAccess()}<button class:active={view === 'audit'} aria-current={view === 'audit' ? 'page' : undefined} onclick={openAuditTrail}><span aria-hidden="true">A</span><small>Audit</small></button><button class:active={view === 'comparison'} aria-current={view === 'comparison' ? 'page' : undefined} onclick={openPeriodComparison}><span aria-hidden="true">%</span><small>Banding</small></button>{/if}
		{#if hasAdminAccess()}<button class:active={view === 'ssoMappings'} aria-current={view === 'ssoMappings' ? 'page' : undefined} onclick={openSsoMappings}><span aria-hidden="true">S</span><small>Mapping SSO</small></button>{/if}
	</nav>
{/if}

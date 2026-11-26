<script lang="ts">
	import { Button } from '#lib/components/ui/button/index.js';
	import { LogOut, PanelLeftClose, PanelLeftOpen, X } from '@lucide/svelte';
	import type { LucideIcon } from '@lucide/svelte';
	import ThemeToggle from '#lib/components/theme-toggle.svelte';

	export type WorkspaceNavigationItem = {
		id: string;
		label: string;
		icon: LucideIcon;
		active: boolean;
		count?: number;
		onSelect: () => void;
	};

	export type WorkspaceNavigationSection = {
		label: string;
		items: WorkspaceNavigationItem[];
	};

	type Props = {
		sections: WorkspaceNavigationSection[];
		collapsed: boolean;
		mobileOpen: boolean;
		userName: string;
		userRole: string;
		userInitials: string;
		isDark: boolean;
		busy: boolean;
		onToggleCollapsed: () => void;
		onToggleTheme: () => void;
		onLogout: () => void;
		onClose: () => void;
	};

	let {
		sections,
		collapsed,
		mobileOpen,
		userName,
		userRole,
		userInitials,
		isDark,
		busy,
		onToggleCollapsed,
		onToggleTheme,
		onLogout,
		onClose
	}: Props = $props();
</script>

{#if mobileOpen}
	<button class="sidebar-backdrop" type="button" aria-label="Tutup navigasi" onclick={onClose}></button>
{/if}

<aside
	class="sidebar"
	class:collapsed
	class:mobile-open={mobileOpen}
	id="main-sidebar"
	aria-label="Navigasi utama"
>
	<div class="sidebar-header">
		<div class="sidebar-brand">
			<span class="brand-mark" aria-hidden="true">
				<svg viewBox="0 0 40 40">
					<path d="M6 6h26v5H17.5v22h-5V11H6z" />
					<path class="brand-accent" d="M22 28h3v5h-3zm5-6h3v11h-3zm5-7h3v18h-3z" />
				</svg>
			</span>
			<div class="sidebar-brand-copy">
				<strong>Tera</strong>
				<span>Kinerja BKK Jateng</span>
			</div>
		</div>

		{#if mobileOpen}
			<Button variant="ghost" size="icon" class="sidebar-mobile-close" aria-label="Tutup navigasi" onclick={onClose}>
				<X size={18} strokeWidth={1.8} aria-hidden="true" />
			</Button>
		{:else}
			<Button
				variant="ghost"
				size="icon"
				class="sidebar-collapse"
				aria-label={collapsed ? 'Perlebar sidebar' : 'Ciutkan sidebar'}
				title={collapsed ? 'Perlebar sidebar' : 'Ciutkan sidebar'}
				onclick={onToggleCollapsed}
			>
				{#if collapsed}
					<PanelLeftOpen size={18} strokeWidth={1.8} aria-hidden="true" />
				{:else}
					<PanelLeftClose size={18} strokeWidth={1.8} aria-hidden="true" />
				{/if}
			</Button>
		{/if}
	</div>

	<div class="sidebar-navigation">
		{#each sections as section (section.label)}
			<section class="sidebar-section" aria-label={section.label}>
				<p class="nav-caption">{section.label}</p>
				<nav class="main-nav" aria-label={section.label}>
					{#each section.items as item (item.id)}
						{@const NavIcon = item.icon}
						<button
							class="main-nav-item"
							class:active={item.active}
							type="button"
							aria-current={item.active ? 'page' : undefined}
							aria-label={item.label}
							title={collapsed && !mobileOpen ? item.label : undefined}
							onclick={() => {
								item.onSelect();
								onClose();
							}}
						>
							<NavIcon class="nav-icon" size={18} strokeWidth={1.8} aria-hidden="true" />
							<span class="nav-label">{item.label}</span>
							{#if item.count && item.count > 0}
								<span class="nav-count" aria-label={`${item.count} perlu ditinjau`}>{item.count}</span>
							{/if}
						</button>
					{/each}
				</nav>
			</section>
		{/each}
	</div>

	<div class="sidebar-account" title={collapsed && !mobileOpen ? userName + ' · ' + userRole : undefined}>
		<div class="sidebar-account-user">
			<span class="sidebar-account-avatar" aria-hidden="true">{userInitials}</span>
			<span class="sidebar-account-copy">
				<strong>{userName}</strong>
				<small>{userRole}</small>
			</span>
		</div>
		<div class="sidebar-account-actions">
			<ThemeToggle isDark={isDark} onToggle={onToggleTheme} className="sidebar-theme-toggle" />
			<Button variant="ghost" size="icon" class="sidebar-logout" aria-label="Keluar dari akun" title="Keluar" disabled={busy} onclick={onLogout}>
				<LogOut size={17} strokeWidth={1.8} aria-hidden="true" />
			</Button>
		</div>
	</div>

</aside>

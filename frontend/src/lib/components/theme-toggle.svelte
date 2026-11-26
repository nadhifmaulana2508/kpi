<script lang="ts">
	import { Button } from '#lib/components/ui/button/index.js';
	import { Moon, Sun } from '@lucide/svelte';

	type Props = {
		isDark: boolean;
		onToggle: () => void;
		className?: string;
	};

	let { isDark, onToggle, className = '' }: Props = $props();
</script>

<Button
	variant="ghost"
	size="icon"
	class={className}
	type="button"
	aria-label={isDark ? 'Gunakan tema terang' : 'Gunakan tema gelap'}
	title={isDark ? 'Gunakan tema terang' : 'Gunakan tema gelap'}
	onclick={onToggle}
>
	{#if isDark}
		<Sun size={18} strokeWidth={1.8} aria-hidden="true" />
	{:else}
		<Moon size={18} strokeWidth={1.8} aria-hidden="true" />
	{/if}
</Button>

const apiBaseUrl = (import.meta.env.VITE_API_BASE_URL ?? '').replace(/\/+$/, '');

function apiUrl(path: string): string {
	if (/^https?:\/\//i.test(path)) return path;
	return apiBaseUrl + (path.startsWith('/') ? path : '/' + path);
}

export class ApiError extends Error {
	constructor(
		message: string,
		readonly status: number
	) {
		super(message);
		this.name = 'ApiError';
	}
}

async function prepareCsrf(): Promise<void> {
	const response = await fetch(apiUrl('/sanctum/csrf-cookie'), {
		credentials: 'include',
		headers: { Accept: 'application/json' }
	});

	if (!response.ok) {
		throw new ApiError('Token sesi demo tidak dapat disiapkan.', response.status);
	}
}

function xsrfToken(): string {
	const cookie = document.cookie
		.split('; ')
		.find((part) => part.startsWith('XSRF-TOKEN='))
		?.slice('XSRF-TOKEN='.length);

	if (!cookie) return '';

	try {
		return decodeURIComponent(cookie);
	} catch {
		return cookie;
	}
}

export async function api<T>(path: string, options: RequestInit = {}): Promise<T> {
	const method = (options.method ?? 'GET').toUpperCase();
	const headers = new Headers(options.headers);
	headers.set('Accept', 'application/json');
	if (options.body) headers.set('Content-Type', 'application/json');

	if (!['GET', 'HEAD', 'OPTIONS'].includes(method)) {
		await prepareCsrf();
		const token = xsrfToken();
		if (token) headers.set('X-XSRF-TOKEN', token);
	}

	const response = await fetch(apiUrl(path), {
		...options,
		method,
		headers,
		credentials: 'include',
		cache: 'no-store'
	});
	const payload = response.status === 204 ? undefined : await response.json().catch(() => undefined);

	if (!response.ok) {
		const message =
			payload?.error?.message ??
			payload?.message ??
			(response.status === 419
				? 'Sesi berubah. Coba kirim ulang tindakan ini.'
				: 'Permintaan belum berhasil diproses.');

		throw new ApiError(message, response.status);
	}

	return payload as T;
}

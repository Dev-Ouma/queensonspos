export function img(path) {
    if (!path) return null;
    return path.startsWith('http') ? path : `/storage/${path}`;
}

export function kes(cents) {
    if (cents === null || cents === undefined) return '—';
    return 'KES ' + (cents / 100).toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

export function img(path) {
    if (!path) return null;
    return path.startsWith('http') ? path : `/storage/${path}`;
}

export function kes(cents) {
    if (cents === null || cents === undefined) return '—';
    return 'KES ' + (cents / 100).toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

/* Placeholder fashion photos for products that have no uploaded image yet. */
const FALLBACK = [
    'photo-1539533018447-63fcce2678e3', 'photo-1496747611176-843222e1e57c', 'photo-1549298916-b41d501d3772',
    'photo-1548036328-c9fa89d128fa', 'photo-1515562141207-7a88fb7ce338', 'photo-1583496661160-fb5886a0aaaa',
    'photo-1543163521-1bf539c55dd2', 'photo-1485968579580-b6d095142e6e', 'photo-1525507119028-ed4c629a60a3',
    'photo-1434389677669-e08b4cac3105', 'photo-1591047139829-d91aecb6caea', 'photo-1503342217505-b0a15ec3261c',
];
export function fallbackImage(id = 0, w = 600) {
    return `https://images.unsplash.com/${FALLBACK[Math.abs(id) % FALLBACK.length]}?q=75&w=${w}&auto=format&fit=crop`;
}

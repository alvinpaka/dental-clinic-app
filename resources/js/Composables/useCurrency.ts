export function formatUGX(value: number | string): string {
  const n = Number(value || 0);
  return `UGX ${n.toLocaleString('en-UG', { maximumFractionDigits: 0 })}`;
}

export function formatUGXWithCents(value: number | string): string {
  const n = Number(value || 0);
  return `UGX ${n.toLocaleString('en-UG', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

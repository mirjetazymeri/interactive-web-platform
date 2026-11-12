/**
 * The visitor's address to forward to Laravel (for per-visitor rate limits),
 * read only from the header named in TRUSTED_CLIENT_IP_HEADER: the one the
 * hosting proxy in front of Next.js sets and overwrites (e.g. "x-real-ip",
 * "cf-connecting-ip"). Unset: nothing is forwarded, because any header could
 * otherwise come straight from the visitor. For "x-forwarded-for" the last
 * entry is used: the address the trusted proxy itself appended.
 */
export function clientIp(headers: Headers, env: Record<string, string | undefined> = process.env): string | null {
  const name = env.TRUSTED_CLIENT_IP_HEADER?.trim().toLowerCase();
  if (!name) return null;
  const raw = headers.get(name);
  const value = (name === "x-forwarded-for" ? raw?.split(",").pop() : raw)?.trim();

  return value && /^[0-9a-fA-F:.]{3,45}$/.test(value) ? value : null;
}

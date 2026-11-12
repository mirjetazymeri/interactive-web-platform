import type { z } from "zod";

export class CmsError extends Error {
  constructor(public kind: "configuration" | "network" | "response" | "http", message: string, public status?: number, options?: ErrorOptions) {
    super(message, options); this.name = "CmsError";
  }
}

export function apiConfig(env: Record<string, string | undefined>) {
  const base = env.API_BASE_URL;
  const channel = env.STOREFRONT_CHANNEL;
  if (!base || !channel) throw new CmsError("configuration", "Set API_BASE_URL and STOREFRONT_CHANNEL in .env.local.");
  try {
    const url = new URL(base);
    if (!/^https?:$/.test(url.protocol) || url.username || url.password || url.search || url.hash) throw new Error();
    if (/[\r\n]/.test(channel)) throw new Error();
    return { base: url.href.replace(/\/$/, ""), channel, origin: url.origin };
  } catch { throw new CmsError("configuration", "Invalid server-side CMS configuration."); }
}

export async function requestCms(config: ReturnType<typeof apiConfig>, path: string, locale?: string, init?: RequestInit) {
  let response: Response;
  try {
    response = await fetch(`${config.base}/${path}`, {
      ...init, cache: "no-store", redirect: "error", signal: AbortSignal.timeout(12_000),
      // Caller headers first; the channel, locale and content headers always win.
      headers: { ...(init?.headers as Record<string, string> | undefined), Accept: "application/json", "X-Channel": config.channel,
        ...(locale ? { "X-Locale": locale, "Accept-Language": locale } : {}),
        ...(init?.body ? { "Content-Type": "application/json" } : {}),
      },
    });
  } catch (cause) {
    throw new CmsError("network", `CMS request failed or timed out: ${path.split("?")[0]}. Check that the CMS server is running and API_BASE_URL is reachable from Node.js.`, undefined, { cause });
  }
  if (!response.ok) throw new CmsError("http", `CMS returned HTTP ${response.status}: ${path.split("?")[0]}`, response.status);
  if (!response.headers.get("content-type")?.includes("application/json")) throw new CmsError("response", "CMS returned a non-JSON response.");
  try { return await response.json() as unknown; }
  catch { throw new CmsError("response", "CMS returned invalid JSON."); }
}

export function parseEnvelope<T>(payload: unknown, schema: z.ZodType<T>): T {
  if (!payload || typeof payload !== "object" || !("data" in payload) || ("success" in payload && payload.success !== true)) {
    throw new CmsError("response", "CMS returned an invalid response envelope.");
  }
  const result = schema.safeParse(payload.data);
  if (!result.success) throw new CmsError("response", `CMS contract mismatch: ${result.error.issues.map((i) => i.path.join(".")).join(", ")}`);
  return result.data;
}

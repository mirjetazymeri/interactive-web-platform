export type LinkContext = { locale: string; locales: string[]; backendOrigin: string };

export function pageHref(locale: string, path: string) {
  return `/${encodeURIComponent(locale)}${path === "home" || !path ? "" : `/${path.split("/").map(encodeURIComponent).join("/")}`}`;
}

export function safeUrl(value: unknown, media = false): string | undefined {
  if (typeof value !== "string" || !value.trim()) return;
  const url = value.trim();
  if (/[\u0000-\u0020\\]/.test(url) || /%0[ad]/i.test(url) || url.startsWith("//")) return;
  if (url.startsWith("/") || (!media && url.startsWith("#"))) return url;
  try {
    const parsed = new URL(url);
    const allowed = media ? ["http:", "https:"] : ["http:", "https:", "mailto:", "tel:"];
    if (allowed.includes(parsed.protocol) && !parsed.username && !parsed.password) return url;
  } catch { /* Relative page links are handled by cmsHref. */ }
}

export function cmsHref(value: unknown, context: LinkContext): string | undefined {
  if (typeof value !== "string" || !value.trim()) return;
  let raw = value.trim();
  if (/[\u0000-\u0020\\]/.test(raw) || raw.startsWith("//")) return;
  if (raw.startsWith("#") || /^(mailto:|tel:)/i.test(raw)) return safeUrl(raw);
  if (/^[a-z][a-z\d+.-]*:/i.test(raw)) {
    const safe = safeUrl(raw);
    if (!safe) return;
    const url = new URL(safe);
    if (url.host !== new URL(context.backendOrigin).host) return safe;
    // Never turn an admin/API destination into a public link.
    if (!/^\/storefront(?:\/|$)/.test(url.pathname)) return;
    raw = `${url.pathname}${url.search}${url.hash}`;
  }
  const url = new URL(raw.startsWith("/") ? raw : `/${raw}`, "https://frontend.invalid");
  let segments = url.pathname.split("/").filter(Boolean);
  if (segments[0] === "storefront") segments = segments.slice(1);
  if (context.locales.includes(segments[0])) segments = segments.slice(1);
  if (["admin", "api"].includes(segments[0])) return;
  try { segments = segments.map(decodeURIComponent); } catch { return; }
  if (segments.some((s) => /[\/\\?#]/.test(s))) return;
  url.searchParams.delete("locale"); url.searchParams.delete("channel");
  return pageHref(context.locale, segments.join("/")) + url.search + url.hash;
}

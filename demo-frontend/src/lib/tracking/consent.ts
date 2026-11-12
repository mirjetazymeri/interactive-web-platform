/**
 * The visitor's cookie choice, stored in one first-party, strictly necessary
 * cookie. A choice made under another policy version, or older than the
 * configured lifetime, no longer counts and the visitor is asked again.
 */

export const consentCookie = "px_consent";

/** Window event that opens the cookie choices again (the footer "Cookie settings" button). */
export const consentSettingsEvent = "px:consent-settings";

export type Consent = { version: string; analytics: boolean; marketing: boolean; decidedAt: number };

export function serializeConsent(consent: Consent) {
  return encodeURIComponent(JSON.stringify({ v: consent.version, a: consent.analytics ? 1 : 0, m: consent.marketing ? 1 : 0, t: consent.decidedAt }));
}

/** The stored choice from a Cookie header / document.cookie, or null when absent, malformed, outdated or expired. */
export function readConsent(cookieHeader: string, version: string, expiryDays: number, now = Date.now()): Consent | null {
  const raw = cookieHeader.split(/;\s*/).find((part) => part.startsWith(`${consentCookie}=`))?.slice(consentCookie.length + 1);
  if (!raw) return null;
  try {
    const value = JSON.parse(decodeURIComponent(raw)) as { v?: unknown; a?: unknown; m?: unknown; t?: unknown };
    if (value.v !== version || typeof value.t !== "number" || now - value.t > expiryDays * 86_400_000 || value.t > now + 60_000) return null;
    return { version, analytics: value.a === 1, marketing: value.m === 1, decidedAt: value.t };
  } catch {
    return null;
  }
}

/** Cookie attributes for the choice: whole site, its own lifetime, never sent cross-site. */
export function consentCookieString(consent: Consent, expiryDays: number, secure: boolean) {
  return `${consentCookie}=${serializeConsent(consent)}; Path=/; Max-Age=${expiryDays * 86_400}; SameSite=Lax${secure ? "; Secure" : ""}`;
}

/** Google Consent Mode v2 signals for a choice. */
export function googleConsent(consent: Pick<Consent, "analytics" | "marketing">) {
  const state = (granted: boolean) => granted ? "granted" as const : "denied" as const;
  return {
    analytics_storage: state(consent.analytics),
    ad_storage: state(consent.marketing),
    ad_user_data: state(consent.marketing),
    ad_personalization: state(consent.marketing),
  };
}

/** Analytics and advertising cookies the providers set; removed when consent is withdrawn. */
export const trackingCookiePattern = /^(?:_ga(?:_.*)?|_gid|_gat(?:_.*)?|_gcl_.*|_fbp|_fbc)$/;

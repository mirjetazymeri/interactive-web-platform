"use client";

import { useCallback, useEffect, useId, useMemo, useRef, useState, useSyncExternalStore } from "react";
import type { TrackingConfig } from "@/lib/tracking/config";
import { consentCookie, consentCookieString, consentSettingsEvent, readConsent, trackingCookiePattern, type Consent } from "@/lib/tracking/consent";
import { linkEvent, setTrackingSink, track } from "@/lib/tracking/events";
import { loadProviders, send, type Loaded } from "@/lib/tracking/providers";
import styles from "./consent-banner.module.css";

function copy(sq: boolean, categories: TrackingConfig["categories"]) {
  const uses = [categories.analytics && (sq ? "cookies analitike për të kuptuar si përdoret faqja" : "analytics cookies to understand how the site is used"),
    categories.marketing && (sq ? "cookies marketingu për të matur reklamat tona" : "marketing cookies to measure our advertising")].filter(Boolean).join(sq ? " dhe " : " and ");
  return sq ? {
    title: "Cookies në këtë faqe",
    text: `Përdorim cookies që janë të nevojshme që faqja të funksionojë. Me lejen tuaj përdorim edhe ${uses}. Zgjedhjen mund ta ndryshoni në çdo kohë te “Cilësimet e cookies”.`,
    accept: "Prano të gjitha", reject: "Refuzo", choose: "Zgjidh", save: "Ruaj zgjedhjet",
    necessary: "Të nevojshme", necessaryHint: "Gjithmonë aktive", analytics: "Analitika", marketing: "Marketingu",
    privacy: "Politika e privatësisë", cookies: "Politika e cookies", label: "Pëlqimi për cookies",
  } : {
    title: "Cookies on this site",
    text: `We use cookies that are needed for the site to work. With your permission we also use ${uses}. You can change your choice at any time under “Cookie settings”.`,
    accept: "Accept all", reject: "Reject all", choose: "Choose", save: "Save choices",
    necessary: "Necessary", necessaryHint: "Always on", analytics: "Analytics", marketing: "Marketing",
    privacy: "Privacy policy", cookies: "Cookie policy", label: "Cookie consent",
  };
}

// document.cookie has no change event; the banner announces its own writes.
const cookieListeners = new Set<() => void>();
const subscribeCookie = (listener: () => void) => { cookieListeners.add(listener); return () => { cookieListeners.delete(listener); }; };
const cookieChanged = () => cookieListeners.forEach((listener) => listener());
/** Only the consent cookie, so cookies set by the providers themselves do not count as a new choice. */
const consentCookieEntry = () => document.cookie.split(/;\s*/).find((part) => part.startsWith(`${consentCookie}=`)) ?? "";

/** Remove analytics/advertising cookies on this host and its parent domain when consent is withdrawn. */
function clearTrackingCookies() {
  const host = location.hostname;
  const domains = ["", host, `.${host.split(".").slice(-2).join(".")}`];
  for (const part of document.cookie.split(/;\s*/)) {
    const name = part.split("=")[0];
    if (!trackingCookiePattern.test(name)) continue;
    for (const domain of domains) document.cookie = `${name}=; Path=/; Max-Age=0${domain ? `; Domain=${domain}` : ""}`;
  }
}

/**
 * Consent banner, provider loading and the site-wide click events. Rendered
 * only when trackingConfig() allows tracking; in debug mode nothing loads and
 * events are logged to the console.
 */
export function TrackingProvider({ config }: { config: TrackingConfig }) {
  // The stored choice, read from the cookie after hydration (the server renders no banner).
  const cookie = useSyncExternalStore(subscribeCookie, consentCookieEntry, () => null);
  const consent = useMemo(() => cookie === null ? undefined : readConsent(cookie, config.version, config.expiryDays), [cookie, config.version, config.expiryDays]);
  const [reopened, setReopened] = useState(false);
  const [choosing, setChoosing] = useState(false);
  const [choice, setChoice] = useState({ analytics: false, marketing: false });
  const loaded = useRef<Loaded>({ gtm: false, ga4: false, metaPixel: false });
  const id = useId();
  const t = copy(config.locale === "sq", config.categories);
  const open = reopened || (consent === null && config.banner);

  const apply = useCallback((next: Pick<Consent, "analytics" | "marketing">) => {
    if (config.mode === "debug") {
      console.info("[tracking:debug] consent", next, "— no provider loads outside production.");
      setTrackingSink(next.analytics || next.marketing ? (name, params) => console.info("[tracking:debug]", name, params) : null, { flush: true });
      return;
    }
    loaded.current = loadProviders(config, next);
    const any = loaded.current.gtm || loaded.current.ga4 || loaded.current.metaPixel;
    setTrackingSink(any ? (name, params) => send(loaded.current, name, params) : null, { flush: any });
  }, [config]);

  // No consent needed, a stored choice, or wait for the banner (events wait too).
  useEffect(() => {
    if (consent === undefined) return;
    if (!config.requireConsent) apply({ analytics: true, marketing: true });
    else if (consent) {
      if (consent.analytics || consent.marketing) apply(consent);
      else setTrackingSink(null, { flush: false });
    } else if (!config.banner) setTrackingSink(null, { flush: false });
  }, [apply, consent, config.requireConsent, config.banner]);

  // Contact, service-inquiry, email, phone and WhatsApp links anywhere on the page.
  useEffect(() => {
    const onClick = (event: MouseEvent) => {
      const anchor = (event.target as Element | null)?.closest?.("a[href]");
      if (!anchor) return;
      const area = anchor.closest("header, .site-chrome") ? "header" : anchor.closest("footer") ? "footer" : "content";
      const found = linkEvent(anchor.getAttribute("href"), { origin: location.origin, locale: config.locale, area });
      if (found) track(found.name, found.params);
    };
    document.addEventListener("click", onClick, { capture: true });
    return () => document.removeEventListener("click", onClick, { capture: true });
  }, [config.locale]);

  useEffect(() => {
    const reopen = () => { setChoice({ analytics: !!consent?.analytics, marketing: !!consent?.marketing }); setChoosing(true); setReopened(true); };
    window.addEventListener(consentSettingsEvent, reopen);
    return () => window.removeEventListener(consentSettingsEvent, reopen);
  }, [consent]);

  function decide(next: { analytics: boolean; marketing: boolean }) {
    const granted = { analytics: next.analytics && config.categories.analytics, marketing: next.marketing && config.categories.marketing };
    const withdrawn = (consent?.analytics && !granted.analytics) || (consent?.marketing && !granted.marketing);
    document.cookie = consentCookieString({ version: config.version, ...granted, decidedAt: Date.now() }, config.expiryDays, location.protocol === "https:");
    setReopened(false); setChoosing(false);
    if (withdrawn) {
      // Loaded scripts cannot be unloaded: clear their cookies and start a clean page.
      clearTrackingCookies();
      location.reload();
      return;
    }
    // The new cookie becomes the stored choice; the effect above applies it.
    cookieChanged();
  }

  if (!open) return null;
  return <section className={styles.banner} role="region" aria-labelledby={`${id}-title`} aria-describedby={`${id}-text`} data-consent-banner>
    <div className={styles.copy}>
      <h2 className={styles.title} id={`${id}-title`}>{config.copy.title || t.title}</h2>
      <p className={styles.text} id={`${id}-text`}>{config.copy.text || t.text}</p>
      {(config.privacyHref || config.cookieHref) && <p className={styles.links}>
        {config.privacyHref && <a href={config.privacyHref}>{t.privacy}</a>}
        {config.cookieHref && config.cookieHref !== config.privacyHref && <a href={config.cookieHref}>{t.cookies}</a>}
      </p>}
    </div>
    {choosing && <fieldset className={styles.choices}>
      <legend className="sr-only">{t.label}</legend>
      <label className={styles.choice}><input type="checkbox" checked disabled /><span>{t.necessary}<small>{t.necessaryHint}</small></span></label>
      {config.categories.analytics && <label className={styles.choice}><input type="checkbox" checked={choice.analytics} onChange={(e) => setChoice({ ...choice, analytics: e.target.checked })} /><span>{t.analytics}</span></label>}
      {config.categories.marketing && <label className={styles.choice}><input type="checkbox" checked={choice.marketing} onChange={(e) => setChoice({ ...choice, marketing: e.target.checked })} /><span>{t.marketing}</span></label>}
    </fieldset>}
    <div className={styles.actions}>
      {choosing
        ? <button type="button" className={`button ${styles.secondary}`} onClick={() => decide(choice)}>{t.save}</button>
        : <button type="button" className={`button ${styles.secondary}`} onClick={() => { setChoice({ analytics: !!consent?.analytics, marketing: !!consent?.marketing }); setChoosing(true); }}>{t.choose}</button>}
      {config.showReject && <button type="button" className={`button ${styles.secondary}`} onClick={() => decide({ analytics: false, marketing: false })}>{t.reject}</button>}
      <button type="button" className="button" onClick={() => decide({ analytics: true, marketing: true })}>{t.accept}</button>
    </div>
  </section>;
}

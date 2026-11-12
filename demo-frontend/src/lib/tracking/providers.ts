"use client";

import type { TrackingConfig } from "./config";
import { googleConsent, type Consent } from "./consent";
import { metaPixelEvent, type TrackingEvent } from "./events";

/**
 * Browser-side loaders for the configured providers. Nothing here runs until
 * the tracking provider has a consent decision that allows it; script URLs
 * are fixed provider hosts with validated, URL-encoded IDs.
 */

type Fbq = ((...args: unknown[]) => void) & { callMethod?: (...args: unknown[]) => void; queue: unknown[]; push: unknown; loaded: boolean; version: string };
declare global {
  interface Window { dataLayer?: unknown[]; gtag?: (...args: unknown[]) => void; fbq?: Fbq; _fbq?: Fbq }
}

function inject(id: string, src: string) {
  if (document.getElementById(id)) return;
  const script = document.createElement("script");
  script.id = id;
  script.async = true;
  script.src = src;
  document.head.appendChild(script);
}

/** The gtag() command queue shared by GTM and GA4, with Consent Mode defaulting to denied. */
function google() {
  window.dataLayer = window.dataLayer || [];
  if (!window.gtag) {
    // gtag.js reads the arguments object itself (an array is ignored), so it must be pushed unchanged.
    // eslint-disable-next-line prefer-rest-params
    window.gtag = function gtag() { window.dataLayer!.push(arguments); };
    window.gtag("consent", "default", { ...googleConsent({ analytics: false, marketing: false }), wait_for_update: 500 });
    window.gtag("set", "ads_data_redaction", true);
  }
  return window.gtag;
}

export type Loaded = { gtm: boolean; ga4: boolean; metaPixel: boolean };

/** Providers started on this page; each starts once, so widening consent never repeats a page view. */
const started: Loaded = { gtm: false, ga4: false, metaPixel: false };

/** Load what the choice allows (scripts already on the page stay; withdrawing reloads the page). */
export function loadProviders(config: TrackingConfig, consent: Pick<Consent, "analytics" | "marketing">): Loaded {
  if (config.mode !== "live") return { ...started };
  if ((config.gtm || config.ga4) && (consent.analytics || consent.marketing)) {
    const gtag = google();
    gtag("consent", "update", googleConsent(consent));
    if (config.gtm && !started.gtm) {
      window.dataLayer!.push({ page_locale: config.locale });
      window.dataLayer!.push({ "gtm.start": Date.now(), event: "gtm.js" });
      inject("gtm-script", `https://www.googletagmanager.com/gtm.js?id=${encodeURIComponent(config.gtm)}`);
      started.gtm = true;
    }
    if (config.ga4 && consent.analytics && !started.ga4) {
      gtag("js", new Date());
      // GA4 sends the page_view of this full page load itself.
      gtag("config", config.ga4, { page_locale: config.locale });
      inject("ga4-script", `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(config.ga4)}`);
      started.ga4 = true;
    }
  }
  if (config.metaPixel && consent.marketing && !started.metaPixel) {
    if (!window.fbq) {
      // The standard Pixel stub: calls queue until fbevents.js takes over via callMethod.
      const fbq = ((...args: unknown[]) => { if (fbq.callMethod) fbq.callMethod(...args); else fbq.queue.push(args); }) as Fbq;
      fbq.queue = []; fbq.push = fbq; fbq.loaded = true; fbq.version = "2.0";
      window.fbq = fbq; window._fbq = fbq;
    }
    window.fbq("consent", "grant");
    window.fbq("init", config.metaPixel);
    window.fbq("track", "PageView");
    inject("meta-pixel-script", "https://connect.facebook.net/en_US/fbevents.js");
    started.metaPixel = true;
  }
  return { ...started };
}

/** Deliver one event to every loaded provider, once each. */
export function send(loaded: Loaded, name: TrackingEvent, params: Record<string, string | number | boolean>) {
  if (loaded.gtm) window.dataLayer?.push({ event: name, ...params });
  if (loaded.ga4) window.gtag?.("event", name, params);
  if (loaded.metaPixel) {
    const event = metaPixelEvent(name, params);
    const data = Object.fromEntries(Object.entries(event.params ?? {}).filter(([, value]) => value !== undefined));
    window.fbq?.(event.kind, event.name, data);
  }
}

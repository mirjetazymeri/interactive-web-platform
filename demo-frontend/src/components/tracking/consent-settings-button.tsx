"use client";

import { consentSettingsEvent } from "@/lib/tracking/consent";

/** Footer control to review or withdraw cookie consent at any time. */
export function ConsentSettingsButton({ label }: { label: string }) {
  return <button type="button" className="footer-consent" onClick={() => window.dispatchEvent(new Event(consentSettingsEvent))}>{label}</button>;
}

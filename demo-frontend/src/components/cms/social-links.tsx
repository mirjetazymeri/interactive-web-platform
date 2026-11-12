import type { ReactNode } from "react";
import { safeUrl } from "@/lib/cms/links";

/** The networks Settings → Social offers, in display order, with their names and line icons (24px, currentColor). */
const networks: { key: string; name: string; icon: ReactNode }[] = [
  { key: "linkedin", name: "LinkedIn", icon: <><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4v-7a6 6 0 0 1 6-6z" /><rect x="2" y="9" width="4" height="12" /><circle cx="4" cy="4" r="2" /></> },
  { key: "instagram", name: "Instagram", icon: <><rect x="2" y="2" width="20" height="20" rx="5" /><circle cx="12" cy="12" r="4" /><path d="M17.5 6.5h.01" /></> },
  { key: "facebook", name: "Facebook", icon: <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z" /> },
  { key: "twitter", name: "X", icon: <><path d="M4 4l11.7 16H20L8.3 4z" /><path d="M20 4l-6.4 7.3M10.4 12.7 4 20" /></> },
  { key: "youtube", name: "YouTube", icon: <><path d="M2.5 17a24 24 0 0 1 0-10 2 2 0 0 1 1.4-1.4 50 50 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24 24 0 0 1 0 10 2 2 0 0 1-1.4 1.4 50 50 0 0 1-16.2 0A2 2 0 0 1 2.5 17" /><path d="m10 15 5-3-5-3z" /></> },
  { key: "tiktok", name: "TikTok", icon: <path d="M9 12a4 4 0 1 0 4 4V3c.6 2.6 2.4 4.4 5 5" /> },
  { key: "pinterest", name: "Pinterest", icon: <><circle cx="12" cy="12" r="10" /><path d="M10.5 21 12.6 12.5M9.5 14.5c-1-.6-1.5-1.6-1.5-2.9C8 9.1 9.8 7.5 12.2 7.5c2.2 0 3.8 1.4 3.8 3.4 0 2.4-1.3 4.1-3 4.1-.7 0-1.2-.4-1-1" /></> },
];

/**
 * Footer social profiles from Settings → Social: one icon button per URL
 * that is filled in (and safe), labelled for screen readers.
 */
export function SocialLinks({ social, siteName, locale }: { social: Record<string, string | null>; siteName: string; locale: string }) {
  const links = networks.flatMap((network) => {
    const href = safeUrl(social[network.key]);
    return href ? [{ ...network, href }] : [];
  });
  if (!links.length) return null;
  const on = locale === "sq" ? "në" : "on";

  return <ul className="footer-social" aria-label={locale === "sq" ? "Rrjetet sociale" : "Social media"}>
    {links.map((link) => <li key={link.key}>
      <a href={link.href} target="_blank" rel="noopener noreferrer me" aria-label={`${siteName} ${on} ${link.name}`} title={link.name}>
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true" focusable="false">{link.icon}</svg>
      </a>
    </li>)}
  </ul>;
}

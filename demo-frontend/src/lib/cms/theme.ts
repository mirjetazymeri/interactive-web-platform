import type { CSSProperties } from "react";
import { CmsError } from "./transport";
import { parseTypography, typographyVariables } from "./typography";

export function hexColor(value: unknown): string | undefined {
  if (typeof value !== "string") return;
  const hex = value.trim().toLowerCase();
  if (/^#[0-9a-f]{6}$/.test(hex)) return hex;
  if (/^#[0-9a-f]{3}$/.test(hex)) return `#${[...hex.slice(1)].map((c) => c + c).join("")}`;
}
export function validateTheme(theme?: Record<string, unknown> | null) {
  const primary = hexColor(theme?.primary_color);
  const secondary = hexColor(theme?.secondary_color);
  const accent = hexColor(theme?.accent_color);
  if (!primary || !secondary || !accent) throw new CmsError("configuration", "CMS theme requires valid primary_color, secondary_color and accent_color hex colors.");
  return { primary, secondary, accent };
}
function rgb(hex: string) { return [1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16)); }
function luminance(hex: string) {
  const linear = rgb(hex).map((n) => { const v = n / 255; return v <= .04045 ? v / 12.92 : ((v + .055) / 1.055) ** 2.4; });
  return linear[0] * .2126 + linear[1] * .7152 + linear[2] * .0722;
}
export function contrast(a: string, b: string) { const x = luminance(a), y = luminance(b); return (Math.max(x, y) + .05) / (Math.min(x, y) + .05); }
// Black/white are contrast endpoints, never a substitute brand palette.
export function foreground(background: string) { return contrast(background, "#ffffff") >= contrast(background, "#000000") ? "#ffffff" : "#000000"; }
export function blend(base: string, overlay: string, opacity: number) {
  return `#${rgb(base).map((n, i) => Math.round(n * (1 - opacity) + rgb(overlay)[i] * opacity).toString(16).padStart(2, "0")).join("")}`;
}
export function readable(color: string, backgrounds: string[], ratio = 4.5) {
  const endpoint = foreground(backgrounds[0]);
  for (let step = 0; step <= 100; step++) {
    const candidate = blend(color, endpoint, step / 100);
    if (backgrounds.every((background) => contrast(candidate, background) >= ratio)) return candidate;
  }
  return endpoint;
}
export function themeVariables(theme?: Record<string, unknown> | null): CSSProperties {
  const { primary, secondary, accent } = validateTheme(theme);
  const text = foreground(primary);
  // Keep all surfaces on the same side of the contrast midpoint.
  const depth = text === "#ffffff" ? "#000000" : "#ffffff";
  const raised = blend(primary, text, .045);
  const surface = contrast(raised, text) >= 4.5 ? raised : blend(primary, depth, .10);
  const raisedAgain = blend(surface, text, .035);
  const elevated = contrast(raisedAgain, text) >= 4.5 ? raisedAgain : surface;
  const surfaces = [primary, surface, elevated];
  const ink = readable(text, surfaces);
  const muted = readable(blend(primary, ink, .68), surfaces);
  const border = readable(blend(primary, ink, .35), surfaces, 3);
  const accentText = readable(accent, surfaces);
  const secondaryAction = readable(secondary, surfaces, 3);
  const hover = blend(accent, foreground(accent), .08);
  const selected = blend(primary, accent, .08);
  // Also readable over the brightest/darkest possible image pixel.
  let opacity = 1;
  for (let step = 25; step <= 100; step++) {
    if (["#000000", "#ffffff"].every((pixel) => contrast(blend(pixel, primary, step / 100), ink) >= 4.5)) { opacity = step / 100; break; }
  }
  return {
    colorScheme: text === "#ffffff" ? "dark" : "light",
    "--color-primary": primary, "--color-secondary": secondary, "--color-accent": accent,
    "--surface-background": primary, "--surface": surface, "--surface-elevated": elevated,
    "--surface-foreground": ink, "--text-muted": muted,
    "--border": border, "--divider": blend(primary, ink, .22),
    "--accent-foreground": foreground(accent), "--accent-text": accentText,
    "--accent-hover": hover, "--accent-hover-foreground": foreground(hover),
    "--secondary-action": secondaryAction, "--secondary-foreground": foreground(secondaryAction),
    "--state-hover": elevated, "--state-selected": selected, "--state-selected-foreground": foreground(selected),
    "--disabled-background": surface, "--disabled-foreground": muted,
    "--focus-ring": accentText, "--overlay": `rgb(${rgb(primary).join(" ")} / ${opacity})`,
    "--shadow": `rgb(${rgb(blend(primary, depth, .5)).join(" ")} / .3)`,
    "--accent-glow": `rgb(${rgb(accent).join(" ")} / .12)`,
    "--feedback-background": surface, "--feedback-foreground": ink, "--feedback-border": accentText,
    "--navbar-surface": elevated, "--navbar-foreground": ink, "--navbar-active": accentText,
    ...typographyVariables(parseTypography(theme?.typography)),
  } as CSSProperties;
}

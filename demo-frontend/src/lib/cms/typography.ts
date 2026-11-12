import type { CSSProperties } from "react";
import generatedFaces from "./font-faces.generated.json";

// Curated families mirrored by the admin's theme.typography selectors; the generic
// family is the last-resort fallback while (or if) the self-hosted WOFF2 loads.
const FONTS = {
  "IBM Plex Mono": "monospace", Poppins: "sans-serif", Inter: "sans-serif", Manrope: "sans-serif", "DM Sans": "sans-serif",
  "Space Grotesk": "sans-serif", "IBM Plex Sans": "sans-serif", Roboto: "sans-serif", Montserrat: "sans-serif",
} as const;
export type FontFamily = keyof typeof FONTS;
export const FONT_FAMILIES = Object.keys(FONTS) as FontFamily[];
export const FONT_WEIGHTS = [300, 400, 500, 600, 700] as const;
export const fontSlug = (family: string) => family.toLowerCase().replace(/\s+/g, "-");

type Face = { weight: number[]; subset: string; unicodeRange: string; file: string };
const faces = generatedFaces as Record<FontFamily, { faces: Face[]; fallback: Record<string, string> }>;

export const TYPOGRAPHY_DEFAULTS = {
  display_font: "IBM Plex Mono" as FontFamily, body_font: "Poppins" as FontFamily,
  display_weight: 600, ui_weight: 500, body_weight: 400,
  body_size: 16, body_line_height: 1.6, heading_letter_spacing: -0.02, heading_scale: 1,
};
export type Typography = typeof TYPOGRAPHY_DEFAULTS;
// Same bounds as the admin validation; anything outside falls back to the default.
const RANGES = { body_size: [14, 20], body_line_height: [1.2, 2], heading_letter_spacing: [-0.05, 0.1], heading_scale: [0.8, 1.25] } as const;

/** Laravel stores settings as strings; each field is validated on its own so one bad value never drops the rest. */
export function parseTypography(raw: unknown): Typography {
  const input = raw && typeof raw === "object" ? raw as Record<string, unknown> : {};
  const font = (key: "display_font" | "body_font") => FONT_FAMILIES.find((f) => f === input[key]) ?? TYPOGRAPHY_DEFAULTS[key];
  const number = (value: unknown) => typeof value === "number" ? value : typeof value === "string" && value.trim() !== "" ? Number(value) : NaN;
  const weight = (key: "display_weight" | "ui_weight" | "body_weight") => {
    const value = number(input[key]);
    return (FONT_WEIGHTS as readonly number[]).includes(value) ? value : TYPOGRAPHY_DEFAULTS[key];
  };
  const ranged = (key: keyof typeof RANGES) => {
    const value = number(input[key]);
    return Number.isFinite(value) && value >= RANGES[key][0] && value <= RANGES[key][1] ? value : TYPOGRAPHY_DEFAULTS[key];
  };
  return {
    display_font: font("display_font"), body_font: font("body_font"),
    display_weight: weight("display_weight"), ui_weight: weight("ui_weight"), body_weight: weight("body_weight"),
    body_size: ranged("body_size"), body_line_height: ranged("body_line_height"),
    heading_letter_spacing: ranged("heading_letter_spacing"), heading_scale: ranged("heading_scale"),
  };
}

const stack = (family: FontFamily) => `"${family}", "${family} Fallback", ${FONTS[family]}`;

export function typographyVariables(typography: Typography): CSSProperties {
  return {
    "--font-display": stack(typography.display_font), "--font-body": stack(typography.body_font),
    "--font-weight-display": String(typography.display_weight), "--font-weight-ui": String(typography.ui_weight),
    "--font-weight-body": String(typography.body_weight),
    "--font-size-body": `${typography.body_size / 16}rem`, "--line-height-body": String(typography.body_line_height),
    "--letter-spacing-heading": `${typography.heading_letter_spacing}em`, "--heading-scale": String(typography.heading_scale),
  } as CSSProperties;
}

const families = (t: Typography) => [...new Set([t.display_font, t.body_font])];
const url = (file: string) => `/fonts/${file}`;

/** @font-face rules for the selected families only, plus metric-matched fallbacks to limit layout shift. */
export function fontFaceCss(typography: Typography): string {
  return families(typography).flatMap((family) => [
    ...faces[family].faces.map((face) => `@font-face{font-family:"${family}";font-style:normal;font-weight:${face.weight.join(" ")};font-display:swap;src:url(${url(face.file)}) format("woff2");unicode-range:${face.unicodeRange}}`),
    (({ fallbackFont, ascentOverride, descentOverride, lineGapOverride, sizeAdjust }) =>
      `@font-face{font-family:"${family} Fallback";src:local("${fallbackFont}");ascent-override:${ascentOverride};descent-override:${descentOverride};line-gap-override:${lineGapOverride};size-adjust:${sizeAdjust}}`)(faces[family].fallback),
  ]).join("");
}

/** The latin files rendered above the fold: display at its weight, body at its weight. */
export function fontPreloads(typography: Typography): string[] {
  const pick = (family: FontFamily, weight: number) => faces[family].faces.find((f) => f.subset === "latin" && f.weight[0] <= weight && weight <= f.weight.at(-1)!);
  return [...new Set([pick(typography.display_font, typography.display_weight), pick(typography.body_font, typography.body_weight)]
    .filter((face): face is Face => !!face).map((face) => url(face.file)))];
}

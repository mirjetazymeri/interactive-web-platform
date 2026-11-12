// Saved CMS CSS retains layout and typography, but interface paint belongs to
// the global theme. No source color (including a named color or gradient) escapes.
export function themeCssValue(property: string, value: string, selector: string, transparentAction = false): string {
  const action = /(?:\.(?:[\w-]*-)?(?:button|btn|cta)|\bbutton\b)(?:[:\s.#\[]|$)/i.test(selector);
  const hover = /:(?:hover|active)/.test(selector);
  const paint = action ? (hover ? "--accent-hover" : "--color-accent") : "--surface";
  const ink = action ? (transparentAction ? "--accent-text" : hover ? "--accent-hover-foreground" : "--accent-foreground") : "--surface-foreground";
  if (property === "color") return `var(${ink})`;
  if (property === "background" || property === "background-color") return /^(?:none|transparent)$/i.test(value) ? value : `var(${paint})`;
  if (property === "color-scheme") return "normal";
  if (property === "box-shadow") return value === "none" ? "none" : "0 8px 24px var(--shadow)";
  if (property.endsWith("color")) return value === "transparent" ? value : `var(${property.startsWith("outline") ? "--focus-ring" : "--border"})`;
  if (/^(?:border(?:-(?:top|right|bottom|left))?|outline)$/.test(property)) {
    if (/^(?:0|none)$/.test(value)) return value;
    const width = value.match(/(?:^|\s)(\d*\.?\d+(?:px|rem|em)|thin|medium|thick)(?:\s|$)/)?.[1] ?? "1px";
    const style = value.match(/\b(none|hidden|solid|dashed|dotted|double|groove|ridge|inset|outset)\b/)?.[1] ?? "solid";
    return `${width} ${style} var(${property === "outline" ? "--focus-ring" : "--border"})`;
  }
  if (property === "text-decoration") return value.match(/\b(none|underline|overline|line-through)\b/)?.[1] ?? "none";
  // Families come from the theme's typography roles; sizes, weights and spacing stay as authored.
  if (property === "font-family" || property === "font") {
    const role = /\bh[1-6]\b|title|heading/i.test(selector) ? "--type-heading"
      : action || /\b(?:nav|label|eyebrow|kicker|badge|tag|chip|pill)/i.test(selector) ? "--type-ui" : "--type-body";
    if (property === "font-family") return `var(${role})`;
    // Shorthand: keep style/weight/size/line-height and swap the trailing family list.
    const head = value.match(/^(.*?[\d.]+(?:px|r?em|%|vw|vh|pt)(?:\s*\/\s*[\d.]+(?:px|r?em|%)?)?)\s+\S/)?.[1];
    return head ? `${head} var(${role})` : value;
  }
  return value;
}

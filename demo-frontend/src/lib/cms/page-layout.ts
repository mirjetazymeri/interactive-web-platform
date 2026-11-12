import type { CmsPage } from "./schema";
import { isHeroType } from "./hero-world";

export const preferredSlots = ["main", "content", "sidebar", "below", "footer"];

// Homepage heroes can be assigned to any CMS slot. Render the first one at
// the top, and use the same placement for the shell's navbar overlay decision.
export function pageWidgets(page: CmsPage): CmsPage["widgets"] {
  if (page.path !== "home") return page.widgets;
  const names = ["hero", ...preferredSlots, ...Object.keys(page.widgets).filter((name) => name !== "hero" && !preferredSlots.includes(name))];
  for (const name of names) {
    const widgets = page.widgets[name] ?? [];
    const hero = [...widgets].sort((a, b) => a.position - b.position).find((widget) => isHeroType(widget.type));
    if (!hero) continue;
    const remaining = widgets.filter((widget) => widget !== hero);
    return {
      ...page.widgets,
      [name]: remaining,
      hero: [hero, ...(name === "hero" ? remaining : page.widgets.hero ?? [])],
    };
  }
  return page.widgets;
}

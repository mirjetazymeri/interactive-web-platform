import type { CmsPage, Widget } from "@/lib/cms/schema";
import { pageHref, type LinkContext } from "@/lib/cms/links";
import { CmsHtml } from "./html";
import { WidgetRenderer } from "./widgets";
import { str } from "@/lib/cms/config";
import { preferredSlots } from "@/lib/cms/page-layout";
import { projectsConfigSchema } from "@/lib/cms/projects";
import { serviceXLabels, serviceXMode } from "@/lib/cms/service-x";
import { isHeroType } from "@/lib/cms/hero-world";
import { bookingReach, groupContactOptions } from "@/lib/cms/contact-options";
import { normalizeMeetingConfig } from "@/lib/cms/meetings";
import { ContactOptions } from "./contact-options";

function placements(desktop: Widget[], mobile: Widget[], leading: Widget[] = []) {
  const key = (w: Widget) => w.placement_id === undefined ? `${w.id}:${w.slot}:${w.position}` : `placement-${w.placement_id}`;
  const merged = new Map(desktop.map((w) => [key(w), { widget: w, visibility: "desktop-only" }]));
  for (const widget of mobile) {
    const prior = merged.get(key(widget));
    if (prior && JSON.stringify(prior.widget) !== JSON.stringify(widget)) {
      merged.set(`${key(widget)}:mobile`, { widget, visibility: "mobile-only" });
      continue;
    }
    merged.set(key(widget), { widget, visibility: prior ? "" : "mobile-only" });
  }
  return Array.from(merged.values()).sort((a, b) => Number(leading.includes(b.widget)) - Number(leading.includes(a.widget)) || a.widget.position - b.widget.position);
}
export function PageBody({ desktop, mobile, context }: { desktop: CmsPage; mobile: CmsPage; context: LinkContext }) {
  const page = desktop;
  const home = page.path === "home";
  const stacked = [...Object.values(desktop.widgets), ...Object.values(mobile.widgets)].flat().some((w) => w.type === "service_x" && serviceXMode(w) === "stacked");
  const servicesPage = page.path === "services";
  const slotNames = Array.from(new Set([...Object.keys(desktop.widgets), ...Object.keys(mobile.widgets)]));
  const slots = [...preferredSlots, ...slotNames.filter((s) => s !== "hero" && !preferredSlots.includes(s))];
  const hasHero = !!(desktop.widgets.hero?.length || mobile.widgets.hero?.length);
  const desktopHero = isHeroType(desktop.widgets.hero?.[0]?.type) ? desktop.widgets.hero[0] : undefined;
  const mobileHero = isHeroType(mobile.widgets.hero?.[0]?.type) ? mobile.widgets.hero[0] : undefined;
  const leading = home ? [desktopHero, mobileHero].filter((widget): widget is Widget => !!widget) : [];
  const heroPlacements = placements(desktop.widgets.hero ?? [], mobile.widgets.hero ?? [], leading);
  const first = heroPlacements[0];
  // Interior pages keep their shared heading behavior. On home, each device's
  // leading hero owns its heading inside the corresponding visibility wrapper.
  const primary = first?.visibility === "" && isHeroType(first.widget.type) && (str(first.widget.config, "headline") || first.widget.title) ? first.widget : undefined;
  // A page led by a contact form lets the form's headline lead; the page title stays for assistive technology.
  const leadWidget = slots.filter((name) => slotNames.includes(name)).flatMap((name) => [...(desktop.widgets[name] ?? [])].sort((a, b) => a.position - b.position))[0];
  const contactLed = !hasHero && leadWidget?.type === "contact_form";
  // A page led by the full-screen project gallery opens straight into the gallery; the title stays for assistive technology.
  const galleryLed = !hasHero && leadWidget?.type === "projects" && projectsConfigSchema.safeParse(leadWidget.config).data?.variant === "vertical_scroll";
  function slot(name: string) {
    const items = placements(desktop.widgets[name] ?? [], mobile.widgets[name] ?? [], name === "hero" ? leading : []);
    const followingSlots = name === "hero" ? (home && !page.content ? slots : []) : slots.slice(slots.indexOf(name) + 1);
    const following = followingSlots.flatMap(slotName => placements(desktop.widgets[slotName] ?? [], mobile.widgets[slotName] ?? []));
    function handoff(index: number, device: "desktop" | "mobile") {
      if (items[index].widget.type !== "service_x") return undefined;
      const next = [...items.slice(index + 1), ...following].find(item => item.visibility !== (device === "desktop" ? "mobile-only" : "desktop-only"));
      if (next?.widget.type !== "projects") return undefined;
      const config = projectsConfigSchema.safeParse(next.widget.config);
      return config.success && config.data.variant === "horizontal_scroll" && config.data.projects.length > 0 ? "" : undefined;
    }
    const itemKey = ({ widget, visibility }: (typeof items)[number]) => `${widget.placement_id ?? `${widget.id}:${widget.slot}:${widget.position}`}:${visibility}`;
    const render = (widget: Widget) => <WidgetRenderer widget={widget} context={context} path={page.path} primaryHero={!servicesPage && name === "hero" && (home ? leading.includes(widget) : widget === primary)} heroTitleFallback={home ? page.title : undefined} />;
    // A contact form next to a meeting booking becomes one "how would you like to reach us?" choice.
    return <div className={`slot slot-${name === "hero" ? "hero" : "content"}`} data-slot={name} key={name}>{groupContactOptions(items).map((group) => {
      if (group.kind === "pair") {
        const { message, booking } = group.items;
        const meeting = normalizeMeetingConfig(booking.widget.config)!;
        return <div key={`contact-options:${itemKey(message)}`} className={message.visibility}>
          <ContactOptions locale={context.locale} order={group.order} reach={bookingReach(booking.widget)}
            cards={{
              message: { title: str(message.widget.config, "title") || message.widget.title || "" },
              booking: { title: meeting.copy.title },
            }}
            panels={{ message: render(message.widget), booking: render(booking.widget) }} />
        </div>;
      }
      const { widget, visibility } = group.item;
      const index = items.indexOf(group.item);
      return <div key={itemKey(group.item)} className={visibility} data-project-handoff-desktop={handoff(index, "desktop")} data-project-handoff-mobile={handoff(index, "mobile")}>{render(widget)}</div>;
    })}</div>;
  }
  const body = <>
    {hasHero && !servicesPage && slot("hero")}
    {page.path !== "home" && <nav className="breadcrumbs container" aria-label={context.locale === "sq" ? "Vendndodhja" : "Breadcrumbs"}><ol><li><a href={pageHref(context.locale, "home")}>{context.locale === "sq" ? "Kreu" : "Home"}</a></li>{page.breadcrumbs.filter((b) => b.path !== "home").map((b) => <li key={b.id}><a href={pageHref(context.locale, b.path)}>{b.title}</a></li>)}<li aria-current="page">{page.title}</li></ol></nav>}
    {servicesPage ? <h1 className="page-title container">{serviceXLabels(context.locale).services}</h1> : home ? (!(desktopHero && mobileHero) && <h1 className={`sr-only${desktopHero ? " mobile-only" : mobileHero ? " desktop-only" : ""}`}>{page.title}</h1>) : !primary && <h1 className={hasHero || contactLed || galleryLed ? "sr-only" : "page-title container"}>{page.title}</h1>}
    {hasHero && servicesPage && slot("hero")}
    {page.content && !contactLed && <section className="page-body container"><CmsHtml html={page.content} context={context} /></section>}
    {slots.filter((name) => slotNames.includes(name)).map(slot)}
  </>;
  return stacked || servicesPage ? <div className="services-page">{body}</div> : body;
}



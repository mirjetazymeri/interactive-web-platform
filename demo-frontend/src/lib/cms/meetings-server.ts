import { getLocales, getPage } from "./api";
import { normalizeMeetingConfig, type MeetingConfig } from "./meetings";

/**
 * The meeting booking widget a request claims to come from, re-read from the
 * CMS (desktop and mobile placements), or null when that page has no such
 * widget. Route handlers use it so only types and formats the widget really
 * offers ever reach the CMS.
 */
export async function bookingWidget(locale: string, path: string, widgetId: number): Promise<MeetingConfig | null> {
  const locales = await getLocales();
  if (!locales.locales.some((l) => l.code === locale)) return null;
  const [desktop, mobile] = await Promise.all([getPage(locale, path), getPage(locale, path, "mobile")]);
  const widget = [...Object.values(desktop?.widgets ?? {}).flat(), ...Object.values(mobile?.widgets ?? {}).flat()]
    .find((w) => w.id === widgetId && w.type === "meeting_booking");

  return widget ? normalizeMeetingConfig(widget.config) : null;
}

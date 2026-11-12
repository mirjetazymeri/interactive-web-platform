import type { Widget } from "./schema";
import { formatsOf, normalizeMeetingConfig, type MeetingFormat } from "./meetings";

/**
 * A contact form and a meeting booking placed next to each other on a page
 * are shown as one "how would you like to reach us?" choice instead of two
 * stacked sections. Editors control it by placement alone: same slot,
 * adjacent positions, same device visibility.
 */

export type ContactChoice = "message" | "booking";

/** Panel ids, also used as the URL hash so a choice can be linked (e.g. /contact#book-a-meeting). */
export const contactChoiceIds: Record<ContactChoice, string> = { message: "message", booking: "book-a-meeting" };

const choiceOf = (widget: Widget): ContactChoice | null =>
  widget.type === "contact_form" ? "message" : widget.type === "meeting_booking" && normalizeMeetingConfig(widget.config) ? "booking" : null;

export type ContactGroup<T> = { kind: "single"; item: T } | { kind: "pair"; items: Record<ContactChoice, T>; order: [ContactChoice, ContactChoice] };

/** Group placements, pairing the first adjacent contact form + bookable meeting widget (either order). */
export function groupContactOptions<T extends { widget: Widget; visibility: string }>(items: T[]): ContactGroup<T>[] {
  const groups: ContactGroup<T>[] = [];
  let paired = false;
  for (let i = 0; i < items.length; i++) {
    const current = items[i], next = items[i + 1];
    const a = choiceOf(current.widget), b = next ? choiceOf(next.widget) : null;
    if (!paired && a && b && a !== b && current.visibility === next.visibility) {
      groups.push({ kind: "pair", items: { [a]: current, [b]: next } as Record<ContactChoice, T>, order: [a, b] });
      paired = true;
      i++;
      continue;
    }
    groups.push({ kind: "single", item: current });
  }
  return groups;
}

/** How a booking can happen, for the booking card's cue line. */
export function bookingReach(widget: Widget): "both" | MeetingFormat {
  const formats = new Set((normalizeMeetingConfig(widget.config)?.types ?? []).flatMap(formatsOf));
  return formats.has("online") && formats.has("in_person") ? "both" : formats.has("in_person") ? "in_person" : "online";
}

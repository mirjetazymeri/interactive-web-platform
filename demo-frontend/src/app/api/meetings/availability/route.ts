import { getConfig } from "@/lib/cms/api";
import { CmsError, requestCms } from "@/lib/cms/transport";
import { availabilityQuerySchema, bookingError, offers, slotsResponseSchema } from "@/lib/cms/meetings";
import { bookingWidget } from "@/lib/cms/meetings-server";

/** Free slots for one meeting type and format, re-validated against the widget that offers them. */
export async function GET(request: Request) {
  const parsed = availabilityQuerySchema.safeParse(Object.fromEntries(new URL(request.url).searchParams));
  if (!parsed.success) return Response.json({ code: "invalid" }, { status: 422 });
  const query = parsed.data;
  try {
    const widget = await bookingWidget(query.locale, query.path, query.widgetId);
    if (!widget || !offers(widget, query.type, query.format)) return Response.json({ code: "invalid" }, { status: 404 });
    const params = new URLSearchParams({ type: query.type, format: query.format, from: new Date(query.from).toISOString(), to: new Date(query.to).toISOString() });
    const result = slotsResponseSchema.safeParse(await requestCms(getConfig(), `meetings/availability?${params}`, query.locale));
    if (!result.success) throw new CmsError("response", "Unexpected availability response.");
    // Short private caching: slots change as people book.
    return Response.json(result.data, { headers: { "Cache-Control": "private, max-age=30" } });
  } catch (error) {
    const { status, code } = bookingError(error instanceof CmsError ? error.status : undefined);
    console.error("[CMS meetings availability]", error instanceof CmsError ? `${error.kind}: ${error.message}` : "Unexpected failure");
    return Response.json({ code }, { status });
  }
}

import { getConfig } from "@/lib/cms/api";
import { CmsError, requestCms } from "@/lib/cms/transport";
import { bookingError, bookingInputSchema, bookingPayload, bookingResponseSchema, offers } from "@/lib/cms/meetings";
import { bookingWidget } from "@/lib/cms/meetings-server";

/** Create a booking. Same-origin JSON only; the widget, type and format are re-checked against the CMS. */
export async function POST(request: Request) {
  const origin = request.headers.get("origin");
  if (!origin || origin !== new URL(request.url).origin) return Response.json({ code: "invalid" }, { status: 403 });
  if (!request.headers.get("content-type")?.includes("application/json")) return Response.json({ code: "invalid" }, { status: 415 });
  const raw = await request.text();
  if (raw.length > 20_000) return Response.json({ code: "invalid" }, { status: 413 });
  let body: unknown;
  try { body = JSON.parse(raw); } catch { return Response.json({ code: "invalid" }, { status: 400 }); }
  const parsed = bookingInputSchema.safeParse(body);
  if (!parsed.success) return Response.json({ code: "invalid" }, { status: 422 });
  const input = parsed.data;
  try {
    const widget = await bookingWidget(input.locale, input.path, input.widgetId);
    if (!widget || !offers(widget, input.type, input.format)) return Response.json({ code: "invalid" }, { status: 404 });
    const result = bookingResponseSchema.safeParse(await requestCms(getConfig(), "meetings/bookings", input.locale, { method: "POST", body: JSON.stringify(bookingPayload(input)) }));
    if (!result.success) throw new CmsError("response", "Unexpected booking response.");
    return Response.json(result.data, { status: 201 });
  } catch (error) {
    const { status, code } = bookingError(error instanceof CmsError ? error.status : undefined);
    if (status === 502) console.error("[CMS meetings booking]", error instanceof CmsError ? `${error.kind}: ${error.message}` : "Unexpected failure");
    return Response.json({ code }, { status });
  }
}

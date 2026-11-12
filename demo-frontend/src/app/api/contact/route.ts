import { getConfig, getLocales, getPage } from "@/lib/cms/api";
import { CmsError, requestCms } from "@/lib/cms/transport";
import { contactInputSchema, contactPayload } from "@/lib/cms/contact-services";

export async function POST(request: Request) {
  const origin = request.headers.get("origin");
  if (!origin || origin !== new URL(request.url).origin) return Response.json({ message: "Invalid origin." }, { status: 403 });
  if (!request.headers.get("content-type")?.includes("application/json")) return Response.json({ message: "JSON required." }, { status: 415 });
  const raw = await request.text();
  if (raw.length > 20_000) return Response.json({ message: "Request too large." }, { status: 413 });
  let body: unknown;
  try { body = JSON.parse(raw); } catch { return Response.json({ message: "Invalid JSON." }, { status: 400 }); }
  const parsed = contactInputSchema.safeParse(body);
  if (!parsed.success) return Response.json({ message: "Check the form fields." }, { status: 422 });
  const input = parsed.data;
  try {
    const locales = await getLocales();
    if (!locales.locales.some((l) => l.code === input.locale)) return Response.json({ message: "Invalid locale." }, { status: 422 });
    const [desktop, mobile] = await Promise.all([getPage(input.locale, input.path), getPage(input.locale, input.path, "mobile")]);
    const widget = [...Object.values(desktop?.widgets ?? {}).flat(), ...Object.values(mobile?.widgets ?? {}).flat()].find((w) => w.id === input.widgetId && w.type === "contact_form");
    if (!widget) return Response.json({ message: "Form unavailable." }, { status: 404 });
    const message = contactPayload(input, widget.config);
    if (!message) return Response.json({ message: "Subject required." }, { status: 422 });
    const payload = await requestCms(getConfig(), "contact", input.locale, { method: "POST", body: JSON.stringify(message) });
    if (!payload || typeof payload !== "object" || !("success" in payload) || payload.success !== true) throw new CmsError("response", "Unexpected contact response.");
    return Response.json({ success: true });
  } catch (error) {
    const status = error instanceof CmsError && [422, 429].includes(error.status ?? 0) ? error.status! : 502;
    console.error("[CMS contact]", error instanceof CmsError ? `${error.kind}: ${error.message}` : "Unexpected failure");
    return Response.json({ message: "Unable to send message. Please try again later." }, { status });
  }
}

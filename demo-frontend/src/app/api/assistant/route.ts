import { CmsError } from "@/lib/cms/transport";
import { errorFor, messageInputSchema } from "@/lib/assistant/schema";
import { askAssistant } from "@/lib/assistant/server";
import { clientIp } from "@/lib/assistant/client-ip";

/** Ask the assistant. Same-origin JSON only; the Laravel engine does all matching. */
export async function POST(request: Request) {
  const origin = request.headers.get("origin");
  if (!origin || origin !== new URL(request.url).origin) return Response.json({ code: "invalid" }, { status: 403 });
  if (!request.headers.get("content-type")?.includes("application/json")) return Response.json({ code: "invalid" }, { status: 415 });
  const raw = await request.text();
  if (raw.length > 4_000) return Response.json({ code: "invalid" }, { status: 413 });
  let body: unknown;
  try { body = JSON.parse(raw); } catch { return Response.json({ code: "invalid" }, { status: 400 }); }
  const parsed = messageInputSchema.safeParse(body);
  if (!parsed.success) return Response.json({ code: "invalid" }, { status: 422 });

  // No locale lookup per message: Laravel falls back to its default language for unknown codes,
  // and links are re-validated against the requested locale, so a wrong code can only drop links.
  try {
    const reply = await askAssistant(parsed.data, clientIp(request.headers));
    return Response.json(reply, { headers: { "Cache-Control": "no-store" } });
  } catch (error) {
    const { status, code } = errorFor(error instanceof CmsError ? error.status : undefined);
    if (status === 502) console.error("[CMS assistant]", error instanceof CmsError ? `${error.kind}: ${error.message}` : "Unexpected failure");
    return Response.json({ code }, { status });
  }
}

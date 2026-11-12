import "server-only";
import { cache } from "react";
import { getConfig } from "@/lib/cms/api";
import { parseEnvelope, requestCms } from "@/lib/cms/transport";
import { bootstrapSchema, replySchema, type AssistantBootstrap, type AssistantMessageInput } from "./schema";

/**
 * Development-only preview: with ASSISTANT_PREVIEW_TOKEN set (and the same
 * value configured in Laravel), the assistant can be tried while it is still
 * hidden from visitors. Never sent from a production build.
 */
export function previewHeaders(): Record<string, string> {
  const token = process.env.ASSISTANT_PREVIEW_TOKEN;
  return process.env.NODE_ENV !== "production" && token && token.length >= 32 ? { "X-Assistant-Preview": token } : {};
}

/** Public assistant configuration, or null when it is hidden or the CMS cannot provide it. Never breaks the page. */
export const getAssistant = cache(async (locale: string): Promise<AssistantBootstrap | null> => {
  try {
    const data = parseEnvelope(await requestCms(getConfig(), "assistant/bootstrap", locale, { headers: previewHeaders() }), bootstrapSchema);
    return data.enabled ? data : null;
  } catch (error) {
    console.error("[CMS assistant]", error instanceof Error ? error.message : "Unexpected failure");
    return null;
  }
});

/** Forwards one message to the Laravel engine; matching and answers stay there. */
export async function askAssistant(input: AssistantMessageInput, clientIp: string | null) {
  const body = { ...(input.message ? { message: input.message } : {}), ...(input.intentId ? { intent_id: input.intentId } : {}), ...(input.context ? { context: input.context } : {}) };
  // Laravel only honours the forwarded address once this server is configured as a trusted proxy.
  const headers = { ...previewHeaders(), ...(clientIp ? { "X-Forwarded-For": clientIp } : {}) };
  const payload = await requestCms(getConfig(), "assistant/message", input.locale, { method: "POST", body: JSON.stringify(body), headers });

  return parseEnvelope(payload, replySchema(input.locale));
}

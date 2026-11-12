import { z } from "zod";
import { MAX_MESSAGE_LENGTH, contextSchema, replySchema, type AssistantContext, type AssistantReply } from "./schema";

/**
 * The conversation for this browser tab (sessionStorage): closing and
 * reopening the assistant, or moving between pages, keeps it; a new tab or
 * window starts fresh. Only the visible messages and the engine's context
 * are kept, never anything else about the visitor.
 */

export type AssistantEntry =
  | { id: string; role: "user"; text: string }
  | { id: string; role: "assistant"; reply: AssistantReply };
export type AssistantSession = { entries: AssistantEntry[]; context: AssistantContext | null };

export const MAX_ENTRIES = 30;
export const sessionKey = (locale: string) => `demo.assistant.v1.${locale}`;
export const emptySession = (): AssistantSession => ({ entries: [], context: null });

/** Restores a stored session, re-validating everything (stored data is untrusted). */
export function parseSession(raw: string | null, locale: string): AssistantSession {
  if (!raw) return emptySession();
  try {
    const entry = z.union([
      z.object({ id: z.string().max(40), role: z.literal("user"), text: z.string().min(1).max(MAX_MESSAGE_LENGTH) }),
      z.object({ id: z.string().max(40), role: z.literal("assistant"), reply: replySchema(locale) }),
    ]);
    const data = z.object({ v: z.literal(1), entries: z.array(entry).max(MAX_ENTRIES), context: contextSchema.nullable() }).parse(JSON.parse(raw));
    return { entries: data.entries as AssistantEntry[], context: data.context };
  } catch {
    return emptySession();
  }
}

/** The stored form: the latest messages only. Replies are re-checked on restore, links included. */
export function serializeSession(session: AssistantSession) {
  return JSON.stringify({ v: 1, entries: session.entries.slice(-MAX_ENTRIES), context: session.context });
}

export function loadSession(locale: string): AssistantSession {
  try { return parseSession(window.sessionStorage.getItem(sessionKey(locale)), locale); } catch { return emptySession(); }
}

export function saveSession(locale: string, session: AssistantSession) {
  try { window.sessionStorage.setItem(sessionKey(locale), serializeSession(session)); } catch { /* Private mode or full storage: the chat still works. */ }
}

export function clearSession(locale: string) {
  try { window.sessionStorage.removeItem(sessionKey(locale)); } catch { /* Nothing stored. */ }
}

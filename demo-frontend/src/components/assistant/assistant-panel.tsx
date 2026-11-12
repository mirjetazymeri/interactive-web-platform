"use client";

import dynamic from "next/dynamic";
import { useCallback, useEffect, useId, useLayoutEffect, useRef, useState, type FormEvent, type KeyboardEvent, type MouseEvent } from "react";
import { createPortal } from "react-dom";
import { replySchema, type AssistantBootstrap, type AssistantErrorCode, type AssistantReply, type AssistantSuggestion } from "@/lib/assistant/schema";
import { clearSession, emptySession, loadSession, saveSession, type AssistantEntry, type AssistantSession } from "@/lib/assistant/session";
import { assistantLabels, type AssistantFailure } from "@/lib/assistant/labels";
import type { OrbState } from "./assistant-orb";
import { AssistantReplyView } from "./assistant-reply";
import styles from "./assistant.module.css";

// The WebGL orb is its own chunk; the conversation is usable before it arrives.
const AssistantOrb = dynamic(() => import("./assistant-orb").then((m) => m.AssistantOrb), { ssr: false, loading: () => <span className={styles.orbPlaceholder} /> });

const REQUEST_TIMEOUT = 15_000;
// Only a request still running after this long says it is looking things up; fast answers never wait for it.
const RETRIEVING_AFTER = 700;
// A page link that has not unloaded the page by then is released again.
const NAVIGATION_GRACE = 12_000;
const MAX_INITIAL_SUGGESTIONS = 4;
const MAX_FOLLOW_UPS = 3;
type Request = { message?: string; intentId?: number; display: string };
const newId = () => `${Date.now().toString(36)}${Math.random().toString(36).slice(2, 8)}`;
const FOCUSABLE = 'a[href],button:not([disabled]),textarea:not([disabled]),input:not([disabled]),[tabindex]:not([tabindex="-1"])';

/**
 * The full-screen assistant. Desktop: conversation beside the orb. Phones: a
 * compact header, the orb above the welcome, then a chat-first layout once
 * the conversation starts (the orb folds into the header's status light).
 */
export function AssistantPanel({ config, locale, onClose }: { config: AssistantBootstrap; locale: string; onClose: () => void }) {
  const t = assistantLabels(locale);
  const titleId = useId();
  const dialog = useRef<HTMLDivElement>(null);
  const input = useRef<HTMLTextAreaElement>(null);
  const log = useRef<HTMLDivElement>(null);
  const [session, setSession] = useState<AssistantSession>(() => loadSession(locale));
  const sessionRef = useRef(session);
  const pendingRef = useRef(false);
  const [pending, setPending] = useState(false);
  const [slow, setSlow] = useState(false);
  const [failure, setFailure] = useState<{ code: AssistantFailure; request: Request } | null>(null);
  const [orb, setOrb] = useState<OrbState>("idle");
  const [draft, setDraft] = useState("");
  // Answers that arrived while open animate in; restored ones simply appear.
  const [fresh, setFresh] = useState<ReadonlySet<string>>(() => new Set());
  const [navigating, setNavigating] = useState<string | null>(null);
  const navigatingRef = useRef<string | null>(null);
  const orbTimer = useRef<number | undefined>(undefined);
  const inFlight = useRef<{ controller: AbortController; userId: string | null } | null>(null);
  const closed = useRef(false);

  useEffect(() => { sessionRef.current = session; saveSession(locale, session); }, [locale, session]);

  // Modal behaviour: the page behind is inert and does not scroll; the first focus goes to the input on desktop.
  useEffect(() => {
    // Open (again): Strict Mode runs this cleanup once on mount, which must not leave the panel marked closed.
    closed.current = false;
    const shell = document.querySelector<HTMLElement>(".site-shell");
    const root = document.documentElement;
    const overflow = root.style.overflow;
    if (shell) shell.inert = true;
    root.style.overflow = "hidden";
    if (window.matchMedia("(pointer: fine)").matches) input.current?.focus({ preventScroll: true });
    else dialog.current?.focus({ preventScroll: true });
    // Phones: follow the visible area, so the composer sits on the keyboard and nothing jumps.
    const viewport = window.visualViewport;
    const fit = () => {
      dialog.current?.style.setProperty("--assistant-height", `${Math.round(viewport?.height ?? window.innerHeight)}px`);
      dialog.current?.style.setProperty("--assistant-top", `${Math.round(viewport?.offsetTop ?? 0)}px`);
    };
    fit();
    viewport?.addEventListener("resize", fit);
    viewport?.addEventListener("scroll", fit);
    // Back from a page opened from the assistant (bfcache): the link is usable again.
    const release = () => { navigatingRef.current = null; setNavigating(null); };
    window.addEventListener("pageshow", release);
    return () => {
      if (shell) shell.inert = false;
      root.style.overflow = overflow;
      viewport?.removeEventListener("resize", fit);
      viewport?.removeEventListener("scroll", fit);
      window.removeEventListener("pageshow", release);
      window.clearTimeout(orbTimer.current);
      // Closed mid-question: cancel it and don't keep a question without its answer.
      const request = inFlight.current;
      closed.current = true;
      if (request) {
        request.controller.abort();
        const current = sessionRef.current;
        if (request.userId) saveSession(locale, { ...current, entries: current.entries.filter((entry) => entry.id !== request.userId) });
      }
    };
    // Set up once per opening; locale is fixed for the panel's lifetime.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  // Keep the newest message in view, inside the conversation only.
  useLayoutEffect(() => {
    const box = log.current;
    if (!box) return;
    const smooth = !window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    box.scrollTo({ top: box.scrollHeight, behavior: smooth ? "smooth" : "auto" });
  }, [session.entries.length, pending, failure]);

  // The composer grows with the question, up to a few lines.
  useLayoutEffect(() => {
    const box = input.current;
    if (!box) return;
    box.style.height = "auto";
    box.style.height = `${Math.min(box.scrollHeight, 132)}px`;
  }, [draft]);

  const settle = useCallback((state: OrbState, after: number) => {
    setOrb(state);
    window.clearTimeout(orbTimer.current);
    orbTimer.current = window.setTimeout(() => setOrb("idle"), after);
  }, []);

  const send = useCallback(async (request: Request, retry = false) => {
    if (pendingRef.current) return; // one question at a time
    pendingRef.current = true;
    setPending(true);
    setFailure(null);
    window.clearTimeout(orbTimer.current);
    setOrb("thinking");
    const userId = newId();
    if (!retry) setSession((s) => ({ ...s, entries: [...s.entries, { id: userId, role: "user", text: request.display }] }));
    // Aborted on timeout, or when the panel closes mid-question (see the unmount cleanup).
    const controller = new AbortController();
    const timer = window.setTimeout(() => controller.abort(new DOMException("Timed out", "TimeoutError")), REQUEST_TIMEOUT);
    const slowTimer = window.setTimeout(() => setSlow(true), RETRIEVING_AFTER);
    inFlight.current = { controller, userId: retry ? null : userId };
    try {
      const context = sessionRef.current.context ?? undefined;
      const response = await fetch("/api/assistant", {
        method: "POST", headers: { "Content-Type": "application/json" }, signal: controller.signal,
        body: JSON.stringify({ locale, ...(request.message ? { message: request.message } : {}), ...(request.intentId ? { intentId: request.intentId } : {}), ...(context ? { context } : {}) }),
      });
      const body: unknown = await response.json().catch(() => null);
      if (!response.ok) {
        const code = (body as { code?: AssistantErrorCode } | null)?.code;
        throw new AssistantRequestError(code && ["invalid", "unavailable", "busy"].includes(code) ? code : "failed");
      }
      const reply = replySchema(locale).safeParse(body);
      if (!reply.success) throw new AssistantRequestError("failed");
      const entry: AssistantEntry = { id: newId(), role: "assistant", reply: reply.data };
      setFresh((ids) => new Set(ids).add(entry.id));
      setSession((s) => ({ entries: [...s.entries, entry], context: reply.data.context }));
      settle("responding", 1400);
    } catch (error) {
      if (closed.current) return; // the visitor closed the panel; nothing to show
      const code: AssistantFailure = error instanceof AssistantRequestError ? error.code
        : error instanceof DOMException && (error.name === "TimeoutError" || error.name === "AbortError") ? "timeout" : "failed";
      setFailure({ code, request });
      settle("error", 2400);
    } finally {
      window.clearTimeout(timer);
      window.clearTimeout(slowTimer);
      inFlight.current = null;
      pendingRef.current = false;
      setPending(false);
      setSlow(false);
    }
  }, [locale, settle]);

  const submit = (event?: FormEvent) => {
    event?.preventDefault();
    const text = draft.trim();
    if (!text || pendingRef.current) return;
    setDraft("");
    void send({ message: text, display: text });
  };
  // Enter sends; Shift+Enter adds a line; never while composing (IME).
  const onComposerKey = (event: KeyboardEvent<HTMLTextAreaElement>) => {
    if (event.key === "Enter" && !event.shiftKey && !event.nativeEvent.isComposing) {
      event.preventDefault();
      submit();
    }
  };
  // The chip that was clicked disappears with the answer; keep focus in the conversation.
  const keepFocus = () => (window.matchMedia("(pointer: fine)").matches ? input.current : dialog.current)?.focus({ preventScroll: true });
  const ask = (suggestion: AssistantSuggestion) => { keepFocus(); void send({ intentId: suggestion.id, display: suggestion.question }); };
  const restart = () => {
    clearSession(locale);
    setSession(emptySession());
    setFailure(null);
    keepFocus();
  };

  /**
   * Links from answers. Same page: close and go to the section. Another page: the browser
   * loads it (the site's normal document navigation); the link shows that it is opening and
   * further clicks are ignored until the page changes.
   */
  const navigate = useCallback((event: MouseEvent<HTMLAnchorElement>, href: string) => {
    if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return; // new tab/window: nothing to manage
    if (navigatingRef.current) { event.preventDefault(); return; }
    const url = new URL(href, window.location.href);
    if (url.pathname === window.location.pathname) {
      event.preventDefault();
      onClose();
      requestAnimationFrame(() => {
        if (url.hash) window.location.hash = url.hash;
        else window.scrollTo({ top: 0 });
      });
      return;
    }
    navigatingRef.current = href;
    setNavigating(href);
    window.setTimeout(() => { if (navigatingRef.current === href) { navigatingRef.current = null; setNavigating(null); } }, NAVIGATION_GRACE);
  }, [onClose]);

  // Escape closes; Tab stays inside the dialog.
  const onKeyDown = (event: KeyboardEvent<HTMLDivElement>) => {
    if (event.key === "Escape") { event.stopPropagation(); onClose(); return; }
    if (event.key !== "Tab" || !dialog.current) return;
    const items = Array.from(dialog.current.querySelectorAll<HTMLElement>(FOCUSABLE)).filter((el) => el.offsetParent !== null);
    if (!items.length) return;
    const first = items[0], last = items[items.length - 1];
    if (event.shiftKey && (document.activeElement === first || document.activeElement === dialog.current)) { event.preventDefault(); last.focus(); }
    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
  };

  const entries = session.entries;
  const lastAssistant = [...entries].reverse().find((entry) => entry.role === "assistant")?.id;
  const status = pending ? (slow ? t.retrieving : t.thinking) : "";

  return createPortal(
    <div ref={dialog} className={styles.panel} role="dialog" aria-modal="true" aria-labelledby={titleId} tabIndex={-1}
      onKeyDown={onKeyDown} data-phase={entries.length ? "chat" : "welcome"} data-orb={orb} data-busy={pending || undefined}>
      <header className={styles.bar}>
        <div className={styles.identity}>
          <span className={styles.statusOrb} data-state={orb} aria-hidden="true" />
          <div className={styles.identityText}>
            <h2 id={titleId} className={styles.name}>{config.name}</h2>
            <p className={styles.status}>
              <span>{pending ? t.busy : t.ready}</span>
              {config.preview && <span className={styles.preview} title={t.preview}>
                <span aria-hidden="true">{t.previewShort}</span><span className={styles.srOnly}>{t.preview}</span>
              </span>}
            </p>
          </div>
        </div>
        <div className={styles.barActions}>
          {entries.length > 0 && <button type="button" className={styles.iconButton} onClick={restart} disabled={pending} aria-label={t.newChat} title={t.newChat}>
            <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="M4 12a8 8 0 1 0 2.4-5.7M4 4v4.5h4.5" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" /></svg>
          </button>}
          <button type="button" className={styles.iconButton} onClick={onClose} aria-label={t.close} title={t.close}>
            <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6 6 18" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" /></svg>
          </button>
        </div>
      </header>

      <div className={styles.orbStage} aria-hidden="true"><AssistantOrb state={orb} /></div>

      <section className={styles.conversation} aria-labelledby={titleId}>
        <div ref={log} className={styles.log} role="log" aria-live="polite" aria-relevant="additions">
          <div className={styles.thread}>
            <p className={`${styles.message} ${styles.welcome}`}>{config.welcome_message}</p>
            {entries.length === 0 && config.suggestions.length > 0 && <Chips label={t.suggestions} items={config.suggestions.slice(0, MAX_INITIAL_SUGGESTIONS)} onPick={ask} disabled={pending} />}
            {entries.map((entry) => entry.role === "user"
              ? <p key={entry.id} className={styles.userMessage}><span className={styles.srOnly}>{t.you}: </span>{entry.text}</p>
              : <AssistantReplyView key={entry.id} reply={entry.reply} labels={t} latest={entry.id === lastAssistant} animate={fresh.has(entry.id)}
                  navigating={navigating} onNavigate={navigate}
                  followUps={entry.id === lastAssistant && !pending && !failure ? <ReplySuggestions reply={entry.reply} label={t.followUps} onPick={ask} /> : null} />)}
            {pending && <div className={styles.thinking} aria-hidden="true"><span className={styles.dots}><i /><i /><i /></span><span key={status} className={styles.thinkingLabel}>{status}</span></div>}
            {failure && <div className={styles.errorMessage} role="alert">
              <p>{t.errors[failure.code]}</p>
              {failure.code !== "invalid" && <button type="button" className={styles.textButton} onClick={() => void send(failure.request, true)}>{t.retry}</button>}
            </div>}
          </div>
        </div>
        {/* What the assistant is doing, for screen readers (the dots above are decorative). */}
        <p className={styles.srOnly} role="status">{status}</p>

        <form className={styles.composer} onSubmit={submit} data-busy={pending || undefined}>
          <label htmlFor={`${titleId}-input`} className={styles.srOnly}>{t.composer}</label>
          <textarea ref={input} id={`${titleId}-input`} className={styles.input} rows={1} value={draft} onChange={(e) => setDraft(e.target.value)} onKeyDown={onComposerKey}
            placeholder={config.placeholder} maxLength={config.max_length} autoComplete="off" enterKeyHint="send" spellCheck />
          <button type="submit" className={styles.send} disabled={pending || !draft.trim()} aria-label={t.send}>
            {pending
              ? <span className={styles.spinner} aria-hidden="true" />
              : <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="M12 19V5M6 11l6-6 6 6" fill="none" stroke="currentColor" strokeWidth="1.9" strokeLinecap="round" strokeLinejoin="round" /></svg>}
          </button>
        </form>
        {config.notice && <p className={styles.notice}>{config.notice}</p>}
      </section>
    </div>,
    document.body,
  );
}

class AssistantRequestError extends Error {
  constructor(public code: AssistantErrorCode) { super(code); }
}

function ReplySuggestions({ reply, label, onPick }: { reply: AssistantReply; label: string; onPick: (s: AssistantSuggestion) => void }) {
  return reply.suggestions.length ? <Chips label={label} items={reply.suggestions.slice(0, MAX_FOLLOW_UPS)} onPick={onPick} followUp /> : null;
}

function Chips({ label, items, onPick, disabled = false, followUp = false }: { label: string; items: AssistantSuggestion[]; onPick: (s: AssistantSuggestion) => void; disabled?: boolean; followUp?: boolean }) {
  return <div className={styles.chips} role="group" aria-label={label} data-follow-up={followUp || undefined}>
    {items.map((item, index) => <button key={item.id} type="button" className={styles.chip} onClick={() => onPick(item)} disabled={disabled} style={{ "--i": index } as React.CSSProperties}>
      <span aria-hidden="true" className={styles.chipArrow}>↳</span>{item.question}
    </button>)}
  </div>;
}

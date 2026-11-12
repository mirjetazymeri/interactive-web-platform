/* eslint-disable @next/next/no-img-element -- Project images are CMS URLs resolved by Laravel, like the widget media. */
import type { CSSProperties, MouseEvent, ReactNode } from "react";
import { actionKey, cardKey, type AssistantAction, type AssistantCard, type AssistantReply } from "@/lib/assistant/schema";
import type { AssistantLabels } from "@/lib/assistant/labels";
import styles from "./assistant.module.css";

type Navigate = (event: MouseEvent<HTMLAnchorElement>, href: string) => void;

/**
 * One assistant answer: text first, then a few cards, then the links, then
 * follow-up questions. A new answer reveals in that order (quickly, and not
 * at all for reduced motion); everything is plain text with validated links.
 */
export function AssistantReplyView({ reply, labels, latest, animate, navigating, followUps, onNavigate }: {
  reply: AssistantReply; labels: AssistantLabels; latest: boolean; animate: boolean; navigating: string | null; followUps: ReactNode; onNavigate: Navigate;
}) {
  const pages = reply.actions.filter((a) => a.type === "page");
  const direct = reply.actions.filter((a) => a.type !== "page");
  // A service or project answered from the CMS already quotes its card; don't repeat it.
  const cards = reply.cards.filter((card) => card.type === "contact" || !(reply.answer.includes(card.title) && (!card.text || reply.answer.includes(card.text))));
  const more = reply.total !== undefined && reply.total > cards.length ? labels.showing(cards.length, reply.total) : null;
  const order = { "--cards": cards.length } as CSSProperties;

  // An answer in another language than the page is marked as such, so screen readers pronounce it correctly.
  return <div className={styles.reply} lang={reply.language} data-latest={latest || undefined} data-animate={animate || undefined} data-status={reply.status} style={order}>
    <p className={styles.message}>{reply.answer}</p>
    {cards.length > 0 && <ul className={styles.cards} data-single={cards.length === 1 || undefined}>
      {cards.map((card, index) => <li key={cardKey(card)} style={{ "--i": index } as CSSProperties}>
        <Card card={card} labels={labels} navigating={navigating} onNavigate={onNavigate} />
      </li>)}
    </ul>}
    {(pages.length > 0 || direct.length > 0) && <div className={styles.actions}>
      {pages.map((action, index) => <ActionLink key={actionKey(action)} action={action} primary={index === 0} note={index === 0 ? more : null} labels={labels} navigating={navigating} onNavigate={onNavigate} />)}
      {direct.map((action) => <a key={actionKey(action)} href={action.href} className={styles.secondaryAction}>{action.label}</a>)}
    </div>}
    {followUps}
  </div>;
}

function ActionLink({ action, primary, note, labels, navigating, onNavigate }: {
  action: AssistantAction; primary: boolean; note: string | null; labels: AssistantLabels; navigating: string | null; onNavigate: Navigate;
}) {
  const busy = navigating === action.href;
  return <a href={action.href} onClick={(e) => onNavigate(e, action.href)} className={primary ? styles.primaryAction : styles.secondaryAction}
    aria-busy={busy || undefined} aria-disabled={(navigating !== null && !busy) || undefined} data-busy={busy || undefined}>
    {busy ? <><span className={styles.spinner} aria-hidden="true" />{labels.opening}</> : <>{action.label}{note && <span className={styles.actionNote}>{note}</span>}<span aria-hidden="true" className={styles.arrow}>→</span></>}
  </a>;
}

function Card({ card, labels, navigating, onNavigate }: { card: AssistantCard; labels: AssistantLabels; navigating: string | null; onNavigate: Navigate }) {
  if (card.type === "contact") {
    const rows: [string, ReactNode][] = [];
    if (card.email) rows.push([labels.email, <a key="email" href={`mailto:${card.email}`}>{card.email}</a>]);
    if (card.phone) rows.push([labels.phone, <a key="phone" href={`tel:${card.phone.replace(/[^+\d]/g, "")}`}>{card.phone}</a>]);
    if (card.address.length) rows.push([labels.address, card.mapsUrl ? <a key="map" href={card.mapsUrl} target="_blank" rel="noopener noreferrer">{card.address.join(", ")}</a> : card.address.join(", ")]);
    if (card.hours) rows.push([labels.hours, card.hours]);
    return <dl className={`${styles.card} ${styles.contactCard}`}>
      {rows.map(([term, value]) => <div key={term}><dt>{term}</dt><dd>{value}</dd></div>)}
    </dl>;
  }
  const busy = card.href !== null && navigating === card.href;
  const body = <>
    {card.type === "project" && <span className={styles.cardMedia}>{card.image ? <img src={card.image} alt="" loading="lazy" decoding="async" /> : <span className={styles.cardMediaEmpty} />}</span>}
    <span className={styles.cardText}>
      {card.subtitle && <span className={styles.cardEyebrow}>{card.subtitle}</span>}
      <span className={styles.cardTitle}>{card.title}</span>
      {card.text && <span className={styles.cardBody}>{card.text}</span>}
      {busy && <span className={styles.cardOpening}><span className={styles.spinner} aria-hidden="true" />{labels.opening}</span>}
    </span>
  </>;
  return card.href
    ? <a className={styles.card} href={card.href} onClick={(e) => onNavigate(e, card.href!)} data-kind={card.type} aria-busy={busy || undefined}
        aria-label={card.subtitle ? `${card.title}, ${card.subtitle}` : card.title}>{body}</a>
    : <div className={styles.card} data-kind={card.type}>{body}</div>;
}

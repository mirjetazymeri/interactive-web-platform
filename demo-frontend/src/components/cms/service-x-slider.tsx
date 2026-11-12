"use client";

import { Children, useEffect, useRef, type ReactNode } from "react";
import { type serviceXLabels } from "@/lib/cms/service-x";
import { enhanceServiceDeck } from "./service-x-deck-motion";
import styles from "./service-x.module.css";

type Props = {
  id: string; label: string; labels: ReturnType<typeof serviceXLabels>;
  services: { id: string; name: string; number: string; displayName: string }[]; children: ReactNode;
  contact: string; nested?: boolean; variant?: "home-vertical";
};

/** Readable document flow first; a measured sticky stage enhances fitting content. */
export function ServiceXSlider({ id, label, labels, services, children, contact, nested = false, variant }: Props) {
  const root = useRef<HTMLDivElement>(null);
  const panels = Children.toArray(children);
  const serviceIds = JSON.stringify(services.map((service) => service.id));
  const Heading = nested ? "h3" : "h2";
  useEffect(() => {
    if (root.current) return enhanceServiceDeck(root.current);
  }, [serviceIds, variant]);
  return <div ref={root} className={styles.deck} data-service-deck data-deck-variant={variant}>
    <div className={styles.deckStage} data-deck-stage tabIndex={-1} role="region" aria-label={labels.services}>
      {label && <p className={`${styles.label} ${styles.deckHeading}`} data-deck-heading>{label}</p>}
      <div className={styles.deckTrack} data-deck-track>
        {services.map((service, index) => <article key={service.id} id={`${id}-${service.id}`} className={styles.deckCard} data-service-card aria-label={service.name} style={{ zIndex: index + 1 }}>
          <div className={styles.deckSurface} data-deck-surface>
            <header className={styles.cardIdentity}>
              <Heading className={styles.cardName} aria-label={service.name}>
                <span className={styles.cardNumber} aria-hidden="true">{service.number}</span>
                <span aria-hidden="true">{service.displayName}</span>
              </Heading>
              <div className={styles.cardMeta}>
                <a className={styles.cardAction} href={contact} aria-label={`${labels.discuss} — ${service.name}`}>{labels.discuss}<span aria-hidden="true">↗</span></a>
              </div>
            </header>
            {panels[index]}
          </div>
        </article>)}
      </div>
    </div>
  </div>;
}

import { useId } from "react";
import { impactServicesFor, wheelLabels } from "./impact-services";
import { wheelNodePosition } from "./impact-motion";
import styles from "./impact-widget.module.css";

/** One set of details serves both the pinned experience and the static fallback. */
export function ServicesWheel({ locale }: { locale: string }) {
  const id = useId();
  const services = impactServicesFor(locale);
  const labels = wheelLabels(locale);
  return <section className={styles.wheel} data-wheel aria-label={labels.services}>
    <div className={styles.wheelHeader}>
      <span>{labels.expertise}</span>
      <span className={styles.counter} aria-hidden="true"><span data-wheel-count>01</span><span> / {String(services.length).padStart(2, "0")}</span></span>
    </div>
    <div className={styles.wheelVisual} data-wheel-visual>
      <div className={styles.ringViewport} aria-hidden="true">
        <div className={styles.rings} />
      </div>
      <div className={styles.orbit} data-wheel-orbit>
        {services.map((service, index) => <div className={styles.nodePosition} style={wheelNodePosition(index, services.length)} data-wheel-position={index} key={service.number}>
          <div className={styles.cabin}>
            <button type="button" className={styles.node} data-wheel-select={index} aria-controls={`${id}-${index}`} aria-label={labels.show(service.title)}>
              <span className={styles.nodeNumber} aria-hidden="true">{service.number}</span>
            </button>
          </div>
        </div>)}
      </div>
      <span className={styles.marker} data-wheel-marker aria-hidden="true"><span /></span>
    </div>
    <div className={styles.details}>
      {services.map((service, index) => <article className={styles.detail} data-wheel-detail={index} data-service-name={service.title} id={`${id}-${index}`} key={service.number}>
        <h3><span>{service.number}</span> {service.title}</h3>
        <ul>{service.offerings.map((offering) => <li key={offering}>{offering}</li>)}</ul>
        {/* Shown only when the page's ServiceX section has this service; it jumps to that card. */}
        <a className={styles.detailLink} data-wheel-service-link hidden href="#">{labels.full}<span aria-hidden="true">↓</span></a>
      </article>)}
    </div>
    <div className={styles.wheelFooter} data-wheel-controls>
      <span className={styles.hint} data-wheel-hint aria-hidden="true">{labels.hint} <span>↓</span></span>
      <div className={styles.controls}>
        <button type="button" data-wheel-previous aria-label={labels.previous}><span aria-hidden="true">←</span></button>
        <button type="button" data-wheel-next aria-label={labels.next}><span aria-hidden="true">→</span></button>
      </div>
    </div>
  </section>;
}

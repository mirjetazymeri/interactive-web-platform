import type { Widget } from "@/lib/cms/schema";
import { bool } from "@/lib/cms/config";
import { pageHref, safeUrl } from "@/lib/cms/links";
import { serviceAnchor, serviceNameParts, serviceNumber, serviceXConfigSchema, serviceXLabels, serviceXMode, type Service } from "@/lib/cms/service-x";
import { ServiceXSlider } from "./service-x-slider";
import { ServiceXImage } from "./service-x-image";
import { ServiceXNavigation } from "./service-x-navigation";
import { ServiceXExperience } from "./service-x-experience";
import styles from "./service-x.module.css";

/** The contact page reads `topic` to start the visitor's message; it is display text only, never trusted. */
function contactAbout(contact: string, topic: string) {
  return `${contact}?${new URLSearchParams({ topic })}`;
}

/** `named`: the slider card already owns the service heading, so the CMS title is supporting copy. */
function ServiceContent({ service, nested, named = false, help }: { service: Service; nested: boolean; named?: boolean; help?: string }) {
  const Title = named ? "p" : nested ? "h3" : "h2";
  const Subheading = nested ? "h4" : "h3";
  const image = safeUrl(service.image, true);
  return <>
    {service.title && <Title className={styles.title}>{service.title}</Title>}
    {(service.subtitle || image) && <div className={`${styles.mainContent} ${service.title ? styles.divided : ""}`}>
      {service.subtitle && <p className={styles.subtitle}>{service.subtitle}</p>}
      {image && <ServiceXImage key={image} src={image} alt={service.image_alt} />}
    </div>}
    {service.subservices.length > 0 && <div className={styles.offerings}>
      {help && <p className={styles.offeringsLabel}>{help}</p>}
      <ul className={styles.subservices}>
        {service.subservices.map((subservice, index) => <li key={subservice.id}>
          <span className={styles.offeringNumber} aria-hidden="true">{serviceNumber(index)}</span>
          {subservice.title && <Subheading className={styles.subheading}>{subservice.title}</Subheading>}
          {subservice.description && <p className={styles.description}>{subservice.description}</p>}
        </li>)}
      </ul>
    </div>}
  </>;
}

export function ServiceX({ widget, locale, homeTransition = false }: { widget: Widget; locale: string; homeTransition?: boolean }) {
  const parsed = serviceXConfigSchema.safeParse(widget.config);
  if (!parsed.success) {
    console.error(`[CMS] Invalid ServiceX content for widget ${widget.id}`);
    return null;
  }
  const { services, section_label, section_index } = parsed.data;
  if (!services.length) return null;
  const mode = serviceXMode(widget);
  const id = serviceAnchor(widget);
  const showTitle = bool(widget.config, "show_title") && !!widget.title;
  const label = [section_index, section_label].filter(Boolean).join(" — ");
  const labels = serviceXLabels(locale);
  const contact = pageHref(locale, "contact");
  return <section className={styles.section} data-widget-id={widget.id} data-placement-id={widget.placement_id} data-service-x={mode} aria-label={widget.title || section_label || labels.services}>
    <div className={`container ${styles.container}`}>
      {showTitle && mode !== "stacked" && <h2 className={styles.widgetTitle}>{widget.title}</h2>}
      {mode === "stacked" ? <><div className={styles.layout}>
        <ServiceXNavigation label={labels.navigation} caption={labels.services} items={services.map((service, index) => ({ id: `${id}-${encodeURIComponent(service.id)}`, name: serviceNameParts(service.name, index).name }))} />
        <ServiceXExperience>
        {services.map((service, index) => {
          const anchor = `${id}-${encodeURIComponent(service.id)}`;
          const image = safeUrl(service.image, true);
          const name = serviceNameParts(service.name, index).name;
          // One calm reading column per service: name and promise, introduction, every solution, one action.
          return <article className={styles.service} data-service-chapter key={service.id} id={anchor} aria-labelledby={`${anchor}-heading`}>
            <header className={styles.serviceHeader} data-chapter-identity data-chapter-entry>
              <h2 id={`${anchor}-heading`}>{name}</h2>
              {service.title && <p className={styles.valueProposition}>{service.title}</p>}
              {service.subtitle && <p className={styles.subtitle}>{service.subtitle}</p>}
            </header>
            {image && <ServiceXImage key={image} src={image} alt={service.image_alt} className={styles.serviceImage} />}
            {service.subservices.length > 0 && <ul className={styles.solutionGrid} data-service-solutions aria-label={`${labels.help} — ${name}`}>
              {service.subservices.map((subservice) => <li className={styles.solutionCard} data-solution-row key={subservice.id} id={`${anchor}-${encodeURIComponent(subservice.id)}`}>
                <h3 className={styles.solutionTitle}>{subservice.title}</h3>
                {subservice.description && <p className={styles.description}>{subservice.description}</p>}
                <a className={styles.solutionAction} href={contactAbout(contact, `${name} — ${subservice.title}`)} aria-label={`${labels.ask} — ${subservice.title}`}>{labels.ask}<span aria-hidden="true">↗</span></a>
              </li>)}
            </ul>}
            <a className={`button ${styles.serviceAction}`} href={contactAbout(contact, name)} aria-label={`${labels.discuss} — ${name}`}>{labels.discuss}<span aria-hidden="true">↗</span></a>
          </article>;
        })}
        </ServiceXExperience>
      </div>
        <aside className={styles.contactEnd}><p className={styles.contactPrompt}>{labels.unsure}</p><a className="button" href={contact}>{labels.contact}<span aria-hidden="true">↗</span></a></aside>
      </> : services.length === 1 ? <>
        <div className={styles.masthead}>{label && <p className={styles.label}>{label}</p>}{services[0].name && <p className={styles.currentName}>{services[0].name}</p>}</div>
        <article><ServiceContent service={services[0]} nested={showTitle} /></article>
      </> : <ServiceXSlider id={id} label={label} labels={labels} contact={contact} nested={showTitle} variant={homeTransition ? "home-vertical" : undefined} services={services.map(({ id, name }, index) => {
        const parts = serviceNameParts(name, index);
        return { id, name, number: parts.number, displayName: parts.name };
      })}>
        {services.map((service) => <ServiceContent key={service.id} service={service} nested={showTitle} named help={labels.help} />)}
      </ServiceXSlider>}
    </div>
  </section>;
}

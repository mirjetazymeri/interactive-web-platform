/** Presentation content supplied for this experience; never replaces CMS HTML.
 * No service catalog or service URLs are supplied by the current HTML widget API.
 * Titles mirror the ServiceX names (web-admin ServiceCatalogSeeder) so a wheel entry
 * links to its card.
 */
export const impactServices = [
  { number: "01", title: "SOFTWARE DEVELOPMENT", offerings: ["Web Development", "Mobile App Development", "Custom Software & SaaS", "UI/UX & Product Design"] },
  { number: "02", title: "AI & AUTOMATION", offerings: ["AI Assistants & Chatbots", "AI Agents & Automation", "AI Integration & Data Solutions"] },
  { number: "03", title: "E-COMMERCE SOLUTIONS", offerings: ["Online Stores & Marketplaces", "E-commerce Integrations", "E-commerce Automation & Optimization"] },
  { number: "04", title: "CREATIVE & 3D EXPERIENCES", offerings: ["Interactive Websites & Animations", "3D Product Experiences", "AR/VR Experiences"] },
  { number: "05", title: "CLOUD & ICT INFRASTRUCTURE", offerings: ["Cloud & Hosting Solutions", "ICT & Network Solutions", "Backup, Security & Recovery"] },
  { number: "06", title: "BUSINESS DIGITALIZATION", offerings: ["ERP & CRM Solutions", "Business Management Systems", "Digital Transformation & Integration"] },
  { number: "07", title: "DIGITAL MARKETING & GROWTH", offerings: ["SEO & AI Search Optimization", "Social Media & Content Marketing", "Paid Advertising & Growth"] },
  { number: "08", title: "CUSTOM TECHNOLOGY SOLUTIONS", offerings: ["Custom Integrations & APIs", "Enterprise & Legacy Modernization", "Technical Consulting & Innovation"] },
] as const;

/** Albanian mirrors the seeder's `sq` translations, in the same order. */
const impactServicesSq = [
  { number: "01", title: "ZHVILLIM SOFTUERËSH", offerings: ["Zhvillim Web", "Zhvillim Aplikacionesh Mobile", "Softuer i Personalizuar & SaaS", "Dizajn UI/UX & Produkti"] },
  { number: "02", title: "AI & AUTOMATIZIM", offerings: ["Asistentë AI & Chatbotë", "Agjentë AI & Automatizim", "Integrim AI & Zgjidhje me të Dhëna"] },
  { number: "03", title: "ZGJIDHJE E-COMMERCE", offerings: ["Dyqane Online & Tregje Digjitale", "Integrime E-commerce", "Automatizim & Optimizim E-commerce"] },
  { number: "04", title: "PËRVOJA KREATIVE & 3D", offerings: ["Faqe Interaktive & Animacione", "Përvoja 3D të Produkteve", "Përvoja AR/VR"] },
  { number: "05", title: "CLOUD & INFRASTRUKTURË ICT", offerings: ["Zgjidhje Cloud & Hosting", "Zgjidhje ICT & Rrjeti", "Backup, Siguri & Rikuperim"] },
  { number: "06", title: "DIGJITALIZIM I BIZNESIT", offerings: ["Zgjidhje ERP & CRM", "Sisteme Menaxhimi të Biznesit", "Transformim Digjital & Integrim"] },
  { number: "07", title: "MARKETING DIGJITAL & RRITJE", offerings: ["SEO & Optimizim për Kërkimin me AI", "Marketing në Rrjete Sociale & Përmbajtje", "Reklamim me Pagesë & Rritje"] },
  { number: "08", title: "ZGJIDHJE TEKNOLOGJIKE ME POROSI", offerings: ["Integrime & API të Personalizuara", "Modernizim i Sistemeve Enterprise & Legacy", "Konsulencë Teknike & Inovacion"] },
] as const;

export type ImpactService = { number: string; title: string; offerings: readonly string[] };
export const impactServicesFor = (locale: string): readonly ImpactService[] => locale === "sq" ? impactServicesSq : impactServices;

export function wheelLabels(locale: string) {
  if (locale === "sq") return { services: "Shërbimet tona", expertise: "Ekspertiza jonë", show: (title: string) => `Shfaq shërbimet: ${title.toLowerCase()}`, full: "Shiko shërbimin e plotë", hint: "Lëvizni për të eksploruar", previous: "Shërbimi i mëparshëm", next: "Shërbimi tjetër" };
  return { services: "Our services", expertise: "Our expertise", show: (title: string) => `Show ${title.toLowerCase()} services`, full: "View the full service", hint: "Scroll to explore", previous: "Previous service", next: "Next service" };
}

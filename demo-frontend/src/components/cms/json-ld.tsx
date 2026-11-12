import { jsonLd } from "@/lib/cms/structured-data";

/** The page's single schema.org graph. A native script tag: structured data is not executable code. */
export function JsonLd({ graph }: { graph: Record<string, unknown>[] }) {
  return graph.length ? <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: jsonLd(graph) }} /> : null;
}

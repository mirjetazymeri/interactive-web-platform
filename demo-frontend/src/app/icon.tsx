import { siteIcon } from "@/lib/cms/site-icon";

// Follows the favicon in the CMS settings without a rebuild.
export const dynamic = "force-dynamic";
export const size = { width: 64, height: 64 };
export const contentType = "image/png";

/** Browser tab icon: the CMS favicon, squared without stretching. */
export default function Icon() {
  return siteIcon(size.width);
}

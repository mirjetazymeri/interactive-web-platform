import { siteIcon } from "@/lib/cms/site-icon";

// Follows the favicon in the CMS settings without a rebuild.
export const dynamic = "force-dynamic";
export const size = { width: 180, height: 180 };
export const contentType = "image/png";

/** iPhone/iPad home-screen icon: the CMS favicon on the site background, with breathing room. */
export default function AppleIcon() {
  return siteIcon(size.width, { padding: 28, solid: true });
}

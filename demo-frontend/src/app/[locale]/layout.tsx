import "../globals.css";
import { preload } from "react-dom";
import { getConfig, getLocales, getSettings } from "@/lib/cms/api";
import { themeVariables } from "@/lib/cms/theme";
import { cmsHref } from "@/lib/cms/links";
import { fontFaceCss, fontPreloads, parseTypography } from "@/lib/cms/typography";
import { trackingConfig } from "@/lib/tracking/config";
import { TrackingProvider } from "@/components/tracking/tracking-provider";
// Locale validation happens in blocking page metadata before the response starts.
export default async function LocaleLayout({ children, params }: { children: React.ReactNode; params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  const [settings, locales] = await Promise.all([getSettings(locale), getLocales()]);
  // Only the two admin-selected families are declared; the browser fetches just the weights in use.
  const typography = parseTypography(settings.theme?.typography);
  for (const href of fontPreloads(typography)) preload(href, { as: "font", type: "font/woff2", crossOrigin: "anonymous" });
  // Analytics only on the production website, when configured and switched on (null otherwise: nothing is rendered).
  const context = { locale, locales: locales.locales.map((l) => l.code), backendOrigin: getConfig().origin };
  const tracking = trackingConfig(settings, locale, {
    privacyHref: cmsHref(settings.gdpr?.privacy_policy_url, context), cookieHref: cmsHref(settings.gdpr?.cookie_policy_url, context),
  });
  return (
    <html lang={locale} style={themeVariables(settings.theme)}>
      <head><style dangerouslySetInnerHTML={{ __html: fontFaceCss(typography) }} /></head>
      <body>{children}{tracking && <TrackingProvider config={tracking} />}</body>
    </html>
  );
}

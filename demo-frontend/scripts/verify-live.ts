import assert from "node:assert/strict";
import {
  apiConfig,
  parseEnvelope,
  requestCms,
} from "../src/lib/cms/transport";
import {
  localesSchema,
  menusSchema,
  pageSchema,
  settingsSchema,
} from "../src/lib/cms/schema";

// Read-only smoke test.
// Use a local or demo CMS containing synthetic data.
const config = apiConfig(process.env);

const frontend = (
  process.env.VERIFY_FRONTEND_URL || "http://localhost:3000"
).replace(/\/+$/, "");

const apiBaseUrl = process.env.API_BASE_URL;
const backendOrigin = apiBaseUrl
  ? new URL(apiBaseUrl).origin
  : null;

function escapeHtml(value: string): string {
  return value
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#39;");
}

function assertNoBackendStorefrontLinks(html: string): void {
  if (!backendOrigin) return;

  for (const match of html.matchAll(/href=["']([^"']+)["']/gi)) {
    const href = match[1].replaceAll("&amp;", "&");

    let target: URL;

    try {
      target = new URL(href, frontend);
    } catch {
      continue;
    }

    assert.ok(
      !(
        target.origin === backendOrigin &&
        /^\/storefront(?:\/|$)/.test(target.pathname)
      ),
      "A menu link points to the backend storefront URL",
    );
  }
}

async function main() {
  const locales = parseEnvelope(
    await requestCms(config, "locales"),
    localesSchema,
  );

  const root = await fetch(frontend, {
    redirect: "manual",
  });

  assert.equal(
    root.status,
    307,
    "The frontend root must redirect to the default locale",
  );

  const location = root.headers.get("location");

  assert.ok(location, "The locale redirect is missing");

  assert.equal(
    new URL(location, frontend).pathname,
    `/${locales.default}`,
    "The root must redirect to the default locale",
  );

  for (const locale of locales.locales) {
    const resolved = parseEnvelope(
      await requestCms(config, "locales", locale.code),
      localesSchema,
    );

    if (resolved.current !== locale.code) {
      console.warn(
        "BACKEND LOCALE MISMATCH: requested locale was not resolved",
      );
    }

    parseEnvelope(
      await requestCms(config, "settings", locale.code),
      settingsSchema,
    );

    const menus = parseEnvelope(
      await requestCms(config, "menus", locale.code),
      menusSchema,
    );

    const paths = new Set([
      "home",
      ...menus.header.map((menu) => menu.path),
      ...menus.footer.map((menu) => menu.path),
    ]);

    for (const path of paths) {
      const page = parseEnvelope(
        await requestCms(
          config,
          `pages/${path}?include_widgets=1`,
          locale.code,
        ),
        pageSchema,
      );

      const url =
        `${frontend}/${locale.code}` +
        (path === "home" ? "" : `/${path}`);

      const response = await fetch(url);
      const html = await response.text();

      assert.equal(
        response.status,
        200,
        "A CMS page did not return HTTP 200",
      );

      assert.ok(
        html.includes(`<html lang="${locale.code}"`),
        "The page language does not match the requested locale",
      );

      assert.ok(
        html.includes(escapeHtml(page.title)),
        "The rendered page is missing the CMS title",
      );

      assertNoBackendStorefrontLinks(html);

      assert.ok(
        !/<iframe\b/i.test(html),
        "An iframe was rendered before consent",
      );

      // Avoid printing configured hosts or CMS paths.
      console.log("PASS localized CMS page");
    }
  }

  const missingPaths = [
    "/zz",
    `/${locales.default}/missing-cms-page`,
    `/${locales.default}/about/team`,
  ];

  for (const path of missingPaths) {
    const response = await fetch(`${frontend}${path}`);

    assert.equal(
      response.status,
      404,
      "An expected missing page did not return HTTP 404",
    );

    console.log("PASS expected HTTP 404");
  }
}

main().catch(() => {
  // Avoid printing API response bodies or environment details.
  console.error(
    "Smoke test failed. Check the local demo configuration and test assertions.",
  );
  process.exitCode = 1;
});

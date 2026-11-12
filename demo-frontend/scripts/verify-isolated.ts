import assert from "node:assert/strict";
import { createServer } from "node:http";
import { spawn } from "node:child_process";
import { createReadStream, readFileSync, statSync } from "node:fs";
import { setTimeout as delay } from "node:timers/promises";

// Test fixtures must contain synthetic data only.
// This script does not write to Laravel or a database.
const fixture = (name: string) =>
  JSON.parse(
    readFileSync(
      new URL(`../tests/fixtures/${name}.json`, import.meta.url),
      "utf8",
    ),
  );

const DEMO_NAME = "Demo Project";
const DEMO_NAME_SQ = "Demo Project SQ";
const MOCK_ORIGIN = "http://127.0.0.1:4010";
const SITE_ORIGIN = "http://127.0.0.1:3002";

let mode = "ok";
let homeLayout = "hero";
let theme = {
  primary_color: "#050505",
  secondary_color: "#000480",
  accent_color: "#fea34d",
};

const mock = createServer((request, response) => {
  assert.equal(
    request.method,
    "GET",
    "Verification must never submit forms",
  );

  const url = new URL(request.url!, MOCK_ORIGIN);

  if (url.pathname === "/missing") {
    response.writeHead(404);
    response.end();
    return;
  }

  // Optional local test video. No media is copied into the project.
  if (url.pathname === "/hero-preview.mp4") {
    const file = process.env.VERIFY_VIDEO_FILE;

    if (!file) {
      response.writeHead(404);
      response.end();
      return;
    }

    const size = statSync(file).size;
    const range = /^bytes=(\d+)-(\d*)$/.exec(
      request.headers.range ?? "",
    );
    const start = range ? Number(range[1]) : 0;
    const end = range?.[2]
      ? Math.min(Number(range[2]), size - 1)
      : size - 1;

    if (start > end || start >= size) {
      response.writeHead(416, {
        "Content-Range": `bytes */${size}`,
      });
      response.end();
      return;
    }

    response.writeHead(range ? 206 : 200, {
      "Content-Type": "video/mp4",
      "Accept-Ranges": "bytes",
      "Content-Length": end - start + 1,
      ...(range
        ? { "Content-Range": `bytes ${start}-${end}/${size}` }
        : {}),
    });

    createReadStream(file, { start, end }).pipe(response);
    return;
  }

  assert.equal(request.headers["x-channel"], "default");
  response.setHeader("Content-Type", "application/json");

  let data;

  if (url.pathname === "/api/v1/locales") {
    data = fixture("locales");
  } else if (url.pathname === "/api/v1/settings") {
    data = fixture("settings");
    data.data.theme = theme;
  } else if (url.pathname === "/api/v1/menus") {
    data = fixture("menus");
  } else if (url.pathname.startsWith("/api/v1/pages/")) {
    if (mode === "failure") {
      response.writeHead(503);
      response.end('{"message":"Unavailable"}');
      return;
    }

    if (mode === "invalid") {
      response.end(
        '{"data":{"title":"Missing required fields"}}',
      );
      return;
    }

    const path = url.pathname.slice("/api/v1/pages/".length);
    const allowedPaths = [
      "home",
      "about",
      "about/team",
      "html/impact",
      "hero/legacy",
      "hero/image",
      "hero/video",
      "hero/none",
      "hero/broken",
      "hero/brand",
      "hero/long",
      "hero/unicode",
      "hero/empty",
      "hero/globe",
      "hero/world",
      "hero/world-seo",
      "hero/world-many",
      "hero/world-empty",
      "hero/world-auto",
      ...[1, 2, 3, 5, 8, 12].map(
        (count) => `hero/world-${count}`,
      ),
    ];

    if (!allowedPaths.includes(path)) {
      response.writeHead(404);
      response.end('{"message":"Not found"}');
      return;
    }

    data = fixture(
      path === "home" ? "pages-home" : "pages-about",
    );

    if (path === "html/impact") {
      data.data = {
        ...data.data,
        path,
        slug: "impact",
        content: null,
        widgets: {
          content: [fixture("impact-widget")],
        },
      };
    }

    if (path === "home" && homeLayout !== "hero") {
      const hero = data.data.widgets.hero[0];
      delete data.data.widgets.hero;

      const slot =
        homeLayout === "devices" &&
        url.searchParams.get("device") === "mobile"
          ? "main"
          : "content";

      if (homeLayout === "devices" && slot === "main") {
        hero.id = 1001;
        hero.config.headline = "Mobile homepage headline";
      }

      hero.slot = slot;

      if (homeLayout !== "missing") {
        data.data.widgets[slot] = [
          hero,
          ...(data.data.widgets[slot] ?? []),
        ];
      }
    }

    if (path.startsWith("hero/")) {
      const hero = fixture("pages-home").data.widgets.hero[0];

      if (path === "hero/brand") {
        hero.config.headline = DEMO_NAME;
      }

      if (path === "hero/long") {
        hero.config.headline =
          "Technology that connects people across every possibility";
      }

      if (path === "hero/unicode") {
        hero.config.headline = "Mirë se vini 👩‍💻 e\u0301";
      }

      if (path === "hero/empty") {
        hero.config.headline = DEMO_NAME;
        hero.config.subtitle = "";
        hero.config.cta_text = "";
      }

      // Globe labels take precedence over configured background media.
      if (path === "hero/globe") {
        hero.config.headline = DEMO_NAME;
        hero.config.rotating_labels = [
          "Your Project Manager",
          "Your Data Engineer",
          "Your Software Developer",
          "Your Cloud Architect",
        ];
      }

      if (path !== "hero/legacy") {
        hero.config.media =
          path === "hero/none"
            ? null
            : {
                type:
                  path === "hero/image" ? "image" : "video",
                url:
                  path === "hero/image"
                    ? hero.config.bg_image
                    : `${MOCK_ORIGIN}/${
                        path === "hero/broken"
                          ? "missing"
                          : "hero-preview.mp4"
                      }?signature=kept%2Bintact`,
                mime_type:
                  path === "hero/image"
                    ? "image/jpeg"
                    : "video/mp4",
                size: null,
              };
      }

      // Retain legacy data to verify media precedence.
      data.data = {
        ...data.data,
        path,
        slug: path.split("/").at(-1),
        title: "Hero verification",
        content: null,
        widgets: { hero: [hero] },
      };

      if (path.startsWith("hero/world")) {
        const sq = request.headers["x-locale"] === "sq";

        const services = [
          {
            id: "data",
            title: sq
              ? "Inxhinieri juaj i të dhënave"
              : "Your Data Engineer",
            description: sq
              ? "Tubacione dhe depo të dhënash"
              : "Pipelines and warehouses",
            icon: "data",
            lat: 51.51,
            lng: -0.13,
          },
          {
            id: "cloud",
            title: sq
              ? "Arkitekti juaj i Cloud"
              : "Your Cloud Architect",
            description: "",
            icon: "cloud",
            lat: 42.66,
            lng: 21.17,
          },
          {
            id: "pm",
            title: sq
              ? "Menaxheri juaj i projektit"
              : "Your Project Manager",
            description: "Delivery you can see",
            icon: "project",
            lat: null,
            lng: null,
          },
          {
            id: "ai",
            title: "AI Solutions",
            description: "",
            icon: "ai",
            lat: 25.2,
            lng: 55.27,
          },
          {
            id: "web",
            title: "Web Development",
            description: "",
            icon: "code",
            lat: 40.71,
            lng: -74.01,
          },
          {
            id: "infra",
            title: "Cloud Infrastructure",
            description: "",
            icon: "security",
            lat: 1.35,
            lng: 103.82,
          },
        ];

        // Sample services without explicit coordinates.
        const auto = [
          "Your backup solution",
          "Your designer",
          "Your developer",
        ].map((title, index) => ({
          id: `svc-auto-${index}`,
          title,
          description: "",
          icon: null,
          lat: null,
          lng: null,
        }));

        const names = [
          "Your Data Engineer",
          "Your Cloud Architect",
          "Your Project Manager",
          "AI Solutions",
          "Web Development",
          "Cloud Infrastructure",
          "Your Designer",
          "Your Developer",
          "Backup Solutions",
          "Mobile Apps",
          "Cyber Security",
          "Analytics",
        ];

        const icons = [
          "data",
          "cloud",
          "project",
          "ai",
          "code",
          "security",
        ];

        const sized = (count: number) =>
          names.slice(0, count).map((title, index) => ({
            id: `svc-n-${index}`,
            title,
            description: "",
            icon: icons[index % icons.length],
            lat: null,
            lng: null,
          }));

        const many = Array.from(
          { length: 40 },
          (_, index) => ({
            id: `svc-${index}`,
            title: `Service number ${index + 1}`,
            description: "",
            icon: "dot",
            lat: null,
            lng: null,
          }),
        );

        const countMatch = /^hero\/world-(\d+)$/.exec(path);

        const world = {
          id: 77,
          type: "hero-world",
          widget_key: "hero-world.1x1",
          title: "Homepage globe",
          slot: "hero",
          position: 0,
          config: {
            headline: sq ? DEMO_NAME_SQ : DEMO_NAME,
            subtitle: sq
              ? "Ekipi juaj inxhinierik në distancë."
              : "Your remote engineering team: architects, engineers and managers on demand.",
            cta_text:
              path === "hero/world-empty"
                ? ""
                : sq
                  ? "Fillo një projekt"
                  : "Start a project",
            cta_link: "/contact",
            seo_title: "Hero World SEO title",
            seo_description: "Hero World SEO description",
            services:
              path === "hero/world-empty"
                ? []
                : path === "hero/world-many"
                  ? [...services, ...many]
                  : path === "hero/world-auto"
                    ? auto
                    : countMatch
                      ? sized(Number(countMatch[1]))
                      : services,
          },
        };

        if (path === "hero/world-auto") {
          world.config.cta_text = "Explore services";
        }

        data.data = {
          ...data.data,
          title: "World verification",
          meta:
            path === "hero/world-seo"
              ? {
                  title: "Page SEO title",
                  description: "Page SEO description",
                }
              : { title: null, description: null },
          widgets: { hero: [world] },
        };
      }
    }

    if (path === "about/team") {
      data.data = {
        ...data.data,
        id: 123,
        slug: "team",
        path,
        parent_id: 2,
        title:
          request.headers["x-locale"] === "sq"
            ? "Ekipi i provës"
            : "Fixture team",
        content: "<p>Nested route verification fixture.</p>",
        breadcrumbs: [
          {
            id: 2,
            slug: "about",
            path: "about",
            title: "About Us",
          },
        ],
        widgets: {
          main: [
            {
              id: 901,
              type: "hero",
              widget_key: "hero.1x1",
              title: "White section fixture",
              slot: "main",
              position: 0,
              config: {
                bg_color: "#ffffff",
                headline: "Explicit white section",
                subtitle:
                  "Dark text on a supported white hero background.",
              },
            },
            {
              id: 902,
              type: "features",
              widget_key: "features.1x1",
              title: null,
              slot: "main",
              position: 1,
              config: {
                heading: "Transparent section",
                items: [
                  {
                    title: "Primary canvas",
                    description:
                      "No explicit background control for features.",
                  },
                ],
              },
            },
          ],
        },
      };
    }
  } else {
    response.writeHead(404);
    response.end("{}");
    return;
  }

  response.end(JSON.stringify(data));
});

const stripScripts = (html: string) =>
  html.replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi, "");

async function main() {
  await new Promise<void>((resolve) =>
    mock.listen(4010, "127.0.0.1", resolve),
  );

  const child = spawn(
    process.execPath,
    [
      "node_modules/next/dist/bin/next",
      "start",
      "--hostname",
      "127.0.0.1",
      "--port",
      "3002",
    ],
    {
      env: {
        ...process.env,
        API_BASE_URL: `${MOCK_ORIGIN}/api/v1`,
        STOREFRONT_CHANNEL: "default",
        SITE_URL: SITE_ORIGIN,
      },
      stdio: "pipe",
    },
  );

  let logs = "";
  child.stdout.on("data", (chunk) => {
    logs += chunk;
  });
  child.stderr.on("data", (chunk) => {
    logs += chunk;
  });

  try {
    let ready = false;

    for (let index = 0; index < 40; index++) {
      try {
        await fetch(`${SITE_ORIGIN}/`, {
          redirect: "manual",
        });
        ready = true;
        break;
      } catch {
        await delay(250);
      }
    }

    assert.ok(ready, logs);

    for (homeLayout of ["hero", "content", "devices"]) {
      for (const locale of ["en", "sq"]) {
        for (const suffix of ["", "/home"]) {
          const response = await fetch(
            `${SITE_ORIGIN}/${locale}${suffix}`,
          );
          assert.equal(response.status, 200);

          const html = stripScripts(await response.text());

          assert.match(html, /<title>[^<]+<\/title>/);
          assert.match(
            html,
            /data-hero-desktop="true" data-hero-mobile="true"/,
          );
          assert.match(
            html,
            /<main id="main-content"><div class="slot slot-hero"/,
          );
          assert.doesNotMatch(
            html,
            /class="page-title|class="breadcrumbs|<h1[^>]*>Home<\/h1>/,
          );
          assert.equal(
            (html.match(/data-widget-id="1"/g) ?? []).length,
            1,
            "hero is moved, not duplicated",
          );
          assert.equal(
            (html.match(/<h1\b/g) ?? []).length,
            homeLayout === "devices" ? 2 : 1,
          );
          assert.match(
            html,
            new RegExp(`href="/${locale}"`),
          );
          assert.doesNotMatch(
            html,
            /class="languages"|hrefLang=/,
            "the header has no language switch",
          );

          if (homeLayout === "devices") {
            assert.match(
              html,
              /class="desktop-only"><div class="widget widget-hero"/,
            );
            assert.match(
              html,
              /class="mobile-only"><div class="widget widget-hero"/,
            );
            assert.match(html, /Mobile homepage headline/);
          }
        }
      }

      console.log(
        `PASS homepage ${homeLayout}: localized routes, hero placement, headings and metadata`,
      );
    }

    homeLayout = "missing";

    for (const locale of ["en", "sq"]) {
      const response = await fetch(
        `${SITE_ORIGIN}/${locale}`,
      );
      assert.equal(response.status, 200);

      const html = stripScripts(await response.text());

      assert.doesNotMatch(
        html,
        /class="page-title|class="breadcrumbs|class="slot slot-hero"/,
      );
      assert.match(html, /<h1 class="sr-only">Home<\/h1>/);
      assert.match(
        html,
        /data-hero-desktop="false" data-hero-mobile="false"/,
      );
      assert.match(html, /class="widget widget-carousel"/);
    }

    console.log(
      "PASS homepage without hero: no visible title banner or fabricated hero",
    );

    for (const locale of ["en", "sq"]) {
      const response = await fetch(
        `${SITE_ORIGIN}/${locale}/html/impact`,
      );
      assert.equal(response.status, 200);

      const html = stripScripts(await response.text());

      assert.match(
        html,
        /class="widget widget-html widget-html-custom"/,
      );
      assert.match(
        html,
        new RegExp(
          `<section id="demo-home-impact" lang="${locale}"`,
        ),
      );
      assert.match(
        html,
        /class="demo-button" href="#services"/,
      );
      assert.match(
        html,
        /:where\(\[data-cms-html="widget-4-[a-f0-9]+"\]\) #demo-home-impact/,
      );
      assert.match(html, /@media \(max-width: 767px\)/);
      assert.doesNotMatch(html, /class="prose"/);

      console.log(
        `PASS custom HTML ${locale}: demo fragment, scoped CSS and unconstrained layout`,
      );
    }

    homeLayout = "content";

    for (const variant of [
      "legacy",
      "image",
      "video",
      "none",
      "broken",
    ]) {
      const response = await fetch(
        `${SITE_ORIGIN}/en/hero/${variant}`,
      );
      assert.equal(response.status, 200);

      const html = stripScripts(await response.text());

      assert.match(
        html,
        /Launch faster with reusable page widgets/,
      );
      assert.match(html, /href="\/en\/about"/);
      assert.equal(
        (html.match(/<h1\b/g) ?? []).length,
        1,
      );
      assert.doesNotMatch(html, /class="hero-shade"/);
      assert.equal(
        (html.match(/class="hero-video-overlay"/g) ?? [])
          .length,
        variant === "none" ? 0 : 1,
        "theme contrast protection applies to image and video backgrounds",
      );
      assert.match(
        html,
        /data-hero-desktop="true" data-hero-mobile="true"/,
      );
      assert.match(
        html,
        /class="sr-only">Launch faster with reusable page widgets<\/span>/,
      );

      if (variant === "legacy" || variant === "image") {
        assert.match(
          html,
          /<img[^>]*alt=""[^>]*class="hero-image"[^>]*loading="lazy"/,
        );
        assert.doesNotMatch(html, /<video/);
      } else {
        assert.doesNotMatch(html, /class="hero-image"/);

        if (variant === "none") {
          assert.doesNotMatch(
            html,
            /<video|class="hero-shade"/,
          );
        } else {
          assert.equal(
            (html.match(/<video/g) ?? []).length,
            1,
            "shared desktop/mobile placement renders once",
          );

          const tag = html.match(/<video[^>]*>/)![0];

          assert.match(tag, /muted=""/);
          assert.match(tag, /loop=""/);
          assert.match(tag, /playsInline=""/);
          assert.match(tag, /preload="none"/);
          assert.doesNotMatch(
            tag,
            /src=|autoPlay/i,
            "hydration evaluates motion and visibility before requesting media",
          );
          assert.match(html, /Play background video/);
        }
      }

      console.log(
        `PASS Hero ${variant}: markup, content and media exclusivity`,
      );
    }

    for (const variant of [
      "brand",
      "long",
      "unicode",
      "empty",
    ]) {
      const html = stripScripts(
        await (
          await fetch(
            `${SITE_ORIGIN}/en/hero/${variant}`,
          )
        ).text(),
      );

      assert.equal(
        (html.match(/<h1\b/g) ?? []).length,
        1,
      );
      assert.match(
        html,
        /class="hero-title-visual" aria-hidden="true"/,
      );

      if (variant === "empty") {
        assert.doesNotMatch(
          html,
          /class="hero-subtitle"|class="button"/,
        );
      }

      console.log(
        `PASS Hero ${variant}: accessible title and optional content`,
      );
    }

    {
      const html = stripScripts(
        await (
          await fetch(`${SITE_ORIGIN}/en/hero/globe`)
        ).text(),
      );

      assert.match(
        html,
        /<section class="hero hero-globe">/,
      );
      assert.doesNotMatch(
        html,
        /<video|class="hero-video-overlay"|class="hero-image"/,
        "globe labels replace the media background",
      );
      assert.equal(
        (html.match(/<h1\b/g) ?? []).length,
        1,
      );
      assert.match(
        html,
        /<canvas class="hero-globe-canvas" aria-hidden="true">/,
      );
      assert.match(
        html,
        /<ul class="sr-only"><li>Your Project Manager<\/li><li>Your Data Engineer<\/li><li>Your Software Developer<\/li><li>Your Cloud Architect<\/li><\/ul>/,
        "every label is readable without the animation",
      );
      assert.equal(
        (html.match(/class="hero-globe-pin"/g) ?? []).length,
        3,
        "three labels are pinned initially",
      );

      console.log(
        "PASS Hero globe: media precedence and accessible labels",
      );
    }

    for (const locale of ["en", "sq"]) {
      const html = stripScripts(
        await (
          await fetch(
            `${SITE_ORIGIN}/${locale}/hero/world`,
          )
        ).text(),
      );

      assert.match(
        html,
        /data-hero-desktop="true" data-hero-mobile="true"/,
        "Hero World is the leading hero",
      );
      assert.match(
        html,
        /class="widget widget-hero-world" data-widget-id="77"><div>/,
      );
      assert.equal(
        (html.match(/<h1\b/g) ?? []).length,
        1,
        "one primary heading",
      );
      assert.match(
        html,
        new RegExp(
          `<h1 class="hero-title"><span class="sr-only">${
            locale === "sq" ? DEMO_NAME_SQ : DEMO_NAME
          }</span>`,
        ),
      );
      assert.match(
        html,
        new RegExp(
          `class="button [^"]+" href="/${locale}/contact"[^>]*>${
            locale === "sq"
              ? "Fillo një projekt"
              : "Start a project"
          }`,
        ),
      );
      assert.match(
        html,
        /<canvas[^>]*aria-hidden="true">/,
      );
      assert.ok(
        html.indexOf("<canvas") <
          html.indexOf(
            `href="/${locale}/contact" data-world-cta`,
          ),
        "the CTA follows the globe in the markup",
      );
      assert.doesNotMatch(
        html,
        /<button[^>]*aria-pressed/,
        "no stacked service buttons",
      );
      assert.equal(
        (html.match(/data-world-pin="true"/g) ?? []).length,
        6,
        "every active service gets a pin",
      );
      assert.match(
        html,
        new RegExp(
          `<ul class="[^"]+" aria-label="${
            locale === "sq" ? "Shërbimet" : "Services"
          }">`,
        ),
      );
      assert.ok(
        html.includes(
          locale === "sq"
            ? "Inxhinieri juaj i të dhënave"
            : "Your Data Engineer",
        ),
        "translated service text is server-rendered",
      );
      assert.equal(
        (html.match(/<title>/g) ?? []).length,
        1,
      );
      assert.match(
        html,
        /<title>Hero World SEO title<\/title>/,
      );
      assert.match(
        html,
        /<meta name="description" content="Hero World SEO description"\/>/,
      );
      assert.equal(
        (html.match(/<meta name="description"/g) ?? [])
          .length,
        1,
      );
      assert.ok(
        html.includes(
          `<link rel="canonical" href="${SITE_ORIGIN}/${locale}/hero/world"`,
        ),
      );
    }

    {
      const html = await (
        await fetch(`${SITE_ORIGIN}/en/hero/world-seo`)
      ).text();

      assert.match(
        html,
        /<title>Page SEO title<\/title>/,
        "explicit page SEO wins over the widget",
      );
      assert.match(
        html,
        /<meta name="description" content="Page SEO description"\/>/,
      );

      const many = stripScripts(
        await (
          await fetch(
            `${SITE_ORIGIN}/en/hero/world-many`,
          )
        ).text(),
      );

      assert.equal(
        (many.match(/data-world-pin="true"/g) ?? []).length,
        46,
        "many services render without failing",
      );

      const empty = stripScripts(
        await (
          await fetch(
            `${SITE_ORIGIN}/en/hero/world-empty`,
          )
        ).text(),
      );

      assert.doesNotMatch(
        empty,
        /data-world-pin|aria-label="Services"/,
        "no services: no empty badges or list",
      );
      assert.doesNotMatch(
        empty,
        /class="button /,
        "no CTA without a label",
      );
      assert.match(empty, /<canvas/);

      console.log(
        "PASS Hero World: headings, CTA, accessible services, SEO and empty states",
      );
    }

    for (const locale of ["en", "sq"]) {
      const response = await fetch(
        `${SITE_ORIGIN}/${locale}/about/team`,
      );
      const html = await response.text();

      assert.equal(response.status, 200);
      assert.ok(
        html.includes(
          locale === "sq"
            ? "Ekipi i provës"
            : "Fixture team",
        ),
      );
      assert.doesNotMatch(
        html,
        /hrefLang=/,
        "no language switch links",
      );
      assert.ok(
        html.includes(`href="/${locale}/about"`),
      );
      assert.ok(
        html.includes(
          `${SITE_ORIGIN}/${locale}/about/team`,
        ),
      );
      assert.match(html, /--color-primary:#050505/);
      assert.match(html, /--color-accent:#fea34d/);
      assert.doesNotMatch(
        html,
        /background-color:#ffffff;color:#000000/,
        "legacy widget colors cannot override the shared palette",
      );
      assert.match(
        html,
        /class="widget widget-features" data-widget-id="902"><div class="container/,
      );
      assert.match(
        html,
        /href="\/[a-z]+\/about"[^>]*aria-current="location"/,
      );
      assert.match(
        html,
        /<h1 class="page-title container">/,
      );

      console.log(
        `PASS nested ${locale}: content, breadcrumb, locale path and canonical`,
      );
    }

    theme = {
      ...theme,
      primary_color: "#fff9ee",
      accent_color: "#000480",
    };

    const updated = await (
      await fetch(`${SITE_ORIGIN}/en/about/team`)
    ).text();

    assert.match(updated, /--color-primary:#fff9ee/);
    assert.match(updated, /--color-accent:#000480/);
    assert.match(updated, /--accent-foreground:#ffffff/);

    console.log(
      "PASS API theme updates the canvas and CTA without a rebuild",
    );

    theme = {
      ...theme,
      primary_color: "#050505",
      accent_color: "#fea34d",
    };

    for (const path of ["/zz", "/en/missing"]) {
      for (const userAgent of [
        "curl/8",
        "Mozilla/5.0 Chrome/130.0.0.0 Safari/537.36",
      ]) {
        const response = await fetch(
          `${SITE_ORIGIN}${path}`,
          {
            headers: { "User-Agent": userAgent },
          },
        );

        assert.equal(response.status, 404, path);
      }

      console.log(
        `PASS HTTP 404 for ${path} with browser and crawler agents`,
      );
    }

    for (const failure of ["failure", "invalid"]) {
      mode = failure;

      const response = await fetch(
        `${SITE_ORIGIN}/en/about/team`,
      );
      assert.equal(response.status, 500, failure);

      const html = stripScripts(await response.text());

      // Inspect rendered markup without embedded RSC scripts.
      assert.doesNotMatch(
        html,
        /<h1[^>]*>Page not found<\/h1>/,
      );

      console.log(
        `PASS ${failure} produces HTTP 500 instead of a false 404`,
      );
    }

    if (process.env.VERIFY_KEEP_SERVER === "1") {
      mode = "ok";

      console.log(
        `Demo preview: ${SITE_ORIGIN}/en/about/team; press Ctrl+C to stop.`,
      );

      await new Promise<void>((resolve) =>
        process.once("SIGINT", resolve),
      );
    }
  } finally {
    child.kill();
    mock.close();
  }
}

main().catch((error) => {
  console.error(error);
  mock.close();
  process.exitCode = 1;
});

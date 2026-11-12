import assert from "node:assert/strict";
import { createServer } from "node:http";
import { spawn } from "node:child_process";
import { readFileSync } from "node:fs";
import { setTimeout as delay } from "node:timers/promises";
import { serviceXFixture } from "../tests/fixtures/service-x";

// Use synthetic fixtures only. No company assets or production data.
const fixture = (name: string) =>
  JSON.parse(
    readFileSync(
      new URL(`../tests/fixtures/${name}.json`, import.meta.url),
      "utf8",
    ),
  );

const MOCK_ORIGIN = "http://127.0.0.1:4019";
const SITE_ORIGIN = "http://127.0.0.1:3019";

// Embedded 1×1 PNG used instead of a company image.
// Replace with a neutral demo asset if visual QA needs a larger image.
const previewImage = Buffer.from(
  "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aX1sAAAAASUVORK5CYII=",
  "base64",
);

const variants = [
  "horizontal_scroll",
  "vertical_scroll",
  "horizontal",
  "vertical",
  "empty",
  "single",
  "invalid",
  "home",
];

const stripScripts = (html: string) =>
  html.replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi, "");

let updated = false;

const mock = createServer((request, response) => {
  assert.equal(
    request.method,
    "GET",
    "Verification must never submit forms",
  );

  const url = new URL(request.url!, MOCK_ORIGIN);

  if (url.pathname === "/project-preview.png") {
    response.setHeader("Content-Type", "image/png");
    response.end(previewImage);
    return;
  }

  response.setHeader("Content-Type", "application/json");

  let payload;

  if (url.pathname === "/api/v1/locales") {
    payload = fixture("locales");
  } else if (url.pathname === "/api/v1/settings") {
    payload = fixture("settings");

    // Verify an alternate palette for the second test locale.
    if (request.headers["x-locale"] === "sq") {
      const theme = payload.data.theme;

      [theme.primary_color, theme.accent_color] = [
        theme.accent_color,
        theme.primary_color,
      ];
    }
  } else if (url.pathname === "/api/v1/menus") {
    payload = fixture("menus");
  } else if (url.pathname.startsWith("/api/v1/widgets/")) {
    if (/\/widgets\/\d+$/.test(url.pathname)) {
      const id = Number(url.pathname.split("/").at(-1));

      payload = {
        data: {
          id,
          type: "projects",
          config: {
            variant: "vertical_scroll",
            projects:
              id === 41
                ? [
                    {
                      id: "single",
                      widget_id: id,
                      name: "Single",
                      slug: "single",
                    },
                  ]
                : [
                    {
                      id: "owned-item",
                      widget_id: id,
                      name: "Current",
                      slug: "owned-item",
                    },
                    {
                      id: "second",
                      widget_id: id,
                      name: "Next project",
                      slug: "second",
                    },
                    {
                      id: "third",
                      widget_id: id,
                      name: "Third project",
                      slug: "third",
                    },
                  ],
          },
        },
      };

      response.end(JSON.stringify(payload));
      return;
    }

    const [, , , , widgetId, , slug] =
      url.pathname.split("/");

    if (slug === "missing") {
      response.writeHead(404);
      response.end("{}");
      return;
    }

    assert.ok(["17", "29", "41"].includes(widgetId));
    assert.ok(
      ["desktop", "mobile"].includes(
        url.searchParams.get("device")!,
      ),
    );
    assert.equal(request.headers["x-channel"], "default");

    payload = {
      success: true,
      data: {
        id: slug,
        widget_id: Number(widgetId),
        slug,
        name: `Detail ${widgetId}`,
        category: "Electronics",
        client_name: "Demo client",
        technologies: ["Next.js"],
        description:
          "First paragraph.\n\nSecond <script>escaped</script> paragraph.",
        work_delivered:
          "Design and development.\n\nPayment integration.",
        goal: "A clear and manageable system.",
        website_url: "https://example.com",
        images: [3, 1, 2, 4].map((position) => ({
          id: position,
          position,
          url: `${MOCK_ORIGIN}/project-preview.png`,
          alt: `Ordered image ${position}`,
        })),
      },
    };

    if (slug === "single") {
      Object.assign(payload.data, {
        category: null,
        client_name: null,
        technologies: [],
        website_url: null,
        work_delivered: null,
        goal: null,
        images: payload.data.images.slice(0, 1),
      });
    }

    if (slug === "long") {
      payload.data.name =
        "Një përvojë e re për tregtinë elektronike dhe shërbimet digjitale ndërkombëtare";
    }
  } else if (url.pathname.startsWith("/api/v1/pages/")) {
    const variant = url.pathname.slice(
      "/api/v1/pages/".length,
    );

    if (variant === "projects") {
      payload = fixture("pages-about");

      payload.data = {
        ...payload.data,
        path: "projects",
        slug: "projects",
        title: "Projects index",
        content: null,
        breadcrumbs: [],
        widgets: {},
      };

      response.end(JSON.stringify(payload));
      return;
    }

    if (!variants.includes(variant) && variant !== "terminal") {
      response.writeHead(404);
      response.end("{}");
      return;
    }

    const locale = String(request.headers["x-locale"]);

    const project = {
      id: "owned-item",
      name:
        locale === "sq"
          ? "Projekt test"
          : "Widget-owned project",
      slug: "owned-item",
      category: "Electronics",
      client_name: "Demo client",
      technologies: ["Shopify"],

      // Deliberate local backend URL to test frontend link rewriting.
      url: `${MOCK_ORIGIN}/storefront/projects/17/owned-item?locale=${locale}`,

      main_image: {
        url: `${MOCK_ORIGIN}/project-preview.png`,
        alt: "Test project preview",
      },
    };

    const items = [
      project,
      {
        ...project,
        id: "second",
        name: "Second project",
        category: null,
        client_name: null,
        technologies: [],
        main_image: null,
      },
      {
        ...project,
        id: "third",
        name: "Third project",
      },
    ];

    const projects = {
      id: 17,
      type: "projects",
      widget_key: "projects.1x1",
      title: "Portfolio",
      placement_id: 20,
      slot: "content",
      position: 1,
      config: {
        variant: ["empty", "single", "home", "terminal"].includes(
          variant,
        )
          ? "horizontal_scroll"
          : variant,
        projects:
          variant === "empty"
            ? []
            : ["single", "terminal"].includes(variant)
              ? [project]
              : items,
      },
    };

    payload = fixture("pages-about");

    const services = serviceXFixture();
    services.position = 0;

    services.config.services.forEach((service) => {
      service.image = null;
    });

    if (updated) {
      projects.position = 4;
      projects.config.projects.push({
        ...project,
        id: "new-item",
        slug: "new-item",
        name: "Newly published item",
      });
    }

    payload.data = {
      ...payload.data,
      path: variant,
      slug: variant,
      title: "Projects QA",
      content: null,
      breadcrumbs: [],
      widgets: {
        content: [
          projects,
          {
            ...projects,
            id: 29,
            placement_id: 29,
            position: 2,
            config: {
              ...projects.config,
              variant: "vertical_scroll",
              projects: [project],
            },
          },
          services,
          {
            ...projects,
            id: 39,
            placement_id: 39,
            position: 3,
            config: {
              ...projects.config,
              variant: "horizontal_scroll",
              projects: items,
            },
          },
        ],
      },
    };

    if (variant === "terminal") {
      payload.data.widgets.content = [services, projects];
    }
  } else {
    response.writeHead(404);
    response.end("{}");
    return;
  }

  response.end(JSON.stringify(payload));
});

async function main() {
  await new Promise<void>((resolve) =>
    mock.listen(4019, "127.0.0.1", resolve),
  );

  const child = spawn(
    process.execPath,
    [
      "node_modules/next/dist/bin/next",
      "start",
      "--hostname",
      "127.0.0.1",
      "--port",
      "3019",
    ],
    {
      env: {
        ...process.env,
        API_BASE_URL: `${MOCK_ORIGIN}/api/v1`,
        STOREFRONT_CHANNEL: "default",
        SITE_URL: SITE_ORIGIN,
        SITE_ENV: "development",
        TRACKING_DEBUG: "false",
      },
      stdio: "pipe",
    },
  );

  let logs = "";

  child.stdout.on("data", (data) => {
    logs += data;
  });

  child.stderr.on("data", (data) => {
    logs += data;
  });

  try {
    let ready = false;

    for (let index = 0; index < 60; index++) {
      try {
        await fetch(`${SITE_ORIGIN}/`);
        ready = true;
        break;
      } catch {
        await delay(250);
      }
    }

    assert.ok(ready, logs);

    for (const locale of ["en", "sq"]) {
      for (const variant of variants) {
        const response = await fetch(
          `${SITE_ORIGIN}/${locale}/${variant}`,
        );

        assert.equal(response.status, 200);

        const html = stripScripts(await response.text());

        assert.doesNotMatch(
          html,
          /Unsupported CMS widget|Invalid Projects configuration/,
        );

        if (["empty", "invalid"].includes(variant)) {
          assert.equal(
            (html.match(/data-widget-id="17"/g) ?? []).length,
            0,
          );
        } else {
          assert.match(html, /widget-projects/);
        }

        assert.match(html, /data-service-x="slider"/);

        if (!["empty", "invalid"].includes(variant)) {
          const canonical = [
            "horizontal",
            "single",
            "home",
          ].includes(variant)
            ? "horizontal_scroll"
            : variant === "vertical"
              ? "vertical_scroll"
              : variant;

          assert.match(
            html,
            new RegExp(`data-projects="${canonical}"`),
          );

          assert.match(
            html,
            new RegExp(
              locale === "sq"
                ? "Projekt test"
                : "Widget-owned project",
            ),
          );

          assert.ok(
            html.includes(
              `/${locale}/projects/17/owned-item`,
            ),
          );

          assert.ok(
            html.includes(
              `/${locale}/projects/29/owned-item`,
            ),
          );

          assert.match(
            html,
            /data-projects="vertical_scroll"/,
          );

          assert.ok(
            html.indexOf('data-widget-id="17"') <
              html.indexOf('data-widget-id="29"'),
          );

          assert.match(html, /Shopify/);

          if (variant !== "single") {
            assert.ok(
              html.indexOf('data-project-id="owned-item"') <
                html.indexOf('data-project-id="second"'),
            );

            assert.ok(
              html.indexOf('data-project-id="second"') <
                html.indexOf('data-project-id="third"'),
            );
          }

          if (canonical === "horizontal_scroll") {
            assert.match(
              html,
              new RegExp(
                locale === "sq"
                  ? "Projekti tjetër"
                  : "Next project",
              ),
            );

            assert.ok(
              html.includes(`href="/${locale}/projects"`),
            );

            assert.match(
              html,
              new RegExp(
                locale === "sq"
                  ? "Shiko të gjitha projektet"
                  : "Explore all projects",
              ),
            );
          }
        }

        console.log(
          `PASS ${locale}/${variant}: project variants, nested cards and services`,
        );
      }
    }

    for (const locale of ["en", "sq"]) {
      const response = await fetch(
        `${SITE_ORIGIN}/${locale}/projects`,
      );

      assert.equal(response.status, 200);
    }

    const terminal = await fetch(
      `${SITE_ORIGIN}/en/terminal`,
    );

    assert.equal(terminal.status, 200);

    const terminalHtml = await terminal.text();

    assert.match(terminalHtml, /Explore all projects/);
    assert.doesNotMatch(
      terminalHtml,
      /data-projects="vertical_scroll"/,
    );

    updated = true;

    const updatedHtml = await (
      await fetch(`${SITE_ORIGIN}/en/horizontal_scroll`)
    ).text();

    assert.ok(updatedHtml.includes("Newly published item"));

    assert.ok(
      updatedHtml.indexOf('data-widget-id="29"') <
        updatedHtml.indexOf('data-widget-id="17"'),
    );

    console.log(
      "PASS API update: new item and changed placement order without rebuilding",
    );

    for (const locale of ["en", "sq"]) {
      for (const id of [17, 29]) {
        const response = await fetch(
          `${SITE_ORIGIN}/${locale}/projects/${id}/owned-item`,
        );

        assert.equal(response.status, 200);

        const html = stripScripts(await response.text());

        assert.ok(html.includes(`Detail ${id}`));

        assert.ok(
          html.includes(
            "Second &lt;script&gt;escaped&lt;/script&gt; paragraph",
          ),
        );

        assert.ok(
          html.indexOf("Ordered image 1") <
            html.indexOf("Ordered image 2"),
        );

        assert.doesNotMatch(html, /Ordered image 4/);
        assert.ok(
          html.includes('href="https://example.com/"'),
        );

        assert.match(html, /Design and development/);
        assert.match(html, /A clear and manageable system/);

        assert.doesNotMatch(
          html,
          /data-project-id="owned-item"/,
        );

        assert.match(html, /data-project-id="second"/);

        assert.ok(
          html.indexOf("Ordered image 3") <
            html.indexOf('data-project-id="second"'),
        );

        const missing = await fetch(
          `${SITE_ORIGIN}/${locale}/projects/${id}/missing`,
        );

        assert.equal(missing.status, 404);

        console.log(
          `PASS detail ${locale}/${id}: parent, escaping, gallery, website and 404`,
        );
      }
    }

    for (const locale of ["en", "sq"]) {
      const response = await fetch(
        `${SITE_ORIGIN}/${locale}/projects/41/single`,
      );

      const html = stripScripts(await response.text());

      assert.doesNotMatch(
        html,
        /data-project-stage|target="_blank"|<dl|Ordered image 2|Ordered image 1/,
      );

      assert.ok(
        html.includes(`href="/${locale}/projects"`),
      );

      assert.equal(
        (html.match(/alt="Ordered image 3"/g) ?? []).length,
        2,
        "one image per device response",
      );

      console.log(
        `PASS ${locale}: single image, optional fields and showcase fallback`,
      );
    }

    // Local browser preview using synthetic API responses.
    if (process.argv.includes("--browser")) {
      updated = false;

      console.log(
        `Demo projects preview: ${SITE_ORIGIN}/en`,
      );

      await new Promise<void>((resolve) => {
        const stop = () => {
          process.removeListener("SIGINT", stop);
          process.removeListener("SIGTERM", stop);
          resolve();
        };

        process.once("SIGINT", stop);
        process.once("SIGTERM", stop);
      });
    }
  } finally {
    child.kill();
    mock.close();
  }
}

main().catch((error) => {
  // Keep detailed diagnostics local; do not publish captured logs.
  console.error(error);
  mock.close();
  process.exitCode = 1;
});

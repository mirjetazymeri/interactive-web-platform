import assert from "node:assert/strict";
import { createServer } from "node:http";
import { spawn } from "node:child_process";
import { readFileSync } from "node:fs";
import { setTimeout as delay } from "node:timers/promises";
import { serviceXFixture } from "../tests/fixtures/service-x";

// Separate mock API, never used by normal dev/start or the production data layer.
const fixture = (name: string) => JSON.parse(readFileSync(new URL(`../tests/fixtures/${name}.json`, import.meta.url), "utf8"));
const paths = ["home", "services", "service-x/empty", "service-x/single", "service-x/missing", "service-x/long", "service-x/multiple", "service-x/multiple-slider", "service-x/swapped"];
const stackedPaths = ["services", "service-x/single", "service-x/missing", "service-x/long", "service-x/multiple", "service-x/swapped"];
const palettes = {
  dark: { primary_color: "#050505", secondary_color: "#000480", accent_color: "#ff9778" },
  light: { primary_color: "#fff9ee", secondary_color: "#4d2559", accent_color: "#563177" },
  low: { primary_color: "#777777", secondary_color: "#777777", accent_color: "#777777" },
};
let theme: unknown = palettes.dark;
const mock = createServer((request, response) => {
  const url = new URL(request.url!, "http://127.0.0.1:4012");
  if (url.pathname.startsWith("/__theme/")) {
    theme = palettes[url.pathname.split("/").at(-1) as keyof typeof palettes] ?? null;
    response.writeHead(200); response.end("Isolated QA theme updated"); return;
  }
  if (url.pathname === "/service-preview.svg") {
    response.writeHead(200, { "Content-Type": "image/svg+xml" });
    response.end('<svg xmlns="http://www.w3.org/2000/svg" width="800" height="600" viewBox="0 0 800 600"><rect width="800" height="600" fill="#e2e0da"/><rect x="94" y="74" width="612" height="390" rx="16" fill="#202020"/><rect x="109" y="90" width="582" height="357" fill="#faf9f6"/><path d="M64 465h672l30 40H34z" fill="#8c8c8c"/><text x="146" y="170" font-family="Arial" font-size="24" fill="#050505">SERVICE X / TEST MEDIA</text><rect x="146" y="204" width="300" height="16" fill="#aaa"/><rect x="146" y="238" width="230" height="12" fill="#ccc"/><circle cx="575" cy="320" r="75" fill="#000480"/></svg>'); return;
  }
  response.setHeader("Content-Type", "application/json");
  if (url.pathname === "/missing.jpg") { response.writeHead(404); response.end("{}"); return; }
  assert.equal(request.method, "GET");
  assert.equal(request.headers["x-channel"], "default");
  let payload;
  if (url.pathname === "/api/v1/locales") payload = fixture("locales");
  else if (url.pathname === "/api/v1/settings") { payload = fixture("settings"); payload.data.theme = theme; }
  else if (url.pathname === "/api/v1/menus") payload = fixture("menus");
  else if (url.pathname.startsWith("/api/v1/pages/")) {
    assert.ok(["en", "sq"].includes(String(request.headers["x-locale"])));
    assert.equal(url.searchParams.get("include_widgets"), "1");
    assert.ok(["desktop", "mobile"].includes(url.searchParams.get("device")!));
    const path = url.pathname.slice("/api/v1/pages/".length);
    if (path === "contact") { response.end(JSON.stringify(fixture("pages-contact"))); return; }
    if (!paths.includes(path)) { response.writeHead(404); response.end("{}"); return; }
    payload = fixture("pages-about");
    const widget = serviceXFixture();
    widget.placement_config.display_mode = stackedPaths.includes(path) ? "stacked" : "slider";
    if (path === "services") widget.placement_id = 82;
    if (request.headers["x-locale"] === "sq") {
      widget.config.section_label = "Ekspertiza jonë";
      widget.config.services[0].name = "Zhvillimi";
      widget.config.services[0].title = "Ndërtuar rreth mënyrës suaj të punës.";
    }
    if (path.endsWith("/empty")) widget.config.services = [];
    if (path.endsWith("/single")) widget.config.services = [{ ...widget.config.services[0], subtitle: "", image: null, subservices: [] }];
    if (path.endsWith("/missing")) {
      widget.config.services[0].image = "http://127.0.0.1:4012/missing.jpg";
      widget.config.services[1].image = null;
    }
    if (path.endsWith("/long")) {
      widget.config.show_title = true;
      widget.config.services[0].title = "Long translated headings deserve enough room to tell the entire story, on every screen size.";
      widget.config.services[0].subtitle = "Long translated supporting text wraps naturally and remains readable. ".repeat(14);
      widget.config.services[1].name = "A longer translated service navigation label";
      widget.config.services[0].subservices[0].description = "This is a longer test description that explains the situation and the assistance offered without adding extra structured fields. ".repeat(8).trim();
      widget.config.services[2].name = "03 Strategy";
    }
    const widgets = [widget];
    if (path.includes("/multiple")) widgets.push({ ...widget, placement_id: 83, position: 1 });
    payload.data = { ...payload.data, path, slug: path.split("/").at(-1), title: "ServiceX verification", content: null, breadcrumbs: [], widgets: { content: widgets } };
  } else { response.writeHead(404); response.end("{}"); return; }
  response.end(JSON.stringify(payload));
});

async function main() {
  await new Promise<void>((resolve) => mock.listen(4012, "127.0.0.1", resolve));
  const child = spawn(process.execPath, ["node_modules/next/dist/bin/next", "start", "--hostname", "127.0.0.1", "--port", "3012"], {
    env: { ...process.env, API_BASE_URL: "http://127.0.0.1:4012/api/v1", STOREFRONT_CHANNEL: "default", SITE_URL: "http://127.0.0.1:3012" }, stdio: "pipe",
  });
  let logs = "";
  child.stdout.on("data", (data) => { logs += data; }); child.stderr.on("data", (data) => { logs += data; });
  try {
    let ready = false;
    for (let index = 0; index < 60; index++) {
      try { await fetch("http://127.0.0.1:3012/"); ready = true; break; } catch { await delay(250); }
    }
    assert.ok(ready, logs);
    for (const locale of ["en", "sq"]) for (const path of paths) {
      const response = await fetch(`http://127.0.0.1:3012/${locale}/${path === "home" ? "" : path}`);
      assert.equal(response.status, 200, path);
      const html = (await response.text()).replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi, "");
      if (path.endsWith("/empty")) { assert.doesNotMatch(html, /data-service-x|aria-pressed/); continue; }
      const stacked = stackedPaths.includes(path);
      if (path === "home") assert.match(html, /data-deck-variant="home-vertical"/);
      else assert.doesNotMatch(html, /data-deck-variant="home-vertical"/, "Home transition never leaks into Services or other placements");
      assert.match(html, new RegExp(`data-service-x="${stacked ? "stacked" : "slider"}"`));
      assert.match(html, new RegExp(path.endsWith("/long") ? "Long translated headings" : locale === "sq" ? "Ndërtuar rreth" : "Built around"));
      if (stacked || path.endsWith("/single")) assert.doesNotMatch(html, /aria-pressed|data-active="false"/);
      else {
        assert.match(html, /data-service-deck="true"/);
        assert.match(html, /data-deck-stage="true"/);
        assert.doesNotMatch(html, /aria-pressed|data-service-card[^>]*inert|aria-label="Next service"|aria-label="Shërbimi tjetër"/);
      }
      if (path.endsWith("/single")) assert.doesNotMatch(html, /<img[^>]+service-preview|Web &amp; applications/);
      else assert.match(html, /A clear identity/);
      if (stacked) {
        assert.match(html, /aria-current="location"/);
        assert.match(html, /href="\/en\/contact"|href="\/sq\/contact"/);
        assert.match(html, /<h2 id="service-x-/);
      }
      if (stacked && !path.endsWith("/single")) {
        assert.match(html, /data-solution-row="true"/);
        assert.doesNotMatch(html, /data-solution-trigger|data-solution-panel/, "solutions are always-visible cards, not disclosures");
        assert.match(html, path.endsWith("/long") ? /longer test description/ : /Useful, accessible experiences/, "every description is readable without interaction");
      }
      if (path === "services") assert.equal([...html.matchAll(/<h1\b/g)].length, 1);
      if (path.includes("/multiple")) {
        const ids = [...html.matchAll(/\sid="([^"]+)"/g)].map((match) => match[1]);
        assert.equal(new Set(ids).size, ids.length, "placement IDs remain unique even with equal widget positions");
      }
      console.log(`PASS ${locale}/${path}: placement mode, shared content, controls and edge-case markup`);
    }
    for (const palette of Object.values(palettes)) {
      theme = palette;
      const response = await fetch("http://127.0.0.1:3012/en/services");
      assert.equal(response.status, 200);
      const html = await response.text();
      assert.ok(html.includes(`--color-primary:${palette.primary_color}`));
      assert.ok(html.includes(`--color-accent:${palette.accent_color}`));
      assert.ok(html.includes("--text-muted:"));
    }
    theme = null;
    assert.equal((await fetch("http://127.0.0.1:3012/en/services")).status, 500, "missing theme fails explicitly, never serves a fabricated palette");
    theme = palettes.dark;
    assert.equal((await fetch("http://127.0.0.1:3012/en/contact")).status, 200);
    console.log("PASS API palette changes without rebuild, invalid-theme failure and working contact destination");
    if (process.env.VERIFY_KEEP_SERVER === "1") {
      console.log("ServiceX QA preview: http://127.0.0.1:3012/en and /en/services. Ctrl+C stops both servers.");
      await new Promise<void>((resolve) => process.once("SIGINT", resolve));
    }
  } finally { child.kill(); mock.close(); }
}
main().catch((error) => { console.error(error); mock.close(); process.exitCode = 1; });


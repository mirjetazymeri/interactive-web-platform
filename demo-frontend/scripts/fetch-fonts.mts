// Downloads the curated CMS fonts as self-hosted WOFF2 and regenerates their @font-face data.
// Run after changing FONT_FAMILIES: `node --import tsx scripts/fetch-fonts.mts`.
import { mkdirSync, rmSync, writeFileSync } from "node:fs";
import { createRequire } from "node:module";
import { FONT_FAMILIES, fontSlug } from "../src/lib/cms/typography";

const require = createRequire(import.meta.url);
const fontkit = require("next/dist/compiled/@next/font/dist/fontkit").default;
const { getFallbackMetricsFromFontFile } = require("next/dist/compiled/@next/font/dist/local/get-fallback-metrics-from-font-file");

const SUBSETS = ["latin-ext", "latin"];
const WEIGHTS = [300, 400, 500, 600, 700];
// A current Chrome UA makes Google Fonts answer with WOFF2 and unicode-range subsets.
const UA = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36";
const root = new URL("../public/fonts/", import.meta.url);

type Face = { weight: [number, number]; subset: string; unicodeRange: string; file: string };
const output: Record<string, { faces: Face[]; fallback: Record<string, string> }> = {};

for (const family of FONT_FAMILIES) {
  const slug = fontSlug(family);
  const css = await (await fetch(`https://fonts.googleapis.com/css2?family=${encodeURIComponent(family)}:wght@${WEIGHTS.join(";")}&display=swap`, { headers: { "user-agent": UA } })).text();
  const byUrl = new Map<string, Omit<Face, "file"> & { url: string }>();
  for (const [, subset, body] of css.matchAll(/\/\* ([\w-]+) \*\/\s*@font-face \{([^}]+)\}/g)) {
    if (!SUBSETS.includes(subset)) continue;
    const weight = Number(body.match(/font-weight: (\d+)/)![1]);
    const url = body.match(/src: url\((.+?\.woff2)\)/)![1];
    const unicodeRange = body.match(/unicode-range: (.+);/)![1];
    // Variable families answer every weight with one file; merge them into a range.
    const known = byUrl.get(url);
    if (known) known.weight = [Math.min(known.weight[0], weight), Math.max(known.weight[1], weight)];
    else byUrl.set(url, { url, subset, unicodeRange, weight: [weight, weight] });
  }
  if (!byUrl.size) throw new Error(`No WOFF2 faces returned for ${family}`);
  rmSync(new URL(`${slug}/`, root), { recursive: true, force: true });
  mkdirSync(new URL(`${slug}/`, root), { recursive: true });
  const faces: Face[] = [];
  let fallback: Record<string, string> | undefined;
  for (const face of byUrl.values()) {
    const [from, to] = face.weight;
    const file = `${slug}/${slug}-${from === to ? from : `${from}-${to}`}-${face.subset}.woff2`;
    const buffer = Buffer.from(await (await fetch(face.url)).arrayBuffer());
    writeFileSync(new URL(file, root), buffer);
    faces.push({ weight: face.weight, subset: face.subset, unicodeRange: face.unicodeRange, file });
    if (face.subset === "latin" && from <= 400 && to >= 400) fallback = getFallbackMetricsFromFontFile(fontkit(buffer), "sans-serif");
  }
  if (!fallback) throw new Error(`No regular latin face for ${family}`);
  output[family] = { faces, fallback };
  console.log(`${family}: ${faces.length} files`);
}

writeFileSync(new URL("../src/lib/cms/font-faces.generated.json", import.meta.url), `${JSON.stringify(output, null, 2)}\n`);

// Segment on the server and serialize the result, so hydration never depends on
// the browser's Unicode/Intl version. Keep combining marks and emoji together.
export function heroTitleParts(title: string) {
  const segmenter = new Intl.Segmenter(undefined, { granularity: "grapheme" });
  const characters = Array.from(segmenter.segment(title), ({ segment }) => segment);
  const step = characters.length > 1 ? Math.min(70, 600 / (characters.length - 1)) : 0;
  return {
    short: characters.length <= 16 && !title.includes("\n"),
    // Keep cursive shaping intact; these scripts receive a whole-title reveal.
    connected: /[\p{Script=Arabic}\p{Script=Syriac}\p{Script=Mongolian}]/u.test(title),
    count: characters.length,
    words: title.split(/(\s+)/u).filter(Boolean).map((word) => Array.from(segmenter.segment(word), ({ segment }) => segment)),
    step,
  };
}

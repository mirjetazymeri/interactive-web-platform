import "server-only";
import postcss, { type ChildNode } from "postcss";
import { themeCssValue } from "./theme-css";

// Layout/typography only. No resource loading, global at-rules, positioning or
// executable content. Rebuild the AST so unvalidated raw input is never emitted.
const properties = new Set(`
  display box-sizing width min-width max-width height min-height max-height
  margin margin-top margin-right margin-bottom margin-left margin-inline margin-block
  margin-inline-start margin-inline-end margin-block-start margin-block-end
  padding padding-top padding-right padding-bottom padding-left padding-inline padding-block
  padding-inline-start padding-inline-end padding-block-start padding-block-end
  border border-width border-style border-color border-top border-right border-bottom border-left
  border-radius border-top-left-radius border-top-right-radius border-bottom-left-radius border-bottom-right-radius
  background background-color color color-scheme box-shadow
  font font-family font-size font-weight font-style font-variant line-height letter-spacing word-spacing
  text-align text-transform text-decoration text-decoration-color text-underline-offset text-wrap
  white-space overflow-wrap word-break vertical-align
  grid-template-columns grid-template-rows grid-auto-flow grid-auto-columns grid-auto-rows
  grid-column grid-row grid-area gap row-gap column-gap
  flex flex-direction flex-wrap flex-grow flex-shrink flex-basis order
  align-items align-content align-self justify-content justify-items justify-self
  overflow overflow-x overflow-y aspect-ratio object-fit object-position
  opacity cursor content outline outline-width outline-style outline-color outline-offset
  transition transition-property transition-duration transition-timing-function transition-delay
  list-style list-style-type list-style-position border-collapse border-spacing
`.trim().split(/\s+/));

export function cleanWidgetCss(input: string, scope: string): string {
  if (!/^[a-z0-9-]+$/.test(scope)) return "";
  // The CMS CSS textarea sometimes contains a complete <style> block.
  const css = input.trim().replace(/^<style\s*>\s*([\s\S]*?)\s*<\/style\s*>$/i, "$1");
  if (!css || /[<\\\u0000-\u0008]/.test(css)) return "";
  try {
    const source = postcss.parse(css);
    source.walkComments((comment) => { comment.remove(); });
    function clean(nodes: ChildNode[]): ChildNode[] {
      return nodes.flatMap((node): ChildNode[] => {
        if (node.type === "atrule") {
          if (!["media", "supports", "container"].includes(node.name.toLowerCase()) || !node.nodes || /[{};@]/.test(node.params)) return [];
          const children = clean(node.nodes);
          return children.length ? [postcss.atRule({ name: node.name, params: node.params, nodes: children })] : [];
        }
        if (node.type !== "rule" || /[{}@&]/.test(node.selector)) return [];
        const selectors = postcss.list.comma(node.selector);
        if (!selectors.length || selectors.some((selector) => !selector.trim() || /^[>+~]/.test(selector.trim()))) return [];
        return selectors.flatMap((selector) => {
          const transparentAction = node.nodes.some((child) => child.type === "decl" && /^background(?:-color)?$/.test(child.prop) && /^(none|transparent)$/i.test(child.value));
          const declarations = node.nodes.flatMap((child) => {
            if (child.type !== "decl" || !properties.has(child.prop.toLowerCase())) return [];
            // CSS escapes are rejected above; comments were removed before checking.
            if (/[{};@]/.test(child.value) || /(?:url|image|image-set|expression|attr|var|env)\s*\(/i.test(child.value)) return [];
            return [postcss.decl({ prop: child.prop.toLowerCase(), value: themeCssValue(child.prop.toLowerCase(), child.value, selector, transparentAction), important: child.important })];
          });
          return declarations.length ? [postcss.rule({
            selector: `:where([data-cms-html="${scope}"]) ${selector}`,
            nodes: declarations,
          })] : [];
        });
      });
    }
    return postcss.root({ nodes: clean(source.nodes) }).toString();
  } catch {
    // Invalid editor input must not make the page fail to render.
    return "";
  }
}


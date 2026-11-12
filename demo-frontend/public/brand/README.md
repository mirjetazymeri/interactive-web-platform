# Navbar symbol provenance

`demo-original.png` is an unchanged, byte-for-byte copy of the configured
transparent Laravel logo (`storage/settings/kvURvUiL7w3HjqKAxkUChs40KfD1Fcfmq9t4U6gd.png`).
It is 180 × 50 RGBA pixels. The symbol occupies columns 0–66; columns 67–71 are
fully transparent, and the wordmark starts at column 72.

The navbar uses an SVG viewport `0 0 67 50` over this original image to display
only the existing symbol. No pixels, colors, proportions or alpha values are
altered, and no generic geometry or blend mode approximates the logo. The source
asset remains intact. This fixed brand asset is used only in the navbar; footer
branding and Laravel settings are unchanged.

## Projects exit silhouette

The horizontal Projects exit uses vector circle geometry measured from the alpha
components of the symbol region above. Large circle: center (17.5, 32.5), radius
17.5; medium: (45, 12), radius 12; small: (59.5, 29.5), radius 6.5. This preserves
the unequal sizes, relative positions and separate gaps of the original mark.
The circles clip a single theme-painted foreground; their original raster colors
are not copied into the interface. The source PNG remains unchanged.

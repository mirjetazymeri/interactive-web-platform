# SEO content review (CMS records to update)

## Status after the 2026-11-02 cleanup (local CMS)

Applied through the admin panel (reversible; a JSON snapshot of the records taken before the changes is kept outside the repository):
- #1, #2, #15 — the four test items in widget #20 are now **drafts**, and the placements of the test galleries #19 and #20 on the Projects page are **switched off**. Records, widgets and media are kept. Only Treforma remains public.
- #3 — Hero World subtitle replaced (EN and SQ); the SQ button label is translated. The headline, globe services and button link are unchanged.
- #4, #5, #8 (pages), #12, #13 — SEO titles and descriptions set for Home, Services, Contact, Privacy Policy, Terms, Projects and Book a meeting (EN, plus SQ where the page has Albanian content); the Albanian Services title is now "Shërbimet".
- #7 — the cookie policy link is cleared (the banner links only to the privacy policy); the legacy GDPR banner text no longer mentions shopping.
- #9 — the footer heading typo is fixed.
- #11 — Treforma's image alt text and SEO title/description set (EN and SQ). The service images already had alt text in both languages.

Still open (owner decisions):
- #6 / legal: real Privacy Policy and Terms text — see `docs/legal-drafts.md` (draft, needs legal approval).
- #8 (settings): default SEO title/description in SEO Settings are still "demo".
- #10: widget #18's English translation title is Albanian text.
- #14, #16: Albanian translations of the Projects page, the meeting booking widget and the Hero World services.
- #17, #18: brand spelling (Site name), organization description, production URL.
- The Projects page orbit gallery (#20, now titled "Projects", heading hidden) shows a published copy of Treforma (same slug as #18, so its detail page canonicalizes to `/projects/18/treforma` and the sitemap lists only #18). The draft placeholder that held the `treforma` slug in #20 now uses `project-vertical`. Add more real projects to #20 as they become available. Because Treforma's text has an Albanian translation, `/sq/projects` now counts as translated (hreflang); its page title and description are still English.
- The only in-person meeting location is "Office Tirana, Rruga e Kavajës 10" (it matches the seed data), while the footer address is in Pristina — confirm which is correct.
- The Hero "Explore services" button links to `/contact`.

Found on 2026-11-02 in the local CMS. Nothing in the tables below was changed automatically.
Proposed texts use only facts already in the CMS (service list, address, contact
details); adjust wording to your approved tone before publishing. Lengths are
guides (titles ≈ 50–60 characters, descriptions ≈ 140–160); the site name is
added to titles automatically ("… | demo").

## Must fix before going live

| # | Where in Admin | Field(s) | Current value | Problem |
| --- | --- | --- | --- | --- |
| 1 | Widgets → #20 "projects vertical ss" (placed on Projects) | 4 published items | "Project-vertical" (slug `treforma`), "test", "test 3" (slug `testime`), "test 4" (slug `adaad`) | Test projects are public, in the sitemap and on /projects; "Project-vertical" duplicates Treforma's slug. Unpublish or replace them. |
| 2 | Widgets → #20 | Widget title | "projects vertical ss" | Shown as the heading of the Projects gallery. |
| 3 | Widgets → #21 Hero World (home) | Subtitle | "Your developerYour developer…" | Placeholder text in the homepage hero (also its largest paint). |
| 4 | Pages → Home | Meta title / description (EN, SQ) | "Home" / "Default seeded homepage with reusable widgets." · "Ballina" / "Faqja kryesore e faqes demo." | Seed text in search results. |
| 5 | Pages → Contact | Meta description (EN) | "Contact page with seeded contact form widget." | Seed text. |
| 6 | Pages → Privacy Policy, Terms & Conditions | Content and meta (EN, SQ) | "This seeded privacy policy page is a placeholder…", "Seeded … page.", "Përmbledhje demo …" | Placeholder legal pages; the cookie banner links to the privacy policy. Needs real legal text. |
| 7 | SEO & Tracking → Analytics & Tracking | Cookie policy link | `/cookie-policy` | The page does not exist (404). Clear the field or create the page. |
| 8 | SEO & Tracking → SEO Settings | Default title / description | "demo" / "demo" | Not a useful snippet; used wherever a page has none. |
| 9 | Widgets → #24 Footer call to action | Heading (EN) | "…Let’s build it togetherr" | Typo. |
| 10 | Widgets → #18 "Projects-Horisontal" | English translation → title | "Përditshmëria bëhet më e lehtë" | Albanian text in the English translation. |
| 11 | Widgets → #18, #20 project items | Image alternative text | empty on every project image | The site falls back to the project name; describe what each image shows. |

## Should fix

| # | Where | Field(s) | Current value | Note |
| --- | --- | --- | --- | --- |
| 12 | Pages → Services (SQ) | Title / meta | "Sherbimet" / "Sherbimet ne demo" | Missing ë; weak description. |
| 13 | Pages → Services (base) | Meta description | "Learn more about the company behind this seeded demo site…" | Overridden by the EN translation today, still seed text. |
| 14 | Pages → Projects, Book a meeting | Meta + Albanian translation | none | No Albanian version exists, so /sq/… canonicalizes to English and is left out of hreflang/sitemap until translated. |
| 15 | Widgets → #19 "Project-vertical" (placed on Projects) | Draft item "Project-vertical" (slug `v`) | — | Empty placeholder widget; remove the placement or fill it. |
| 16 | Widgets → #21 Hero World | Albanian translation | none | The Albanian homepage hero shows English. |
| 17 | Settings → General / SEO | App/site name | "demo" | Brand is written "demo" elsewhere; set Site name in SEO Settings if that is the official spelling. |
| 18 | SEO Settings | Organization description, production URL | empty | Organization description feeds structured data; the URL is needed at launch. |
| 19 | Settings → GDPR | Cookie banner title/text (legacy) | "…improve your shopping experience…" | Only used by the Laravel storefront preview, but it is e-commerce wording. |

## Proposed texts

**SEO Settings — defaults**
- Title — EN: `demo — Software, Cloud & Digital Growth` · SQ: `demo — Softuer, Cloud dhe Rritje Dixhitale`
- Description — EN: `demo is an ICT and digital solutions company in Pristina: software and web development, AI and automation, cloud and ICT infrastructure, and digital marketing.`
  SQ: `demo është kompani e zgjidhjeve TIK dhe dixhitale në Prishtinë: zhvillim softuerësh dhe uebi, AI dhe automatizim, infrastrukturë cloud dhe TIK, si dhe marketing dixhital.`
- Organization description — EN: `ICT and digital solutions company in Pristina, Kosovo, providing software development, AI and automation, e-commerce, cloud and ICT infrastructure, business digitalization and digital marketing.`
  SQ: `Kompani e zgjidhjeve TIK dhe dixhitale në Prishtinë, Kosovë, që ofron zhvillim softuerësh, AI dhe automatizim, e-commerce, infrastrukturë cloud dhe TIK, digjitalizim biznesi dhe marketing dixhital.`

**Home** (or leave the page's SEO title empty to use the defaults above)
- Description — EN: `Software and web development, AI and automation, cloud and ICT infrastructure and digital marketing from demo in Pristina. See our services and projects.`
  SQ: `Zhvillim softuerësh dhe uebi, AI dhe automatizim, infrastrukturë cloud dhe TIK dhe marketing dixhital nga demo në Prishtinë. Shihni shërbimet dhe projektet tona.`
- Hero World subtitle (placeholder) — EN: `Software development, AI and automation, cloud and ICT infrastructure, and digital marketing.` · SQ: `Zhvillim softuerësh, AI dhe automatizim, infrastrukturë cloud dhe TIK, si dhe marketing dixhital.`

**Services**
- Title — EN: `IT Services: Software, AI, Cloud & Digital Marketing` · SQ: `Shërbime IT: Softuer, AI, Cloud dhe Marketing Dixhital`
- Description — EN: `Software and web development, AI and automation, e-commerce, 3D experiences, cloud and ICT infrastructure, business digitalization and digital marketing.`
  SQ: `Zhvillim softuerësh dhe uebi, AI dhe automatizim, e-commerce, përvoja 3D, infrastrukturë cloud dhe TIK, digjitalizim biznesi dhe marketing dixhital.`

**Projects**
- Title — EN: `Projects` · SQ: `Projektet`
- Description — EN: `Selected demo projects: platforms, e-commerce stores and digital products, with the client, scope and technologies of each.`
  SQ: `Projekte të zgjedhura të demo: platforma, dyqane online dhe produkte dixhitale, me klientin, punën dhe teknologjitë e secilit.`

**Contact**
- Title — EN: `Contact Us` · SQ: `Na kontaktoni`
- Description — EN: `Tell us about your project or book a meeting with demo in Pristina. Call +383 45 271 270 or email info@demo.com.`
  SQ: `Na tregoni për projektin tuaj ose rezervoni një takim me demo në Prishtinë. Telefononi +383 45 271 270 ose shkruani në info@demo.com.`

**Book a meeting**
- Title — EN: `Book a Meeting` · SQ: `Rezervoni një takim`
- Description — EN: `Choose a time to meet demo online or at our office in Pristina and discuss your project.`
  SQ: `Zgjidhni një orar për t’u takuar me demo online ose në zyrën tonë në Prishtinë dhe diskutoni projektin tuaj.`

**Privacy Policy / Terms & Conditions** (after the real legal text exists)
- Privacy — EN: `How demo collects and uses personal data from this website, the contact form and meeting bookings.` · SQ: `Si i mbledh dhe i përdor demo të dhënat personale nga kjo faqe, formulari i kontaktit dhe rezervimet e takimeve.`
- Terms — EN: `Terms and conditions for using the demo website.` · SQ: `Kushtet dhe termat e përdorimit të faqes së demo.`

Check the meeting formats offered (online / in person) in Meetings → Meeting types before using the "Book a meeting" text.

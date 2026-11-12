import type { AssistantErrorCode } from "./schema";

/**
 * Interface words around the assistant (buttons, states, errors). The
 * assistant's own texts — name, welcome, tooltip, placeholder, answers — come
 * from the CMS. Same locale convention as the rest of the site chrome.
 */
export function assistantLabels(locale: string) {
  if (locale === "sq" || locale === "al") return {
    retrieving: "Po gjej informacionin përkatës…", ready: "Gati për t’ju ndihmuar", busy: "Po punoj…", previewShort: "Parapamje",
    showing: (shown: number, total: number) => `${shown} nga ${total}`, opening: "Po hapet…", composer: "Pyetja juaj",
    open: "Hap asistentin", close: "Mbyll asistentin", send: "Dërgo pyetjen", thinking: "Duke menduar…",
    you: "Ju", suggestions: "Pyetje të sugjeruara", followUps: "Mund të pyesni gjithashtu", newChat: "Bisedë e re",
    retry: "Provo përsëri", preview: "Parapamje — e fshehur për vizitorët", email: "Email", phone: "Telefon", address: "Adresa", hours: "Orari", map: "Hap hartën",
    errors: { invalid: "Pyetja nuk mund të dërgohej. Provoni ta shkruani ndryshe.", unavailable: "Asistenti nuk është i disponueshëm tani.", busy: "Shumë pyetje njëherësh. Provoni përsëri pas pak.", failed: "Diçka shkoi keq. Provoni përsëri.", timeout: "Përgjigja po vonon shumë. Provoni përsëri." },
  };
  if (locale === "hu") return {
    retrieving: "Releváns információ keresése…", ready: "Segítségére vagyok", busy: "Dolgozom…", previewShort: "Előnézet",
    showing: (shown: number, total: number) => `${shown} / ${total}`, opening: "Megnyitás…", composer: "Az Ön kérdése",
    open: "Asszisztens megnyitása", close: "Asszisztens bezárása", send: "Kérdés küldése", thinking: "Gondolkodom…",
    you: "Ön", suggestions: "Javasolt kérdések", followUps: "Kérdezhetné azt is", newChat: "Új beszélgetés",
    retry: "Újra", preview: "Előnézet — a látogatók nem látják", email: "E-mail", phone: "Telefon", address: "Cím", hours: "Nyitvatartás", map: "Térkép megnyitása",
    errors: { invalid: "A kérdést nem sikerült elküldeni. Próbálja másképp megfogalmazni.", unavailable: "Az asszisztens most nem érhető el.", busy: "Túl sok kérdés egyszerre. Próbálja újra kicsit később.", failed: "Valami hiba történt. Próbálja újra.", timeout: "A válasz túl sokáig tart. Próbálja újra." },
  };
  return {
    retrieving: "Finding relevant information…", ready: "Ready to help", busy: "Working on it…", previewShort: "Preview",
    showing: (shown: number, total: number) => `${shown} of ${total}`, opening: "Opening…", composer: "Your question",
    open: "Open the assistant", close: "Close the assistant", send: "Send question", thinking: "Thinking…",
    you: "You", suggestions: "Suggested questions", followUps: "You could also ask", newChat: "New conversation",
    retry: "Try again", preview: "Preview — hidden from visitors", email: "Email", phone: "Phone", address: "Address", hours: "Hours", map: "Open map",
    errors: { invalid: "That question couldn’t be sent. Try wording it differently.", unavailable: "The assistant isn’t available right now.", busy: "Too many questions at once. Please try again in a moment.", failed: "Something went wrong. Please try again.", timeout: "The answer is taking too long. Please try again." },
  };
}
export type AssistantLabels = ReturnType<typeof assistantLabels>;
export type AssistantFailure = AssistantErrorCode | "timeout";

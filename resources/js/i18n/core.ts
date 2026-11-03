import en from "./en.json" with { type: "json" };
import fr from "./fr.json" with { type: "json" };
import es from "./es.json" with { type: "json" };
import de from "./de.json" with { type: "json" };
export const locales = ["en", "fr", "es", "de"] as const;
export type Locale = (typeof locales)[number];
export type MessageKey = keyof typeof en;
export const catalogs = { en, fr, es, de } satisfies Record<
    Locale,
    Record<MessageKey, string>
>;
export const languageNames: Record<Locale, string> = {
    en: "English",
    fr: "Français",
    es: "Español",
    de: "Deutsch",
};
const regionalLocales: Record<Locale, string> = {
    en: "en-GB",
    fr: "fr-FR",
    es: "es-ES",
    de: "de-DE",
};
export function translate(
    locale: Locale,
    key: MessageKey,
    values: Record<string, string | number> = {},
) {
    return catalogs[locale][key].replace(
        /\{(\w+)\}/g,
        (placeholder, name: string) =>
            Object.hasOwn(values, name) ? String(values[name]) : placeholder,
    );
}
export function formatBudget(amount: number, currency: string, locale: Locale) {
    return new Intl.NumberFormat(regionalLocales[locale], {
        style: "currency",
        currency,
        maximumFractionDigits: 0,
    }).format(amount);
}
export function formatGiftDate(date: string, locale: Locale) {
    return new Intl.DateTimeFormat(regionalLocales[locale], {
        day: "numeric",
        month: "long",
        year: "numeric",
        timeZone: "UTC",
    }).format(new Date(`${date}T12:00:00Z`));
}

import { describe, expect, it } from "vitest";
import {
    catalogs,
    translate,
    locales,
    formatBudget,
    formatGiftDate,
} from "../../resources/js/i18n/core";

describe("localized messages", () => {
    it.each(locales)(
        "provides every English key and interpolation in %s",
        (locale) => {
            expect(Object.keys(catalogs[locale]).sort()).toEqual(
                Object.keys(catalogs.en).sort(),
            );
            for (const [key, message] of Object.entries(catalogs.en)) {
                expect(
                    catalogs[locale][key as keyof typeof catalogs.en].trim(),
                ).not.toBe("");
                expect(
                    catalogs[locale][key as keyof typeof catalogs.en]
                        .match(/\{\w+\}/g)
                        ?.sort() ?? [],
                ).toEqual(message.match(/\{\w+\}/g)?.sort() ?? []);
            }
        },
    );
    it("interpolates user text literally, never interpreting placeholders inside it", () => {
        expect(
            translate("en", "Remove {name}", {
                name: "<script>{name}</script>",
            }),
        ).toBe("Remove <script>{name}</script>");
    });
    it("formats money in the selected locale without converting the amount", () => {
        expect(formatBudget(25, "CHF", "de")).toContain("25");
        expect(formatBudget(25, "EUR", "en")).toBe("€25");
        expect(formatBudget(25, "EUR", "fr")).toMatch(/25\s€/);
        expect(formatBudget(25, "JPY", "es")).toMatch(/25/);
    });
    it("formats calendar dates without shifting the gift day", () => {
        expect(formatGiftDate("2026-12-24", "en")).toBe("24 December 2026");
        expect(formatGiftDate("2026-12-24", "de")).toBe("24. Dezember 2026");
    });
});

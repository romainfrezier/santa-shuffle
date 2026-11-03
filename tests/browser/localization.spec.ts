import { test, expect } from "@playwright/test";
import { locales, translate, type Locale } from "../../resources/js/i18n/core";
import { fixture, login, xsrf } from "./helpers";

test("all four languages persist through login, event validation, creation and navigation", async ({
    page,
}) => {
    await page.goto("/login");
    let current: Locale = "fr";
    for (const locale of locales) {
        await page
            .getByRole("combobox", { name: translate(current, "Language") })
            .selectOption(locale);
        await expect(page.locator("html")).toHaveAttribute("lang", locale);
        await expect(
            page.getByLabel(translate(locale, "Your email address")),
        ).toBeVisible();
        await expect(
            page.getByRole("button", {
                name: translate(locale, "Send me my code"),
            }),
        ).toBeVisible();
        current = locale;
    }
    await login(page, `locales-${Date.now()}@example.test`, current);
    for (const locale of locales) {
        fixture("reset-rate-limits");
        await page
            .getByRole("combobox", { name: translate(current, "Language") })
            .selectOption(locale);
        await expect(page.locator("html")).toHaveAttribute("lang", locale);
        const t = (key: Parameters<typeof translate>[1]) =>
            translate(locale, key);
        await page
            .getByRole("link", {
                name: t("Create a Secret Santa"),
                exact: true,
            })
            .click();
        await page.getByLabel(t("Group name")).fill(`Localization ${locale}`);
        const date = new Date();
        date.setDate(date.getDate() + 20);
        await page
            .getByLabel(t("Gift date"))
            .fill(date.toISOString().slice(0, 10));
        await page.getByLabel(t("Budget per gift")).fill("0");
        await page
            .locator("form")
            .evaluate((form) => form.setAttribute("novalidate", ""));
        await page
            .getByRole("button", { name: t("Create my Secret Santa") })
            .click();
        await expect(page.getByLabel(t("Budget per gift"))).toHaveAttribute(
            "aria-invalid",
            "true",
        );
        await expect(page.locator("#budget-error")).not.toBeEmpty();
        await page.getByLabel(t("Budget per gift")).fill("25");
        await page
            .getByRole("button", { name: t("Create my Secret Santa") })
            .click();
        await expect(page).toHaveURL(/\/manage$/);
        await expect(
            page.getByRole("heading", {
                name: `Localization ${locale}`,
                exact: true,
            }),
        ).toBeVisible();
        await expect(
            page.getByRole("heading", { name: t("Event settings") }),
        ).toBeVisible();
        await expect(
            page.getByRole("button", {
                name: t("Start the draw"),
                exact: true,
            }),
        ).toBeDisabled();
        await page.setViewportSize({ width: 390, height: 844 });
        await page.screenshot({
            path: `artifacts/manage-${locale}-mobile.png`,
            fullPage: true,
        });
        expect(
            await page.evaluate(
                () => document.documentElement.scrollWidth <= innerWidth,
            ),
        ).toBeTruthy();
        await page
            .getByRole("link", { name: t("My events"), exact: true })
            .click();
        await expect(page).toHaveURL("/my-events");
        await page.reload();
        await expect(page.locator("html")).toHaveAttribute("lang", locale);
        await expect(
            page.getByRole("heading", { name: t("My events"), exact: true }),
        ).toBeVisible();
        current = locale;
    }
    const response = await page.request.post("/locale", {
        headers: { "X-XSRF-TOKEN": await xsrf(page) },
        data: { locale: "../../etc/passwd" },
    });
    expect(response.status()).toBe(200);
    await page.reload();
    await expect(page.locator("html")).toHaveAttribute("lang", current);
    const stranger = fixture("seed", "Private event");
    await page.goto(`/events/${stranger.uuid}/manage`);
    await expect(page.locator("html")).toHaveAttribute("lang", current);
    await expect(
        page.getByRole("heading", {
            name: translate(current, "This envelope is private."),
        }),
    ).toBeVisible();
    await page.goto("/missing-localized-page");
    await expect(page.locator("html")).toHaveAttribute("lang", current);
    await expect(
        page.getByRole("heading", {
            name: translate(current, "This invitation could not be found."),
        }),
    ).toBeVisible();
});

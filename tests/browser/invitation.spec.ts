import { expect, test } from "@playwright/test";
import { fixture } from "./helpers";

test("participants can return from home and keep a private invitation for another browser", async ({
    page,
    context,
    browser,
}) => {
    const event = fixture("seed", "Équipe cadeaux");
    const spaceUrl = `/events/${event.uuid}/me`;
    const openSpace = "Ouvrir mon espace personnel";
    await page.goto(event.people[0]);
    await page.getByRole("button", { name: openSpace }).click();
    await expect(page).toHaveURL(spaceUrl);
    await page.goto("/");
    await page
        .getByRole("link", { name: "Mon enveloppe pour Équipe cadeaux" })
        .click();
    await expect(page).toHaveURL(spaceUrl);
    await page
        .getByRole("link", {
            name: "Retrouver mon lien privé pour le conserver",
        })
        .click();
    await expect(page).toHaveURL(event.people[0]);
    await context.grantPermissions(["clipboard-read", "clipboard-write"]);
    await page.getByRole("button", { name: "Copier mon lien privé" }).click();
    await expect(page.getByRole("button", { name: "Copié !" })).toBeVisible();
    const saved = await page.evaluate(() => navigator.clipboard.readText());
    expect(saved).toBe(event.people[0]);

    const other = await browser.newContext({
        locale: "fr-FR",
        baseURL: new URL(page.url()).origin,
    });
    const secondPage = await other.newPage();
    await secondPage.goto("/");
    await expect(secondPage.getByText("Équipe cadeaux")).toHaveCount(0);
    expect((await secondPage.request.get(spaceUrl)).status()).toBe(403);
    await secondPage.goto(saved);
    await secondPage.getByRole("button", { name: openSpace }).click();
    await expect(
        secondPage.getByRole("heading", { name: /Personne 1/ }),
    ).toBeVisible();
    await other.close();
});

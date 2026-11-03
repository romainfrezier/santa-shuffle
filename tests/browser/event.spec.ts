import { test, expect } from "@playwright/test";
import { fixture, login, manage, draw, xsrf } from "./helpers";
test("create an event with real session and CSRF", async ({ page }) => {
    await login(page, `create-${Date.now()}@example.test`);
    await page.goto("/create");
    await page.getByLabel("Nom du groupe").fill("Équipe création");
    const date = new Date();
    date.setDate(date.getDate() + 20);
    await page
        .getByLabel("Date des cadeaux")
        .fill(date.toISOString().slice(0, 10));
    await page.getByRole("combobox", { name: "Devise" }).click();
    await page.getByRole("option", { name: "CHF · franc suisse" }).click();
    await page.getByRole("button", { name: "Créer mon Secret Santa" }).click();
    await expect(page).toHaveURL(/\/manage$/);
    await expect(page.getByText(/20.*CHF par cadeau/)).toBeVisible();
    await expect(
        page.getByRole("heading", { name: "Équipe création", exact: true }),
    ).toBeVisible();
});
test("reopen, invite, confirm, redraw and reveal; responsive management", async ({
    page,
    browser,
}) => {
    const event = fixture("seed");
    await manage(page, event);
    await page.getByText("Modifier les informations", { exact: true }).click();
    await page.getByLabel("Budget par cadeau").fill("25");
    await page
        .getByRole("button", { name: "Enregistrer les informations" })
        .click();
    await expect(page.getByText("25 € par cadeau")).toBeVisible();
    await draw(page);
    page.on("dialog", (d) => d.accept());
    await page
        .getByRole("button", { name: "Rouvrir les inscriptions" })
        .click();
    await expect(
        page.getByText("Les inscriptions sont ouvertes", { exact: true }),
    ).toBeVisible();
    await page
        .getByText("Inviter une personne par e-mail", { exact: true })
        .click();
    await page.getByLabel("Prénom de la personne").fill("Zoé");
    await page.getByLabel("Son adresse e-mail").fill("late@example.test");
    await page.getByRole("button", { name: "Envoyer l’invitation" }).click();
    await expect(
        page.locator(".person-name").filter({ hasText: "Zoé" }),
    ).toBeVisible();
    await expect(
        page.getByRole("button", { name: "Lancer le tirage", exact: true }),
    ).toBeDisabled();
    const late = fixture("participant", event.uuid, "late@example.test");
    const ctx = await browser.newContext({ locale: "fr-FR" });
    const participant = await ctx.newPage();
    await participant.goto(late.link);
    await participant
        .getByRole("button", { name: "Ouvrir mon espace personnel" })
        .click();
    await page.getByRole("button", { name: "Actualiser", exact: true }).click();
    await expect(
        page.getByRole("button", { name: "Lancer le tirage", exact: true }),
    ).toBeEnabled();
    await page.setViewportSize({ width: 1280, height: 900 });
    await page.screenshot({
        path: "artifacts/manage-desktop.png",
        fullPage: true,
    });
    await page.setViewportSize({ width: 390, height: 844 });
    await page.screenshot({
        path: "artifacts/manage-mobile.png",
        fullPage: true,
    });
    expect(
        await page.evaluate(
            () => document.documentElement.scrollWidth <= window.innerWidth,
        ),
    ).toBeTruthy();
    await draw(page);
    await participant.reload();
    await participant
        .getByRole("button", { name: /ouvrir mon enveloppe/i })
        .click();
    await expect(
        participant.getByText("<img src=x onerror=window.pwned=1>", {
            exact: true,
        }),
    ).toBeVisible();
    expect(
        await participant.evaluate(
            () => (window as Window & { pwned?: unknown }).pwned,
        ),
    ).toBeUndefined();
    await page.getByRole("button", { name: "Actualiser", exact: true }).click();
    await expect(
        page.getByRole("button", { name: "Rouvrir les inscriptions" }),
    ).toHaveCount(0);
    await ctx.close();
});
test("stored XSS is text in the management view", async ({ page }) => {
    const name = "<img src=x onerror=window.pwned=1>";
    const event = { ...fixture("seed", name), name };
    await manage(page, event);
    expect(
        await page.evaluate(
            () => (window as Window & { pwned?: unknown }).pwned,
        ),
    ).toBeUndefined();
    await expect(page.locator('img[src="x"]')).toHaveCount(0);
});
test("real HTTP rejects missing and invalid CSRF and unauthorized event access", async ({
    page,
    request,
}) => {
    const event = fixture("seed");
    await manage(page, event);
    for (const headers of [
        {},
        { "X-XSRF-TOKEN": "invalid" },
        { Origin: "https://attacker.example", "Sec-Fetch-Site": "cross-site" },
    ] as Record<string, string>[]) {
        const response = await page.request.post(
            `/events/${event.uuid}/reopen`,
            { headers, data: { confirm: 1 } },
        );
        expect(response.status()).toBe(419);
    }
    const protectedPage = await request.get(`/events/${event.uuid}/manage`);
    expect(protectedPage.status()).toBe(403);
    expect(await protectedPage.text()).not.toContain("owner@example.test");
    const response = await page.request.post(`/events/${event.uuid}/rotate`, {
        headers: { "X-XSRF-TOKEN": await xsrf(page) },
        data: { confirm: 1 },
    });
    expect(response.ok()).toBeTruthy();
    expect((await request.get(event.manager)).status()).toBe(404);
});
test("logout rotates session and prevents reuse", async ({ page, request }) => {
    const event = fixture("seed");
    await page.goto("/login");
    const before = (await page.context().cookies()).find((c) =>
        c.name.endsWith("session"),
    )!.value;
    await manage(page, event);
    const cookies = await page.context().cookies();
    expect(cookies.find((c) => c.name.endsWith("session"))!.value).not.toBe(
        before,
    );
    const cookieHeader = cookies.map((c) => `${c.name}=${c.value}`).join("; ");
    await page.request.post("/logout", {
        headers: { "X-XSRF-TOKEN": await xsrf(page) },
    });
    expect(
        (
            await request.get(`/events/${event.uuid}/manage`, {
                headers: { Cookie: cookieHeader },
            })
        ).status(),
    ).toBe(403);
});
test("cancel and delete from management", async ({ page }) => {
    const event = fixture("seed");
    await manage(page, event);
    page.on("dialog", (d) => d.accept());
    await page.getByText("Annulation et suppression", { exact: true }).click();
    await page
        .getByRole("button", { name: "Annuler l’événement", exact: true })
        .click();
    await expect(
        page.getByText("Événement annulé", { exact: true }),
    ).toBeVisible();
    if (
        !(await page
            .getByRole("button", {
                name: "Supprimer définitivement",
                exact: true,
            })
            .isVisible())
    )
        await page
            .getByText("Annulation et suppression", { exact: true })
            .click();
    await page
        .getByRole("button", { name: "Supprimer définitivement", exact: true })
        .click();
    await expect(page).toHaveURL("/");
    expect((await page.request.get(`/events/${event.uuid}`)).status()).toBe(
        404,
    );
});

test("warm home and currency picker work on mobile, keyboard and reduced motion", async ({
    page,
}) => {
    await page.goto("/");
    await page.getByRole("button", { name: "Un tour de magie ?" }).click();
    await expect(page.locator(".card-front strong")).toHaveText("Alex ?");
    await page.screenshot({
        path: "artifacts/home-desktop.png",
        fullPage: true,
    });
    await page.setViewportSize({ width: 390, height: 844 });
    await page.screenshot({
        path: "artifacts/home-mobile.png",
        fullPage: true,
    });
    expect(
        await page.evaluate(
            () => document.documentElement.scrollWidth <= innerWidth,
        ),
    ).toBeTruthy();
    await page.emulateMedia({ reducedMotion: "reduce" });
    await page.reload();
    await page.getByRole("button", { name: "Un tour de magie ?" }).click();
    await expect(page.locator(".card-front strong")).toHaveText("Alex ?");
    await login(page, `keyboard-${Date.now()}@example.test`);
    await page.goto("/create");
    const currency = page.getByRole("combobox", { name: "Devise" });
    await currency.focus();
    await page.keyboard.press("Space");
    await expect(
        page.getByRole("option", { name: "EUR · euro", exact: true }),
    ).toBeFocused();
    await page.keyboard.press("ArrowDown");
    await expect(
        page.getByRole("option", { name: "CHF · franc suisse" }),
    ).toBeFocused();
    await page.keyboard.press("Enter");
    await expect(currency).toContainText("CHF");
    await page.screenshot({
        path: "artifacts/create-mobile.png",
        fullPage: true,
    });
    expect(
        await page.evaluate(
            () => document.documentElement.scrollWidth <= innerWidth,
        ),
    ).toBeTruthy();
});

test("email login protects legacy links and lists multiple owned events", async ({
    page,
}) => {
    const first = fixture("seed", "Noël studio");
    fixture("seed", "Noël famille", first.email);
    fixture("seed", "Événement confidentiel");
    await page.goto(first.manager);
    await expect(page).toHaveURL("/login");
    expect(
        (await page.request.get(`/events/${first.uuid}/manage`)).status(),
    ).toBe(403);
    await page.screenshot({
        path: "artifacts/login-desktop.png",
        fullPage: true,
    });
    await page.setViewportSize({ width: 390, height: 844 });
    await page.screenshot({
        path: "artifacts/login-mobile.png",
        fullPage: true,
    });
    await login(page, first.email);
    await expect(
        page.getByRole("heading", { name: "Noël studio", exact: true }),
    ).toBeVisible();
    await expect(
        page.getByRole("heading", { name: "Noël famille", exact: true }),
    ).toBeVisible();
    await expect(page.getByText("Événement confidentiel")).toHaveCount(0);
    await page.screenshot({
        path: "artifacts/events-mobile.png",
        fullPage: true,
    });
    expect(
        await page.evaluate(
            () => document.documentElement.scrollWidth <= innerWidth,
        ),
    ).toBeTruthy();
    await page.setViewportSize({ width: 1280, height: 900 });
    await page.screenshot({
        path: "artifacts/events-desktop.png",
        fullPage: true,
    });
});

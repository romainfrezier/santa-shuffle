import { expect, type Page } from "@playwright/test";
import { spawnSync } from "node:child_process";
import { readFileSync } from "node:fs";
import { translate, type Locale } from "../../resources/js/i18n/core";
type SeededEvent = {
    email: string;
    uuid: string;
    manager: string;
    people: string[];
    name?: string;
};
export function fixture(
    mode: "seed",
    name?: string,
    email?: string,
): SeededEvent;
export function fixture(
    mode: "participant",
    uuid: string,
    email: string,
): { link: string };
export function fixture(mode: "reset-rate-limits"): { cleared: boolean };
export function fixture(...args: (string | undefined)[]) {
    const p = spawnSync(
        "php",
        ["tests/browser/fixture.php", ...args.map((value) => value ?? "")],
        {
            env: {
                ...process.env,
                ...JSON.parse(
                    readFileSync("artifacts/browser-runtime.json", "utf8"),
                ),
            },
            encoding: "utf8",
        },
    );
    if (p.status !== 0) throw Error(p.stderr + p.stdout);
    return JSON.parse(p.stdout);
}
export async function login(page: Page, email: string, locale: Locale = "fr") {
    await page.goto("/login");
    await page.getByLabel(translate(locale, "Your email address")).fill(email);
    await page
        .getByRole("button", { name: translate(locale, "Send me my code") })
        .click();
    await expect(
        page.getByLabel(translate(locale, "6-digit code")),
    ).toBeVisible();
    const logs = readFileSync("artifacts/browser-server.log", "utf8")
        .replace(/=\r?\n/g, "")
        .replace(/=3D/g, "=");
    const codes = [...logs.matchAll(/font-weight:bold[^>]*>([0-9]{6})</g)];
    if (!codes.length) throw Error("No code in synthetic mail sink");
    await page
        .getByLabel(translate(locale, "6-digit code"))
        .fill(codes.at(-1)![1]);
    await page
        .getByRole("button", { name: translate(locale, "Open my space") })
        .click();
    await expect(page).toHaveURL("/my-events");
}
export async function manage(page: Page, event: SeededEvent) {
    await login(page, event.email);
    await page.goto(`/events/${event.uuid}/manage`);
    await expect(
        page.getByRole("heading", {
            name: event.name ?? "Équipe navigateur",
            exact: true,
        }),
    ).toBeVisible();
}
export async function draw(page: Page) {
    await page.getByRole("checkbox").check();
    await page
        .getByRole("button", { name: "Lancer le tirage", exact: true })
        .click();
    await expect(
        page.getByText("Tirage terminé", { exact: true }),
    ).toBeVisible();
}
export async function xsrf(page: Page) {
    return decodeURIComponent(
        (await page.context().cookies()).find((c) => c.name === "XSRF-TOKEN")!
            .value,
    );
}

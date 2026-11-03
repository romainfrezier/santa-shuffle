import { defineConfig } from "@playwright/test";
export default defineConfig({
    testDir: "tests/browser",
    fullyParallel: false,
    workers: 1,
    timeout: 30000,
    outputDir: "artifacts/playwright",
    reporter: [
        ["list"],
        [
            "html",
            { outputFolder: "artifacts/playwright-report", open: "never" },
        ],
    ],
    use: {
        locale: "fr-FR",
        baseURL: "http://127.0.0.1:8137",
        trace: "retain-on-failure",
        screenshot: "only-on-failure",
    },
    webServer: {
        command: "node tests/browser/server.mjs",
        url: "http://127.0.0.1:8137/up",
        reuseExistingServer: false,
        timeout: 30000,
    },
});

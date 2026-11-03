import assert from "node:assert/strict";
import { execFileSync } from "node:child_process";
import { readFileSync } from "node:fs";
import test from "node:test";

test("the production notice preserves font licenses and non-MIT animation terms", () => {
    execFileSync(process.execPath, ["scripts/collect-licenses.mjs"]);
    const notice = readFileSync(
        "public/build/THIRD_PARTY_LICENSES.txt",
        "utf8",
    );
    for (const font of ["fraunces", "manrope"]) {
        assert.ok(
            notice.includes(
                readFileSync(
                    `node_modules/@fontsource-variable/${font}/LICENSE`,
                    "utf8",
                ),
            ),
            `${font} must retain its complete upstream copyright and license`,
        );
    }
    assert.match(notice, /GSAP License: https:\/\/gsap\.com\/standard-license/);
    assert.match(notice, /This is not the MIT license/);
    assert.ok(
        notice.includes(
            readFileSync("docs/licenses/react-remove-scroll-bar.txt", "utf8"),
        ),
    );
});

import { readFile, readdir, writeFile, mkdir } from "node:fs/promises";
import { join } from "node:path";

const lock = JSON.parse(await readFile("package-lock.json", "utf8"));
const sections = [
    "Santa Shuffle third-party notices\nProject source is MIT licensed. Each dependency retains its own license.\nThis file includes installed production npm packages, including packages not bundled into browser code.",
];

for (const [directory, dependency] of Object.entries(lock.packages).sort()) {
    if (!directory || dependency.dev) continue;
    let files;
    try {
        files = await readdir(directory);
    } catch (error) {
        if (error.code === "ENOENT" && dependency.optional) continue;
        throw error;
    }
    const metadata = JSON.parse(
        await readFile(join(directory, "package.json"), "utf8"),
    );
    const notices = files.filter((name) =>
        /^(licen[cs]e|copying|notice)([.-]|$)/i.test(name),
    );
    const texts = await Promise.all(
        notices.sort().map((name) => readFile(join(directory, name), "utf8")),
    );
    if (!texts.length) {
        if (metadata.name === "gsap" || metadata.name === "@gsap/react") {
            texts.push(
                "Copyright GreenSock / Webflow. Standard No Charge GSAP License: https://gsap.com/standard-license\nThis is not the MIT license. The upstream package distributes its terms by URL.",
            );
        } else {
            // Some npm archives omit their upstream license; keep reviewed copies in source.
            texts.push(
                await readFile(`docs/licenses/${metadata.name}.txt`, "utf8"),
            );
        }
    }
    sections.push(
        `${metadata.name}@${metadata.version}\nLicense: ${metadata.license ?? dependency.license ?? "See notice"}\n\n${texts.join("\n\n")}`,
    );
}

await mkdir("public/build", { recursive: true });
await writeFile(
    "public/build/THIRD_PARTY_LICENSES.txt",
    sections.join("\n\n----------------------------------------\n\n"),
);
console.log(
    `Collected notices for ${sections.length - 1} installed production packages.`,
);

# Third-party notices

The [MIT license](LICENSE) covers Santa Shuffle's own source. It does not relicense dependencies, fonts or base-image software. Review the installed versions in `composer.lock` and `package-lock.json` when distributing a modified application.

| Component | License evidence in the installed package |
| --- | --- |
| Laravel, Inertia, React, Radix UI and Sonner | MIT; see each package's LICENSE file and lockfile metadata |
| Lucide React | ISC, plus MIT for icons derived from Feather; both notices are in its LICENSE file |
| canvas-confetti | ISC, included in its LICENSE file |
| Fraunces and Manrope fonts | SIL Open Font License 1.1; copyright and full license in each Fontsource package's LICENSE file |
| GSAP and `@gsap/react` | GSAP Standard No Charge License, linked by their package manifests; **not MIT** |

GSAP's license permits use in websites and applications but restricts certain visual animation-builder uses. It is not an unrestricted open-source license. Santa Shuffle's source is open source, but the complete dependency stack must not be described as entirely MIT or entirely OSI-licensed. Review the [upstream GSAP terms](https://gsap.com/standard-license/) before changing the product into an animation tool. This notice is not a relicensing or legal opinion.

`npm run build` writes `public/build/THIRD_PARTY_LICENSES.txt`, preserving the license texts supplied by installed production npm packages, including the complete font notices. The Docker image serves this file at `/build/THIRD_PARTY_LICENSES.txt`. The collector includes optional installed production packages even if they are not shipped in the browser bundle. Packages that omit license files require an explicitly reviewed source copy in `docs/licenses/`; missing unreviewed notices fail the build.

The reviewed copies for `react-remove-scroll-bar` and `yoga-layout` come from their upstream repositories because their npm archives omit the license files. GSAP packages supply their terms by URL; the generated notice preserves that reference. Composer dependencies retain their license files under `vendor/` in the container. Container OS and runtime software retains its own license metadata; the generated npm notice is not a complete container license inventory. Release builds include an SBOM to support further review.

Sources: [react-remove-scroll-bar LICENSE](https://github.com/theKashey/react-remove-scroll-bar/blob/master/LICENSE), [Yoga 3.2.1 LICENSE](https://github.com/facebook/yoga/blob/v3.2.1/LICENSE), and the installed package license files listed above.

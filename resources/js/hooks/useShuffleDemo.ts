import { useRef, useState } from "react";
import { gsap } from "gsap";
import { useGSAP } from "@gsap/react";
gsap.registerPlugin(useGSAP);
const names = ["Camille", "Alex", "Charlie", "Lou"];
export function useShuffleDemo() {
    const scene = useRef<HTMLDivElement>(null);
    const [round, setRound] = useState(0);
    const { contextSafe } = useGSAP(
        () => {
            const media = gsap.matchMedia();
            media.add("(prefers-reduced-motion: no-preference)", () => {
                gsap.from(".shuffle-card", {
                    y: 35,
                    rotation: 0,
                    stagger: 0.12,
                    duration: 0.8,
                    ease: "power3.out",
                });
            });
            return () => media.revert();
        },
        { scope: scene },
    );
    const shuffle = contextSafe(() => {
        setRound((value) => value + 1);
        if (!window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
            gsap.fromTo(
                ".shuffle-card",
                { y: -18, x: 14 },
                {
                    y: 0,
                    x: 0,
                    duration: 0.65,
                    stagger: 0.08,
                    ease: "back.out(2)",
                    overwrite: "auto",
                },
            );
        }
    });
    return { scene, shuffle, name: names[round % names.length] };
}

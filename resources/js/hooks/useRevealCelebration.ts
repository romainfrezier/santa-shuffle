import { useEffect, useRef } from "react";
export function useRevealCelebration(recipient: unknown) {
    const opened = useRef(false);
    useEffect(() => {
        if (!recipient || !opened.current) return;
        opened.current = false;
        let active = true;
        if (!window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
            void import("canvas-confetti").then(({ default: confetti }) => {
                if (active)
                    void confetti({
                        particleCount: 90,
                        spread: 75,
                        origin: { y: 0.65 },
                        colors: ["#b83242", "#255447", "#eeb9af", "#d7ab52"],
                        disableForReducedMotion: true,
                    });
            });
        }
        return () => {
            active = false;
        };
    }, [recipient]);
    return () => {
        opened.current = true;
    };
}

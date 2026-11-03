import { router } from "@inertiajs/react";
import { useRef, useState } from "react";
export type EventAction = (
    path: string,
    prompt: string,
    remove?: boolean,
) => void;
export function useEventAction(uuid: string) {
    const pending = useRef(false);
    const [busy, setBusy] = useState(false);
    const [errors, setErrors] = useState<string[]>([]);
    const action: EventAction = (path, prompt, remove = false) => {
        if (pending.current || !window.confirm(prompt)) return;
        pending.current = true;
        setBusy(true);
        setErrors([]);
        router.visit(`/events/${uuid}${path}`, {
            method: remove ? "delete" : "post",
            data: { confirm: 1 },
            preserveScroll: true,
            onError: (errors) => setErrors(Object.values(errors)),
            onFinish: () => {
                pending.current = false;
                setBusy(false);
            },
        });
    };
    return { action, busy, errors };
}

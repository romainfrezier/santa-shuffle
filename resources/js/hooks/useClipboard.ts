import { useState } from "react";
export function useClipboard(value: string) {
    const [copied, setCopied] = useState(false);
    const [copyError, setCopyError] = useState(false);
    async function copy() {
        try {
            await navigator.clipboard.writeText(value);
            setCopied(true);
            setCopyError(false);
        } catch {
            setCopyError(true);
        }
    }
    return { copy, copied, copyError };
}

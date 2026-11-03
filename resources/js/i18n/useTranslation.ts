import { usePage } from "@inertiajs/react";
import { translate, type Locale, type MessageKey } from "./core";
export function useTranslation() {
    const { locale = "en" } = usePage<{ locale?: Locale }>().props;
    return {
        locale,
        t: (key: MessageKey, values?: Record<string, string | number>) =>
            translate(locale, key, values),
    };
}

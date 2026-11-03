import { router } from "@inertiajs/react";
import { useState } from "react";
import { locales, languageNames, type Locale } from "../i18n/core";
import { useTranslation } from "../i18n/useTranslation";
export function LanguageSwitcher() {
    const { locale, t } = useTranslation();
    const [busy, setBusy] = useState(false);
    function changeLanguage(value: Locale) {
        setBusy(true);
        router.post(
            "/locale",
            { locale: value },
            { preserveScroll: true, onFinish: () => setBusy(false) },
        );
    }
    return (
        <label className="language-switcher">
            <span className="sr-only">{t("Language")}</span>
            <select
                value={locale}
                disabled={busy}
                onChange={(event) =>
                    changeLanguage(event.target.value as Locale)
                }
            >
                {locales.map((code) => (
                    <option key={code} value={code} lang={code}>
                        {languageNames[code]}
                    </option>
                ))}
            </select>
        </label>
    );
}

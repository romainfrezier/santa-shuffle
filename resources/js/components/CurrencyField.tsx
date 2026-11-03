import { usePage } from "@inertiajs/react";
import * as Select from "@radix-ui/react-select";
import { Check, ChevronDown } from "lucide-react";
import { useTranslation } from "../i18n/useTranslation";
export function CurrencyField({
    value = "EUR",
    error,
}: {
    value?: string;
    error?: string;
}) {
    const { t, locale } = useTranslation();

    const { currencies } = usePage<{ currencies: Record<string, string> }>()
        .props;
    return (
        <div className="field">
            <label htmlFor="currency">{t("Currency")}</label>
            <Select.Root name="currency" defaultValue={value}>
                <Select.Trigger
                    id="currency"
                    className="currency-trigger"
                    aria-invalid={!!error}
                    aria-describedby={error ? "currency-error" : undefined}
                >
                    <Select.Value />
                    <Select.Icon>
                        <ChevronDown size={18} />
                    </Select.Icon>
                </Select.Trigger>
                <Select.Portal>
                    <Select.Content
                        className="currency-menu"
                        position="popper"
                        sideOffset={6}
                    >
                        <Select.Viewport>
                            {Object.keys(currencies).map((code) => (
                                <Select.Item
                                    className="currency-option"
                                    key={code}
                                    value={code}
                                >
                                    <Select.ItemText>
                                        {code} ·{" "}
                                        {new Intl.DisplayNames(locale, {
                                            type: "currency",
                                        }).of(code)}
                                    </Select.ItemText>
                                    <Select.ItemIndicator>
                                        <Check size={16} />
                                    </Select.ItemIndicator>
                                </Select.Item>
                            ))}
                        </Select.Viewport>
                    </Select.Content>
                </Select.Portal>
            </Select.Root>
            {error && (
                <span id="currency-error" className="error" role="alert">
                    {error}
                </span>
            )}
        </div>
    );
}

import { CalendarDays, Gift } from "lucide-react";
import { useTranslation } from "../i18n/useTranslation";
import { formatGiftDate, formatBudget } from "../i18n/core";
import type { Exchange } from "../types/exchange";
export function EventDetails({ exchange }: { exchange: Exchange }) {
    const { t, locale } = useTranslation();

    return (
        <div className="event-details">
            <span>
                <CalendarDays size={16} aria-hidden="true" />
                {formatGiftDate(exchange.gift_date, locale)}
            </span>
            <span>
                <Gift size={16} aria-hidden="true" />
                {formatBudget(exchange.budget, exchange.currency, locale)}{" "}
                {t("per gift")}
            </span>
        </div>
    );
}

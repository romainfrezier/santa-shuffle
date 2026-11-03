import { CurrencyField } from "../../components/CurrencyField";
import { useTranslation } from "../../i18n/useTranslation";
import { Form } from "@inertiajs/react";
import { Field, TextArea } from "../../components/Fields";
import type { Exchange } from "../../types/exchange";
import type { EventAction } from "./useEventAction";
export default function EventSettings({
    exchange,
    action,
    busy,
}: {
    exchange: Exchange;
    action: EventAction;
    busy: boolean;
}) {
    const { t } = useTranslation();

    const base = `/events/${exchange.uuid}`;

    return (
        <>
            <section className="paper">
                <h2>{t("Event settings")}</h2>
                {!exchange.drawn && !exchange.cancelled && (
                    <details className="management-details">
                        <summary>{t("Edit details")}</summary>
                        <Form action={base} method="patch">
                            {({ errors, processing }) => (
                                <>
                                    <Field
                                        id="event-name"
                                        label={t("Event name")}
                                        name="name"
                                        defaultValue={exchange.name}
                                        required
                                        maxLength={100}
                                        error={errors.name}
                                    />
                                    <Field
                                        label={t("Gift date")}
                                        name="gift_date"
                                        type="date"
                                        defaultValue={exchange.gift_date}
                                        required
                                        error={errors.gift_date}
                                    />
                                    <Field
                                        label={t("Budget per gift")}
                                        name="budget"
                                        type="number"
                                        min={1}
                                        max={10000}
                                        defaultValue={exchange.budget}
                                        required
                                        error={errors.budget}
                                    />
                                    <CurrencyField
                                        error={errors.currency}
                                        value={exchange.currency}
                                    />
                                    <TextArea
                                        label={t("Message to the group")}
                                        name="message"
                                        defaultValue={exchange.message ?? ""}
                                        maxLength={1000}
                                        error={errors.message}
                                    />
                                    <button
                                        className="button secondary"
                                        disabled={processing}
                                    >
                                        {t("Save details")}
                                    </button>
                                </>
                            )}
                        </Form>
                    </details>
                )}
                {!exchange.cancelled && (
                    <button
                        className="text-button"
                        disabled={busy}
                        onClick={() =>
                            action(
                                "/rotate",
                                t(
                                    "Revoke your other organizer sessions? They will be logged out. This browser will keep its access.",
                                ),
                            )
                        }
                    >
                        {t("Revoke my other sessions")}
                    </button>
                )}
                <details className="management-details">
                    <summary>{t("Cancellation and deletion")}</summary>
                    <p>
                        {t(
                            "Deletion permanently removes participants and assignments. Data is also automatically deleted 30 days after the gift date.",
                        )}
                    </p>
                    {!exchange.drawn && !exchange.cancelled && (
                        <button
                            className="text-button remove"
                            disabled={busy}
                            onClick={() =>
                                action(
                                    "/cancel",
                                    t(
                                        "Cancel this event? Participants will be notified and their access closed.",
                                    ),
                                )
                            }
                        >
                            {t("Cancel event")}
                        </button>
                    )}
                    <button
                        className="text-button remove"
                        disabled={busy}
                        onClick={() =>
                            action(
                                "",
                                t(
                                    "Permanently delete this event and all its participants?",
                                ),
                                true,
                            )
                        }
                    >
                        {t("Delete permanently")}
                    </button>
                </details>
            </section>
        </>
    );
}

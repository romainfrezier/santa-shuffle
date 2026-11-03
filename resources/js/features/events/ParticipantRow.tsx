import { useTranslation } from "../../i18n/useTranslation";
import type { Exchange, Person } from "../../types/exchange";
import type { EventAction } from "./useEventAction";
export function ParticipantRow({
    person,
    exchange,
    action,
    busy,
}: {
    person: Person;
    exchange: Exchange;
    action: EventAction;
    busy: boolean;
}) {
    const { t } = useTranslation();
    return (
        <li>
            <span className="avatar" aria-hidden="true">
                {person.name.slice(0, 1).toUpperCase()}
            </span>
            <span className="person-name">{person.name}</span>
            <span className="person-state">
                {exchange.drawn
                    ? person.revealed
                        ? t("Envelope opened")
                        : t("Envelope sealed")
                    : person.confirmed
                      ? t("Confirmed")
                      : t("Email confirmation needed")}
            </span>
            {!exchange.cancelled && (
                <details className="person-actions">
                    <summary>
                        {t("Manage")}
                        <span className="sr-only"> {person.name}</span>
                    </summary>
                    <p className="fine-print">
                        {
                            {
                                pending: t("Email pending"),
                                accepted: t("Accepted by the SMTP server"),
                                failed: t("Sending failed"),
                                obsolete: t("Notification replaced"),
                            }[person.delivery ?? "pending"]
                        }
                    </p>
                    <button
                        className="text-button"
                        disabled={busy}
                        onClick={() =>
                            action(
                                `/participants/${person.id}/resend`,
                                t("Resend the link to {name}?", {
                                    name: person.name,
                                }),
                            )
                        }
                    >
                        {t("Resend link")}
                    </button>
                    <button
                        className="text-button"
                        disabled={busy}
                        onClick={() =>
                            action(
                                `/participants/${person.id}/rotate`,
                                t(
                                    "Revoke the link and sessions for {name}? A new link will be sent.",
                                    { name: person.name },
                                ),
                            )
                        }
                    >
                        {t("Renew link")}
                    </button>
                </details>
            )}
            {!exchange.drawn && !exchange.cancelled && (
                <button
                    className="text-button remove"
                    aria-label={t("Remove {name}", { name: person.name })}
                    onClick={() =>
                        action(
                            `/participants/${person.id}`,
                            t("Remove {name} from the group?", {
                                name: person.name,
                            }),
                            true,
                        )
                    }
                    disabled={busy}
                >
                    {t("Remove")}
                </button>
            )}
        </li>
    );
}

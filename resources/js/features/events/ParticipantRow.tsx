import { Form } from "@inertiajs/react";
import { ChevronDown, Mail, RotateCw, Trash2 } from "lucide-react";
import { Field } from "../../components/Fields";
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
    const path = `/participants/${person.id}`;
    const identity = (
        <>
            <span className="avatar" aria-hidden="true">
                {person.name.slice(0, 1).toUpperCase()}
            </span>
            <span className="person-identity">
                <strong className="person-name">{person.name}</strong>
                <span className="person-state">
                    {exchange.drawn
                        ? person.revealed
                            ? t("Envelope opened")
                            : t("Envelope sealed")
                        : person.confirmed
                          ? t("Confirmed")
                          : t("Email confirmation needed")}
                </span>
            </span>
        </>
    );

    return (
        <li>
            {exchange.cancelled ? (
                <div className="person-heading">{identity}</div>
            ) : (
                <details className="person-actions">
                    <summary className="person-heading">
                        {identity}
                        <span className="person-toggle">
                            {t("Manage")}{" "}
                            <ChevronDown size={16} aria-hidden="true" />
                        </span>
                    </summary>
                    <div className="person-tools">
                        {!exchange.drawn && (
                            <Form
                                action={`/events/${exchange.uuid}${path}/name`}
                                method="patch"
                                errorBag={`rename-${person.id}`}
                                options={{ preserveScroll: true }}
                            >
                                {({ errors, processing }) => (
                                    <>
                                        <Field
                                            id={`person-name-${person.id}`}
                                            name="name"
                                            label={t(
                                                "Name your group will recognize (e.g. Alex M.)",
                                            )}
                                            defaultValue={person.name}
                                            required
                                            maxLength={80}
                                            error={errors.name}
                                        />
                                        <Field
                                            id={`rename-email-${person.id}`}
                                            name="email"
                                            type="email"
                                            label={t(
                                                "Participant email for name change",
                                            )}
                                            required
                                            maxLength={254}
                                            error={errors.email}
                                        />
                                        <button
                                            className="button secondary"
                                            disabled={processing || busy}
                                        >
                                            {t("Save participant name")}
                                        </button>
                                    </>
                                )}
                            </Form>
                        )}
                        {!exchange.drawn && !person.confirmed && (
                            <Form
                                action={`/events/${exchange.uuid}${path}/confirm`}
                                method="post"
                                options={{ preserveScroll: true }}
                                errorBag={`confirm-${person.id}`}
                            >
                                {({ errors, processing }) => (
                                    <>
                                        <Field
                                            id={`confirm-email-${person.id}`}
                                            name="email"
                                            type="email"
                                            label={t(
                                                "Email address to confirm",
                                            )}
                                            required
                                            maxLength={254}
                                            autoComplete="off"
                                            error={errors.email}
                                        />
                                        <button
                                            className="button secondary"
                                            disabled={processing || busy}
                                        >
                                            {t("Confirm participation")}
                                        </button>
                                    </>
                                )}
                            </Form>
                        )}
                        {person.delivery && (
                            <p className="fine-print">
                                {
                                    {
                                        pending: t("Email pending"),
                                        accepted: t(
                                            "Accepted by the SMTP server",
                                        ),
                                        failed: t("Sending failed"),
                                        obsolete: t("Notification replaced"),
                                    }[person.delivery]
                                }
                            </p>
                        )}
                        <div className="person-buttons">
                            <button
                                type="button"
                                disabled={busy}
                                onClick={() =>
                                    action(
                                        `${path}/resend`,
                                        t("Resend the link to {name}?", {
                                            name: person.name,
                                        }),
                                    )
                                }
                            >
                                <Mail size={16} aria-hidden="true" />
                                {t("Resend link")}
                            </button>
                            <button
                                type="button"
                                disabled={busy}
                                onClick={() =>
                                    action(
                                        `${path}/rotate`,
                                        t(
                                            "Revoke the link and sessions for {name}? A new link will be sent.",
                                            { name: person.name },
                                        ),
                                    )
                                }
                            >
                                <RotateCw size={16} aria-hidden="true" />
                                {t("Renew link")}
                            </button>
                            {!exchange.drawn && (
                                <button
                                    type="button"
                                    className="remove"
                                    aria-label={t("Remove {name}", {
                                        name: person.name,
                                    })}
                                    disabled={busy}
                                    onClick={() =>
                                        action(
                                            path,
                                            t("Remove {name} from the group?", {
                                                name: person.name,
                                            }),
                                            true,
                                        )
                                    }
                                >
                                    <Trash2 size={16} aria-hidden="true" />
                                    {t("Remove")}
                                </button>
                            )}
                        </div>
                    </div>
                </details>
            )}
        </li>
    );
}

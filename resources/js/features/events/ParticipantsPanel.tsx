import { ParticipantRow } from "./ParticipantRow";
import { useTranslation } from "../../i18n/useTranslation";
import { useClipboard } from "../../hooks/useClipboard";
import { Form, Link, router } from "@inertiajs/react";
import { Field } from "../../components/Fields";
import type { Exchange, Person } from "../../types/exchange";
import type { EventAction } from "./useEventAction";
export default function ParticipantsPanel({
    exchange,
    inviteUrl,
    participants,
    action,
    busy,
}: {
    exchange: Exchange;
    inviteUrl: string;
    participants: Person[];
    action: EventAction;
    busy: boolean;
}) {
    const { t } = useTranslation();

    const base = `/events/${exchange.uuid}`;
    const { copied, copyError, copy } = useClipboard(inviteUrl);
    const confirmed = participants.filter((person) => person.confirmed).length;
    return (
        <>
            <section className="paper">
                <h2>
                    {exchange.drawn
                        ? t("The team is ready.")
                        : t("Invite the right people.")}
                </h2>
                {!exchange.drawn && !exchange.cancelled && (
                    <>
                        <p>
                            {t(
                                "Share this link with your team. To take part yourself, open the invitation.",
                            )}
                        </p>
                        <div className="copy-field">
                            <label className="sr-only" htmlFor="invite">
                                {t("Invitation link")}
                            </label>
                            <input
                                id="invite"
                                value={inviteUrl}
                                readOnly
                                onFocus={(e) => e.target.select()}
                            />
                            <button className="button secondary" onClick={copy}>
                                {copied ? t("Copied!") : t("Copy")}
                            </button>
                        </div>
                        {copyError && (
                            <p className="error" role="alert">
                                {t(
                                    "Select the link above to copy it manually.",
                                )}
                            </p>
                        )}
                        <Link className="inline-link" href={base}>
                            {t("Open the invitation ↗")}
                        </Link>
                    </>
                )}
                {!exchange.drawn && !exchange.cancelled && (
                    <details className="management-details">
                        <summary>{t("Invite someone by email")}</summary>
                        <Form
                            action={`${base}/invite`}
                            method="post"
                            resetOnSuccess
                        >
                            {({ errors, processing }) => (
                                <>
                                    <Field
                                        label={t("Their first name")}
                                        name="name"
                                        required
                                        maxLength={80}
                                        error={errors.name}
                                    />
                                    <Field
                                        label={t("Their email address")}
                                        name="email"
                                        type="email"
                                        required
                                        maxLength={254}
                                        error={errors.email}
                                    />
                                    <p className="fine-print">
                                        {t(
                                            "They will receive a link to confirm their participation.",
                                        )}
                                    </p>
                                    <button
                                        className="button secondary"
                                        disabled={
                                            processing ||
                                            participants.length >= 100
                                        }
                                    >
                                        {t("Send invitation")}
                                    </button>
                                </>
                            )}
                        </Form>
                    </details>
                )}
                <div className="section-heading">
                    <h3>{t("Participants")}</h3>
                    <button
                        className="text-button"
                        onClick={() =>
                            router.reload({
                                only: [
                                    "participants",
                                    "exclusions",
                                    "exchange",
                                ],
                            })
                        }
                    >
                        {t("Refresh")}
                    </button>
                    <span>
                        {t("{confirmed} / {total} confirmed", {
                            confirmed,
                            total: participants.length,
                        })}
                    </span>
                </div>
                {participants.length === 0 ? (
                    <div className="empty">
                        <p>{t("Your team hasn’t arrived yet.")}</p>
                        <span>
                            {t("Pass the link around: names will appear here.")}
                        </span>
                    </div>
                ) : (
                    <ul className="people">
                        {participants.map((p) => (
                            <ParticipantRow
                                key={p.id}
                                person={p}
                                exchange={exchange}
                                action={action}
                                busy={busy}
                            />
                        ))}
                    </ul>
                )}
            </section>
        </>
    );
}

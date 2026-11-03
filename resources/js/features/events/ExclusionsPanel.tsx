import { useTranslation } from "../../i18n/useTranslation";
import { Form, router } from "@inertiajs/react";
import type { Exchange, Person, Exclusion } from "../../types/exchange";
export default function ExclusionsPanel({
    exchange,
    participants,
    exclusions,
}: {
    exchange: Exchange;
    participants: Person[];
    exclusions: Exclusion[];
}) {
    const { t } = useTranslation();

    const base = `/events/${exchange.uuid}`;

    return (
        <>
            {!exchange.drawn && !exchange.cancelled && (
                <section className="paper exclusions">
                    <h2>{t("A little chance, a few rules.")}</h2>
                    <p>
                        {t(
                            "Prevent someone from drawing a specific name. For a mutual exclusion, add both directions.",
                        )}
                    </p>
                    {participants.length < 2 ? (
                        <p className="muted">
                            {t(
                                "Exclusions become available once two people have registered.",
                            )}
                        </p>
                    ) : (
                        <Form
                            action={`${base}/exclusions`}
                            method="post"
                            resetOnSuccess
                        >
                            {({ errors, processing }) => (
                                <>
                                    <div className="exclusion-form">
                                        <div className="field">
                                            <label htmlFor="giver_id">
                                                {t("This person…")}
                                            </label>
                                            <select
                                                id="giver_id"
                                                name="giver_id"
                                                required
                                                defaultValue=""
                                            >
                                                <option value="" disabled>
                                                    {t("Choose")}
                                                </option>
                                                {participants.map((p) => (
                                                    <option
                                                        key={p.id}
                                                        value={p.id}
                                                    >
                                                        {p.name}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                        <div className="field">
                                            <label htmlFor="recipient_id">
                                                {t("… cannot draw")}
                                            </label>
                                            <select
                                                id="recipient_id"
                                                name="recipient_id"
                                                required
                                                defaultValue=""
                                            >
                                                <option value="" disabled>
                                                    {t("Choose")}
                                                </option>
                                                {participants.map((p) => (
                                                    <option
                                                        key={p.id}
                                                        value={p.id}
                                                    >
                                                        {p.name}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                        <button
                                            className="button secondary"
                                            disabled={processing}
                                        >
                                            {t("Add")}
                                        </button>
                                    </div>
                                    {Object.values(errors).map((e, i) => (
                                        <p
                                            className="error"
                                            role="alert"
                                            key={i}
                                        >
                                            {e}
                                        </p>
                                    ))}
                                </>
                            )}
                        </Form>
                    )}
                    <ul className="exclusion-list">
                        {exclusions.map((e) => (
                            <li key={e.id}>
                                <span>
                                    {
                                        participants.find(
                                            (p) => p.id === e.giver_id,
                                        )?.name
                                    }{" "}
                                    <span aria-label={t("cannot draw")}>↛</span>{" "}
                                    {
                                        participants.find(
                                            (p) => p.id === e.recipient_id,
                                        )?.name
                                    }
                                </span>
                                <button
                                    className="text-button"
                                    onClick={() =>
                                        router.delete(
                                            `${base}/exclusions/${e.id}`,
                                        )
                                    }
                                >
                                    {t("Delete this exclusion")}
                                </button>
                            </li>
                        ))}
                    </ul>
                </section>
            )}
        </>
    );
}

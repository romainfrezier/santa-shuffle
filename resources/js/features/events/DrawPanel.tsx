import { useTranslation } from "../../i18n/useTranslation";
import { drawAvailability } from "./drawAvailability";
import { Form } from "@inertiajs/react";
import type { Exchange, Person } from "../../types/exchange";
import type { EventAction } from "./useEventAction";
export default function DrawPanel({
    exchange,
    participants,
    action,
    busy,
}: {
    exchange: Exchange;
    participants: Person[];
    action: EventAction;
    busy: boolean;
}) {
    const { t } = useTranslation();

    const base = `/events/${exchange.uuid}`;
    const { confirmed, canDraw, canReopen } = drawAvailability(
        exchange,
        participants,
    );
    return (
        <>
            <aside className="draw-panel">
                <span className="mini-seal" aria-hidden="true">
                    S
                </span>
                <h2>
                    {exchange.cancelled
                        ? t("Event cancelled.")
                        : exchange.drawn
                          ? t("Everyone has a secret.")
                          : t("The big shuffle.")}
                </h2>
                <p>
                    {exchange.cancelled
                        ? t(
                              "Access is closed. You can delete the data from the event settings.",
                          )
                        : exchange.drawn
                          ? t(
                                "Everyone receives a personal link by email. You can track opened envelopes, but recipients stay secret.",
                            )
                          : t(
                                "Is everyone here? The draw assigns a recipient to each person while respecting your exclusions.",
                            )}
                </p>
                {canReopen && (
                    <>
                        <p>
                            {t(
                                "Forgot someone? You can reopen registration until the first envelope is opened. All assignments will be cancelled.",
                            )}
                        </p>
                        <button
                            className="button secondary full"
                            disabled={busy}
                            onClick={() =>
                                action(
                                    "/reopen",
                                    t(
                                        "Cancel all assignments and reopen registration? You will need to run the draw again for everyone.",
                                    ),
                                )
                            }
                        >
                            {t("Reopen registration")}
                        </button>
                    </>
                )}
                {exchange.drawn && participants.some((p) => p.revealed) && (
                    <p className="fine-print">
                        {t(
                            "An envelope has been opened: assignments are now final.",
                        )}
                    </p>
                )}
                {!exchange.drawn && !exchange.cancelled && (
                    <Form action={`${base}/draw`} method="post">
                        {({ errors, processing }) => (
                            <>
                                <label className="checkbox">
                                    <input
                                        type="checkbox"
                                        name="confirm"
                                        value="1"
                                        required
                                    />
                                    <span>
                                        {t(
                                            "The team is complete. I understand the draw becomes final as soon as the first envelope is opened.",
                                        )}
                                    </span>
                                </label>
                                {Object.values(errors).map((e, i) => (
                                    <p key={i} className="error" role="alert">
                                        {e}
                                    </p>
                                ))}
                                <button
                                    className="button full"
                                    disabled={processing || !canDraw}
                                >
                                    {processing
                                        ? t("Chance is at work…")
                                        : t("Start the draw")}
                                </button>
                                {confirmed < 3 ? (
                                    <p className="fine-print">
                                        {t(
                                            "At least 3 confirmed participants are required.",
                                        )}
                                    </p>
                                ) : (
                                    confirmed !== participants.length && (
                                        <p className="fine-print">
                                            {t(
                                                "Wait for confirmations or remove pending registrations.",
                                            )}
                                        </p>
                                    )
                                )}
                            </>
                        )}
                    </Form>
                )}
            </aside>
        </>
    );
}

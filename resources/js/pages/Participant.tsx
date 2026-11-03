import { EventDetails } from "../components/EventDetails";
import { useTranslation } from "../i18n/useTranslation";
import type { Exchange } from "../types/exchange";
import { useRevealCelebration } from "../hooks/useRevealCelebration";
import { Form } from "@inertiajs/react";
import Layout from "../components/Layout";
import { Field, TextArea } from "../components/Fields";
type Person = { name: string; wishes: string | null };
export default function Participant({
    exchange,
    participant,
    recipient,
}: {
    exchange: Exchange;
    participant: Person;
    recipient: Person | null;
}) {
    const { t } = useTranslation();

    const markOpened = useRevealCelebration(recipient);
    return (
        <Layout title={t("My envelope")}>
            <section className="participant-page">
                <h1>
                    {exchange.drawn
                        ? t("{name}, a secret for you.", {
                              name: participant.name,
                          })
                        : t("You’re part of the fun, {name}.", {
                              name: participant.name,
                          })}
                </h1>
                <p>{exchange.name}</p>
                <EventDetails exchange={exchange} />
                {exchange.drawn ? (
                    recipient ? (
                        <div className="recipient paper" aria-live="polite">
                            <p>{t("This year, you’ll be making a gift for")}</p>
                            <h2>{recipient.name}</h2>
                            <div className="wishes">
                                <h3>{t("Some ideas for the gift")}</h3>
                                <p>
                                    {recipient.wishes ||
                                        t(
                                            "No special wishes. Find a little something that will make them smile.",
                                        )}
                                </p>
                            </div>
                            <p className="fine-print">
                                {t("Keep the secret until gift day.")}
                            </p>
                        </div>
                    ) : (
                        <Form
                            action={`/events/${exchange.uuid}/reveal`}
                            onBefore={markOpened}
                            method="post"
                        >
                            {({ processing }) => (
                                <button
                                    className="reveal-envelope"
                                    disabled={processing}
                                >
                                    <span
                                        className="envelope-lines"
                                        aria-hidden="true"
                                    />
                                    <span className="seal" aria-hidden="true">
                                        S
                                    </span>
                                    <strong>
                                        {processing
                                            ? t("Your envelope is opening…")
                                            : t("Open my envelope")}
                                    </strong>
                                    <span>
                                        {t("Your recipient is hiding here.")}
                                    </span>
                                </button>
                            )}
                        </Form>
                    )
                ) : (
                    <div className="paper participant-wait">
                        <h2>{t("Chance is getting ready.")}</h2>
                        <p>
                            {t(
                                "Your participation is confirmed. An email will let you know when your envelope is ready.",
                            )}
                        </p>
                        <Form
                            action={`/events/${exchange.uuid}/me`}
                            method="patch"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <Field
                                        label={t("Your first name or nickname")}
                                        name="name"
                                        defaultValue={participant.name}
                                        maxLength={80}
                                        required
                                        error={errors.name}
                                    />
                                    <TextArea
                                        label={t("Your gift wishes (optional)")}
                                        name="wishes"
                                        defaultValue={participant.wishes ?? ""}
                                        maxLength={1000}
                                        error={errors.wishes}
                                    />
                                    <button
                                        className="button secondary"
                                        disabled={processing}
                                    >
                                        {processing
                                            ? t("Saving…")
                                            : t("Save my wishes")}
                                    </button>
                                </>
                            )}
                        </Form>
                    </div>
                )}
            </section>
        </Layout>
    );
}

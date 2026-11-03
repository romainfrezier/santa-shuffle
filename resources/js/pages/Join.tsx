import { EventDetails } from "../components/EventDetails";
import { useTranslation } from "../i18n/useTranslation";
import type { Exchange } from "../types/exchange";
import { Form, Link } from "@inertiajs/react";
import Layout from "../components/Layout";
import { Field, TextArea } from "../components/Fields";
export default function Join({
    exchange,
    count,
}: {
    exchange: Exchange;
    count: number;
}) {
    const { t } = useTranslation();

    return (
        <Layout title={exchange.name}>
            <div className="form-layout">
                <aside>
                    <h1>{exchange.name}</h1>
                    <EventDetails exchange={exchange} />
                    <p>
                        {exchange.message ||
                            t(
                                "A team, some gifts, and a secret to keep. All that’s missing is you.",
                            )}
                    </p>
                    <p className="muted">
                        {t(
                            count === 1
                                ? "{count} confirmed participant"
                                : "{count} confirmed participants",
                            { count },
                        )}
                    </p>
                </aside>
                <section className="paper">
                    <h2>
                        {exchange.drawn
                            ? t("The envelopes are on their way.")
                            : t("Are you joining the fun?")}
                    </h2>
                    {exchange.drawn ? (
                        <>
                            <p>
                                {t(
                                    "The draw is complete. Open the personal link in your email to discover your recipient.",
                                )}
                            </p>
                            <Link href="/" className="button">
                                {t("Back to home")}
                            </Link>
                        </>
                    ) : (
                        <Form
                            action={`/events/${exchange.uuid}/join`}
                            method="post"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <Field
                                        label={t("Your first name or nickname")}
                                        name="name"
                                        maxLength={80}
                                        autoComplete="given-name"
                                        required
                                        error={errors.name}
                                    />
                                    <Field
                                        label={t("Your email address")}
                                        name="email"
                                        type="email"
                                        maxLength={254}
                                        autoComplete="email"
                                        required
                                        error={errors.email}
                                    />
                                    <TextArea
                                        label={t("A few gift ideas (optional)")}
                                        name="wishes"
                                        maxLength={1000}
                                        placeholder={t(
                                            "Dark chocolate, a book, something handmade…",
                                        )}
                                        error={errors.wishes}
                                    />
                                    <button
                                        className="button full"
                                        disabled={processing}
                                    >
                                        {processing
                                            ? t("Sending…")
                                            : t("Send me my invitation")}
                                    </button>
                                    <p className="fine-print">
                                        {t(
                                            "Confirm your email to take part. Only the person giving you a gift will see your wishes.",
                                        )}
                                    </p>
                                    <p className="fine-print">
                                        {t(
                                            "Already registered? Use the same address to receive your link again.",
                                        )}
                                    </p>
                                </>
                            )}
                        </Form>
                    )}
                </section>
            </div>
        </Layout>
    );
}

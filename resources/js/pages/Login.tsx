import { useTranslation } from "../i18n/useTranslation";
import { Form } from "@inertiajs/react";
import Layout from "../components/Layout";
import { Field } from "../components/Fields";
export default function Login({
    pendingEmail,
}: {
    pendingEmail: string | null;
}) {
    const { t } = useTranslation();

    return (
        <Layout title={t("My organizer space")}>
            <div className="form-layout">
                <aside>
                    <span className="eyebrow">{t("YOUR SECRET CORNER")}</span>
                    <h1>
                        {t("One code.")}
                        <br />
                        {t("And you’re in.")}
                    </h1>
                    <p>
                        {t(
                            "Find all your Secret Santa events with your email address. No password to remember.",
                        )}
                    </p>
                    <div className="aside-note">
                        <strong>{t("Keep this code to yourself.")}</strong>
                        <p>
                            {t(
                                "It opens your organizer space, not your guests’ space.",
                            )}
                        </p>
                    </div>
                </aside>
                <section className="paper">
                    {pendingEmail && (
                        <>
                            <h2>{t("Time to check your email.")}</h2>
                            <p>
                                {t(
                                    "Code sent to {email}. It is valid for 10 minutes in this browser.",
                                    { email: pendingEmail },
                                )}
                            </p>
                            <Form action="/login/verify" method="post">
                                {({ errors, processing }) => (
                                    <>
                                        <Field
                                            label={t("6-digit code")}
                                            name="code"
                                            inputMode="numeric"
                                            autoComplete="one-time-code"
                                            pattern="[0-9]{6}"
                                            minLength={6}
                                            maxLength={6}
                                            required
                                            error={errors.code}
                                        />
                                        <button
                                            className="button full"
                                            disabled={processing}
                                        >
                                            {processing
                                                ? t("Verifying…")
                                                : t("Open my space")}
                                        </button>
                                    </>
                                )}
                            </Form>
                            <hr />
                        </>
                    )}
                    <h2>
                        {pendingEmail ? t("Another code?") : t("Welcome home.")}
                    </h2>
                    <Form action="/login/code" method="post">
                        {({ errors, processing }) => (
                            <>
                                <Field
                                    label={t("Your email address")}
                                    name="email"
                                    type="email"
                                    autoComplete="email"
                                    defaultValue={pendingEmail ?? ""}
                                    maxLength={254}
                                    required
                                    error={errors.email}
                                />
                                <button
                                    className="button full"
                                    disabled={processing}
                                >
                                    {processing
                                        ? t("Sending code…")
                                        : pendingEmail
                                          ? t("Send another code")
                                          : t("Send me my code")}
                                </button>
                                <p className="fine-print">
                                    {pendingEmail
                                        ? t(
                                              "Wait one minute between requests. Only the latest code works. You can also change the address above.",
                                          )
                                        : t(
                                              "First visit? The same code opens your space. Check your spam folder too.",
                                          )}
                                </p>
                            </>
                        )}
                    </Form>
                </section>
            </div>
        </Layout>
    );
}

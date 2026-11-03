import { CurrencyField } from "../components/CurrencyField";
import { useTranslation } from "../i18n/useTranslation";
import { Form, Link, usePage } from "@inertiajs/react";
import Layout from "../components/Layout";
import { Field, TextArea } from "../components/Fields";
export default function Create() {
    const { t } = useTranslation();

    const { organizerEmail } = usePage<{ organizerEmail: string }>().props;
    return (
        <Layout title={t("Create my Secret Santa")}>
            <div className="form-layout">
                <aside>
                    <Link className="back" href="/">
                        {t("← Back to home")}
                    </Link>
                    <h1>
                        {t("It all starts")}
                        <br />
                        {t("with an invitation.")}
                    </h1>
                    <p>
                        {t(
                            "Bring your favorite people together. We’ll take care of the draw.",
                        )}
                    </p>
                    <div className="aside-note">
                        <strong>{t("One little detail that matters.")}</strong>
                        <p>
                            {t(
                                "Organizing? You can take part too, using your group’s invitation link.",
                            )}
                        </p>
                    </div>
                </aside>
                <section className="paper">
                    <h2>{t("Your Secret Santa")}</h2>
                    <Form action="/events" method="post">
                        {({ errors, processing }) => (
                            <>
                                <Field
                                    label={t("Group name")}
                                    name="name"
                                    placeholder={t("The team’s Christmas")}
                                    maxLength={100}
                                    required
                                    error={errors.name}
                                />
                                <div className="form-row">
                                    <Field
                                        label={t("Gift date")}
                                        name="gift_date"
                                        type="date"
                                        required
                                        error={errors.gift_date}
                                    />
                                    <Field
                                        label={t("Budget per gift")}
                                        name="budget"
                                        type="number"
                                        min={1}
                                        max={10000}
                                        step={1}
                                        defaultValue={20}
                                        required
                                        error={errors.budget}
                                    />
                                    <CurrencyField error={errors.currency} />
                                </div>
                                <p className="fine-print">
                                    {t(
                                        "Organized with {email}. Notifications will be sent to this verified address.",
                                        { email: organizerEmail },
                                    )}
                                </p>
                                <TextArea
                                    label={t("A note for the team (optional)")}
                                    name="message"
                                    maxLength={1000}
                                    placeholder={t(
                                        "Shall we exchange gifts over something delicious?",
                                    )}
                                    error={errors.message}
                                />
                                <button
                                    className="button full"
                                    disabled={processing}
                                >
                                    {processing
                                        ? t("Creating…")
                                        : t("Create my Secret Santa")}{" "}
                                    <span aria-hidden="true">↗</span>
                                </button>
                                <p className="fine-print">
                                    {t(
                                        "From 3 to 100 participants. Everyone gives and receives a gift.",
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

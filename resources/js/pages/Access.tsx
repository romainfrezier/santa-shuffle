import { useTranslation } from "../i18n/useTranslation";
import { Form } from "@inertiajs/react";
import Layout from "../components/Layout";
export default function Access({
    eventName,
    action,
    organizer,
}: {
    eventName: string;
    action: string;
    organizer: boolean;
}) {
    const { t } = useTranslation();

    return (
        <Layout title={t("Your private invitation")}>
            <section className="centered paper">
                <h1>
                    {organizer
                        ? t("Welcome behind the scenes.")
                        : t("A place is waiting for you.")}
                </h1>
                <p>{eventName}</p>
                <Form action={action} method="post">
                    {({ processing }) => (
                        <button className="button" disabled={processing}>
                            {processing
                                ? t("Opening…")
                                : organizer
                                  ? t("Manage my Secret Santa")
                                  : t("Open my personal space")}
                        </button>
                    )}
                </Form>
                <p className="fine-print">
                    {t(
                        "This link is private. Keep it to come back whenever you like.",
                    )}
                </p>
            </section>
        </Layout>
    );
}

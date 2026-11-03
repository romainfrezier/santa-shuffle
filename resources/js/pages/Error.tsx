import { useTranslation } from "../i18n/useTranslation";
import { Link } from "@inertiajs/react";
import Layout from "../components/Layout";
export default function Error({ status }: { status: number }) {
    const { t } = useTranslation();

    const messages: Record<number, [string, string]> = {
        403: [
            t("This envelope is private."),
            t(
                "Organizing? Log in with your email code. Participating? Open your personal link.",
            ),
        ],
        404: [
            t("This invitation could not be found."),
            t("Check that you opened the complete link sent by email."),
        ],
        409: [
            t("The group has changed."),
            t(
                "The draw may already have happened. Open your personal link again to see its current status.",
            ),
        ],
        410: [
            t("This Secret Santa is over."),
            t("Links expire 30 days after the gift date. See you next year!"),
        ],
        419: [
            t("Your session has expired."),
            t("Go back home or open your personal link, then try again."),
        ],
        429: [
            t("Just a moment…"),
            t(
                "You’ve made several requests in a short time. Wait before trying again and check your inbox.",
            ),
        ],
    };
    const [title, text] = messages[status] ?? [
        t("A little hiccup."),
        t("We couldn’t complete this request. Please try again in a moment."),
    ];
    return (
        <Layout title={title}>
            <section className="centered paper">
                <h1>{title}</h1>
                <p>{text}</p>
                <Link className="button" href="/">
                    {t("Back to home")}
                </Link>
            </section>
        </Layout>
    );
}

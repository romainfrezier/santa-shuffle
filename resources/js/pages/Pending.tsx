import { useTranslation } from "../i18n/useTranslation";
import { Link } from "@inertiajs/react";
import Layout, { Mark } from "../components/Layout";
import type { Exchange } from "../types/exchange";
export default function Pending({ exchange }: { exchange: Exchange }) {
    const { t } = useTranslation();

    return (
        <Layout title={t("Check your inbox")}>
            <section className="centered paper">
                <span className="mail-symbol" aria-hidden="true">
                    <Mark />
                </span>
                <h1>
                    {t("An invitation")}
                    <br />
                    {t("in your inbox.")}
                </h1>
                <p>
                    {t(
                        "If your address is valid, you’ll receive your personal link for {name}.",
                        { name: exchange.name },
                    )}
                </p>
                <p>
                    {t(
                        "Open it and confirm your participation. Remember to check your spam folder.",
                    )}
                </p>
                <Link
                    className="button secondary"
                    href={`/events/${exchange.uuid}`}
                >
                    {t("Back to the invitation")}
                </Link>
            </section>
        </Layout>
    );
}

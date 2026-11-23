import { useTranslation } from "../i18n/useTranslation";
import { Form } from "@inertiajs/react";
import Layout from "../components/Layout";
import { useClipboard } from "../hooks/useClipboard";
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
    const { copy, copied, copyError } = useClipboard(action);

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
                        "Bookmark this page on a personal device or keep a copy of its address. Anyone with this private link can open your envelope.",
                    )}
                </p>
                <button
                    className="button secondary"
                    type="button"
                    onClick={copy}
                    aria-live="polite"
                >
                    {copied ? t("Copied!") : t("Copy my private link")}
                </button>
                {copyError && (
                    <p role="status">
                        {t("Copy the address from your browser’s address bar.")}
                    </p>
                )}
            </section>
        </Layout>
    );
}

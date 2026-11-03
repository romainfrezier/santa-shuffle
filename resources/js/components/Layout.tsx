import { LanguageSwitcher } from "./LanguageSwitcher";
import { useTranslation } from "../i18n/useTranslation";
import { Head, Link, usePage } from "@inertiajs/react";
import { useEffect, type PropsWithChildren } from "react";
import { Toaster, toast } from "sonner";
export function Mark() {
    return (
        <svg aria-hidden="true" viewBox="0 0 32 32" fill="none">
            <path
                d="M5 10h22v17H5zM5 10l11 9 11-9M11 9c-7-9 7-8 5 1 2-9 16-10 5-1"
                stroke="currentColor"
                strokeWidth="1.8"
                strokeLinejoin="round"
                strokeLinecap="round"
            />
        </svg>
    );
}
export default function Layout({
    title,
    children,
    home = false,
}: PropsWithChildren<{ title: string; home?: boolean }>) {
    const { t, locale } = useTranslation();

    const { notice, organizerEmail, hasAccess } = usePage<{
        notice?: string;
        organizerEmail?: string;
        hasAccess?: boolean;
    }>().props;
    useEffect(() => {
        document.documentElement.lang = locale;
    }, [locale]);
    useEffect(() => {
        if (notice) toast.success(notice);
    }, [notice]);
    return (
        <div className={home ? "site landing" : "site"}>
            <Head title={title} />
            <a className="skip" href="#main">
                {t("Skip to content")}
            </a>
            <header className="header">
                <Link className="brand" href="/">
                    <Mark />
                    <span>
                        Santa<span className="brand-light">Shuffle</span>
                    </span>
                </Link>
                <nav aria-label={t("Main navigation")}>
                    <a href="/#how">{t("How it works")}</a>
                    <Link href={organizerEmail ? "/my-events" : "/login"}>
                        {organizerEmail ? t("My events") : t("Log in")}
                    </Link>
                    {home ? (
                        <Link className="nav-button" href="/create">
                            {t("Organize a Secret Santa")}{" "}
                            <span aria-hidden="true">↗</span>
                        </Link>
                    ) : hasAccess ? (
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            className="text-button"
                        >
                            {t("Log out")}
                        </Link>
                    ) : null}
                    <LanguageSwitcher />
                </nav>
            </header>
            <Toaster
                position="bottom-right"
                richColors
                closeButton
                containerAriaLabel={t("Notifications")}
                toastOptions={{ closeButtonAriaLabel: t("Close notification") }}
            />
            <main id="main">{children}</main>
            <footer>
                <Link className="brand" href="/">
                    <Mark />
                    Santa Shuffle
                </Link>
                <span>{t("One gift. One secret. A lovely moment.")}</span>
                <span className="footer-note">
                    {t("Open source & self-hosted")}
                </span>
            </footer>
        </div>
    );
}

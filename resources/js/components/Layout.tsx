import { Head, Link, usePage } from "@inertiajs/react";
import type { PropsWithChildren } from "react";
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
    const { notice } = usePage<{ notice?: string }>().props;
    return (
        <div className={home ? "site landing" : "site"}>
            <Head title={title} />
            <a className="skip" href="#main">
                Aller au contenu
            </a>
            <header className="header">
                <Link className="brand" href="/">
                    <Mark />
                    <span>
                        Santa<span className="brand-light">Shuffle</span>
                    </span>
                </Link>
                <nav aria-label="Navigation principale">
                    <a href="/#how">Comment ça marche</a>
                    {home ? (
                        <Link className="nav-button" href="/create">
                            Organiser un Secret Santa{" "}
                            <span aria-hidden="true">↗</span>
                        </Link>
                    ) : (
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            className="text-button"
                        >
                            Fermer ma session
                        </Link>
                    )}
                </nav>
            </header>
            {notice && (
                <div className="notice" role="status">
                    {notice}
                </div>
            )}
            <main id="main">{children}</main>
            <footer>
                <Link className="brand" href="/">
                    <Mark />
                    Santa Shuffle
                </Link>
                <span>Un cadeau. Un secret. Un bon moment.</span>
                <span className="footer-note">Libre & auto-hébergeable</span>
            </footer>
        </div>
    );
}

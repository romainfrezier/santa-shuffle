import { useTranslation } from "../i18n/useTranslation";
import { Link } from "@inertiajs/react";
import { useShuffleDemo } from "../hooks/useShuffleDemo";
import {
    ArrowUpRight,
    Shuffle,
    Heart,
    LockKeyhole,
    Sparkles,
} from "lucide-react";
import Layout from "../components/Layout";
import type { Exchange } from "../types/exchange";
export default function Home({
    envelopes,
}: {
    envelopes: Pick<Exchange, "uuid" | "name">[];
}) {
    const { t } = useTranslation();

    const { scene, shuffle, name } = useShuffleDemo();
    return (
        <Layout title={t("Your team’s Secret Santa")} home>
            <section className="festive-hero">
                <div className="hero-copy">
                    <span className="eyebrow">
                        <Sparkles size={16} aria-hidden="true" />
                        {t("LET CHANCE MAKE SOMEONE’S DAY.")}
                    </span>
                    <h1>
                        {t("Little gifts.")}
                        <br />
                        <em>{t("Big smiles.")}</em>
                    </h1>
                    <p>
                        {t("Bring your favorite people together.")}
                        <br />
                        {t("We shuffle the names. You make someone happy.")}
                    </p>
                    {envelopes.length ? (
                        <ul className="saved-envelopes">
                            {envelopes.map((event) => (
                                <li key={event.uuid}>
                                    <Link
                                        className="button"
                                        href={`/events/${event.uuid}/me`}
                                    >
                                        {t("My envelope for {name}", {
                                            name: event.name,
                                        })}
                                        <ArrowUpRight
                                            size={20}
                                            aria-hidden="true"
                                        />
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <>
                            <Link className="button" href="/create">
                                {t("Create my Secret Santa")}{" "}
                                <ArrowUpRight size={20} aria-hidden="true" />
                            </Link>
                            <details className="invitation-help">
                                <summary>{t("Already taking part?")}</summary>
                                <p>
                                    {t(
                                        "Your envelope appears here while you’re signed in on this browser. Otherwise, open your saved private link or the one in your invitation email.",
                                    )}
                                </p>
                            </details>
                        </>
                    )}
                </div>
                <div className="shuffle-scene" ref={scene}>
                    <span className="scene-caption">
                        {t("A little happiness, tucked in an envelope.")}
                    </span>
                    <div className="shuffle-card card-back" aria-hidden="true">
                        <Heart size={30} />
                        <span>
                            {t("A little thought,")}
                            <br />
                            {t("a big difference.")}
                        </span>
                    </div>
                    <div
                        className="shuffle-card card-middle"
                        aria-hidden="true"
                    >
                        <span>
                            {t("FOR SOMEONE")}
                            <br />
                            {t("WONDERFUL.")}
                        </span>
                        <Sparkles size={32} />
                    </div>
                    <div className="shuffle-card card-front">
                        <div className="postmark" aria-hidden="true">
                            SANTA
                            <br />
                            POST
                        </div>
                        <span className="card-label">
                            {t("What if you made someone smile…")}
                        </span>
                        <strong aria-live="polite">{name} ?</strong>
                        <span className="card-secret">{t("From… shhh.")}</span>
                        <span className="seal" aria-hidden="true">
                            S
                        </span>
                    </div>
                    <div className="demo-control">
                        <button className="shuffle-button" onClick={shuffle}>
                            <Shuffle size={18} aria-hidden="true" />
                            {t("A little magic?")}
                        </button>
                        <span>{t("Just a demo, just for fun.")}</span>
                    </div>
                </div>
            </section>
            <div className="promise-strip">
                <span>
                    <Heart size={18} aria-hidden="true" />
                    {t("For your favorite people")}
                </span>
                <span>
                    <LockKeyhole size={18} aria-hidden="true" />
                    {t("Everyone has a little secret")}
                </span>
                <span>
                    <Sparkles size={18} aria-hidden="true" />
                    {t("No account needed for guests")}
                </span>
            </div>
            <section className="how" id="how">
                <div>
                    <h2>{t("How it works")}</h2>
                    <p>
                        {t(
                            "Everyone gives one gift and receives one. Only the giver sees who their gift is for.",
                        )}
                    </p>
                </div>
                <ol>
                    <li>
                        <span className="step-number">1</span>
                        <div>
                            <h3>{t("Create the event")}</h3>
                            <p>
                                {t(
                                    "Sign in with an email code, then set the gift date and budget. To take part yourself, join through the group invitation too.",
                                )}
                            </p>
                        </div>
                    </li>
                    <li>
                        <span className="step-number">2</span>
                        <div>
                            <h3>{t("Pass the invitation around")}</h3>
                            <p>
                                {t(
                                    "Share the group link. Each guest enters a name and email, then opens their private invitation to confirm. Gift wishes are optional.",
                                )}
                            </p>
                        </div>
                    </li>
                    <li>
                        <span className="step-number">3</span>
                        <div>
                            <h3>{t("Draw and open the envelopes")}</h3>
                            <p>
                                {t(
                                    "Once everyone has confirmed and at least three people are taking part, the organizer starts the draw. Each person opens their own envelope to find out who to surprise.",
                                )}
                            </p>
                        </div>
                    </li>
                </ol>
            </section>
            <section className="closing">
                <h2>
                    {t("The surprise is yours.")}
                    <br />
                    {t("Your data too.")}
                </h2>
                <p>
                    {t(
                        "Santa Shuffle is an open source project you can host yourself.",
                    )}
                    <br />
                    {t(
                        "No ads. No advertising trackers. Just the joy of giving.",
                    )}
                </p>
                <Link className="button" href="/create">
                    {t("Bring my team together")}
                    <span aria-hidden="true">↗</span>
                </Link>
            </section>
        </Layout>
    );
}

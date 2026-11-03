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
export default function Home() {
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
                    <Link className="button" href="/create">
                        {t("Create my Secret Santa")}{" "}
                        <ArrowUpRight size={20} aria-hidden="true" />
                    </Link>
                    <span className="hero-note">
                        {t("A name, a date, a budget. Let the magic begin.")}
                    </span>
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
                    <h2>
                        {t("You bring the team.")}
                        <br />
                        {t("We shuffle the names.")}
                    </h2>
                    <p>
                        {t(
                            "The hardest part? Keeping the secret until the big day.",
                        )}
                    </p>
                </div>
                <ol>
                    <li>
                        <span className="step-number">1</span>
                        <div>
                            <h3>{t("Set the scene")}</h3>
                            <p>
                                {t(
                                    "A name, a date, a budget. Your event is ready.",
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
                                    "One link for everyone. Each person confirms and shares their wishes.",
                                )}
                            </p>
                        </div>
                    </li>
                    <li>
                        <span className="step-number">3</span>
                        <div>
                            <h3>{t("Let chance decide")}</h3>
                            <p>
                                {t(
                                    "Start the draw. Everyone opens their private envelope and discovers who to surprise.",
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

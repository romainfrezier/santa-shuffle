import { Link } from "@inertiajs/react";
import Layout from "../components/Layout";
export default function Home() {
    return (
        <Layout title="Le Secret Santa de votre équipe" home>
            <section className="hero">
                <div className="hero-copy">
                    <h1>
                        Les petits cadeaux font
                        <br className="desktop-break" /> les grandes équipes.
                    </h1>
                    <p>
                        Un soupçon de hasard, beaucoup d’attention.
                        <br />
                        Organisez votre Secret Santa et laissez la surprise
                        faire le reste.
                    </p>
                    <Link className="button cream" href="/create">
                        Créer mon Secret Santa <span aria-hidden="true">↗</span>
                    </Link>
                    <span className="hero-note">
                        Sans compte pour vos invités. Le secret est bien gardé.
                    </span>
                </div>
                <div className="hero-envelope" aria-hidden="true">
                    <div className="gift-tag">
                        <span>À quelqu’un de chouette.</span>
                        <strong>De la part de… chut.</strong>
                        <span className="tag-star">✳</span>
                    </div>
                    <div className="envelope-face">
                        <span className="seal">S</span>
                    </div>
                </div>
                <div className="hero-foot">
                    <span>
                        Pour les collègues, les amis, les grandes tablées.
                    </span>
                    <span>Et tous les petits budgets.</span>
                </div>
            </section>
            <section className="how" id="how">
                <div>
                    <h2>
                        Vous réunissez l’équipe.
                        <br />
                        On mélange les noms.
                    </h2>
                    <p>Le plus difficile ? Garder le secret jusqu’au jour J.</p>
                </div>
                <ol>
                    <li>
                        <span className="step-number">1</span>
                        <div>
                            <h3>Posez le décor</h3>
                            <p>
                                Un nom, une date, un budget. Votre événement est
                                prêt.
                            </p>
                        </div>
                    </li>
                    <li>
                        <span className="step-number">2</span>
                        <div>
                            <h3>Faites passer l’invitation</h3>
                            <p>
                                Un lien pour tout le monde. Chacun confirme sa
                                participation et glisse ses envies.
                            </p>
                        </div>
                    </li>
                    <li>
                        <span className="step-number">3</span>
                        <div>
                            <h3>Laissez le hasard choisir</h3>
                            <p>
                                Lancez le tirage. Chacun ouvre son enveloppe
                                privée et découvre à qui faire plaisir.
                            </p>
                        </div>
                    </li>
                </ol>
            </section>
            <section className="closing">
                <h2>
                    La surprise est à vous.
                    <br />
                    Vos données aussi.
                </h2>
                <p>
                    Santa Shuffle est un projet open source à héberger chez
                    vous.
                    <br />
                    Pas de publicité, pas de suivi publicitaire. Juste le
                    plaisir d’offrir.
                </p>
                <Link className="button" href="/create">
                    Réunir mon équipe <span aria-hidden="true">↗</span>
                </Link>
            </section>
        </Layout>
    );
}

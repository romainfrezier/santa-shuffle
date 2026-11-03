import { Form, Link } from "@inertiajs/react";
import Layout from "../components/Layout";
import { Details, Field, TextArea, type Exchange } from "../components/Fields";
export default function Join({
    exchange,
    count,
}: {
    exchange: Exchange;
    count: number;
}) {
    return (
        <Layout title={exchange.name}>
            <div className="form-layout">
                <aside>
                    <h1>{exchange.name}</h1>
                    <Details exchange={exchange} />
                    <p>
                        {exchange.message ||
                            "Une équipe, des cadeaux et un secret à garder. Il ne manque plus que vous."}
                    </p>
                    <p className="muted">
                        {count}{" "}
                        {count > 1
                            ? "participations confirmées"
                            : "participation confirmée"}
                    </p>
                </aside>
                <section className="paper">
                    <h2>
                        {exchange.drawn
                            ? "Les enveloppes sont parties."
                            : "Vous êtes de la fête ?"}
                    </h2>
                    {exchange.drawn ? (
                        <>
                            <p>
                                Le tirage a eu lieu. Ouvrez le lien personnel
                                reçu par e-mail pour découvrir votre
                                destinataire.
                            </p>
                            <Link href="/" className="button">
                                Retour à l’accueil
                            </Link>
                        </>
                    ) : (
                        <Form
                            action={`/events/${exchange.uuid}/join`}
                            method="post"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <Field
                                        label="Votre prénom ou petit nom"
                                        name="name"
                                        maxLength={80}
                                        autoComplete="given-name"
                                        required
                                        error={errors.name}
                                    />
                                    <Field
                                        label="Votre adresse e-mail"
                                        name="email"
                                        type="email"
                                        maxLength={254}
                                        autoComplete="email"
                                        required
                                        error={errors.email}
                                    />
                                    <TextArea
                                        label="Quelques idées pour vous faire plaisir (facultatif)"
                                        name="wishes"
                                        maxLength={1000}
                                        placeholder="Du chocolat noir, un livre, quelque chose fait main…"
                                        error={errors.wishes}
                                    />
                                    <button
                                        className="button full"
                                        disabled={processing}
                                    >
                                        {processing
                                            ? "Envoi en cours…"
                                            : "Recevoir mon invitation"}
                                    </button>
                                    <p className="fine-print">
                                        Confirmez votre e-mail pour participer.
                                        Vos envies seront visibles uniquement
                                        par la personne qui vous offre un
                                        cadeau.
                                    </p>
                                    <p className="fine-print">
                                        Déjà inscrit ? Utilisez la même adresse
                                        pour recevoir à nouveau votre lien.
                                    </p>
                                </>
                            )}
                        </Form>
                    )}
                </section>
            </div>
        </Layout>
    );
}

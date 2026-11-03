import { Form, Link } from "@inertiajs/react";
import Layout from "../components/Layout";
import { Field, TextArea } from "../components/Fields";
export default function Create() {
    return (
        <Layout title="Créer mon Secret Santa">
            <div className="form-layout">
                <aside>
                    <Link className="back" href="/">
                        ← Retour à l’accueil
                    </Link>
                    <h1>
                        Tout commence
                        <br />
                        par une invitation.
                    </h1>
                    <p>
                        Réunissez vos personnes préférées. On s’occupe du
                        hasard.
                    </p>
                    <div className="aside-note">
                        <strong>Un petit détail qui compte.</strong>
                        <p>
                            Vous êtes organisateur ? Vous pourrez aussi
                            participer, en utilisant le lien d’invitation de
                            votre groupe.
                        </p>
                    </div>
                </aside>
                <section className="paper">
                    <h2>Votre Secret Santa</h2>
                    <Form action="/events" method="post">
                        {({ errors, processing }) => (
                            <>
                                <Field
                                    label="Nom du groupe"
                                    name="name"
                                    placeholder="Le Noël de l’équipe"
                                    maxLength={100}
                                    required
                                    error={errors.name}
                                />
                                <div className="form-row">
                                    <Field
                                        label="Date des cadeaux"
                                        name="gift_date"
                                        type="date"
                                        required
                                        error={errors.gift_date}
                                    />
                                    <Field
                                        label="Budget par cadeau (€)"
                                        name="budget"
                                        type="number"
                                        min={1}
                                        max={10000}
                                        step={1}
                                        defaultValue={20}
                                        required
                                        error={errors.budget}
                                    />
                                </div>
                                <Field
                                    label="Votre adresse e-mail"
                                    name="organizer_email"
                                    type="email"
                                    autoComplete="email"
                                    maxLength={254}
                                    required
                                    placeholder="vous@exemple.fr"
                                    error={errors.organizer_email}
                                />
                                <p className="field-hint">
                                    Votre lien privé de gestion sera envoyé à
                                    cette adresse.
                                </p>
                                <TextArea
                                    label="Un petit mot pour l’équipe (facultatif)"
                                    name="message"
                                    maxLength={1000}
                                    placeholder="On échange nos cadeaux autour d’un bon goûter ?"
                                    error={errors.message}
                                />
                                <button
                                    className="button full"
                                    disabled={processing}
                                >
                                    {processing
                                        ? "Création en cours…"
                                        : "Créer mon Secret Santa"}{" "}
                                    <span aria-hidden="true">↗</span>
                                </button>
                                <p className="fine-print">
                                    De 3 à 100 participants. Chacun offre et
                                    reçoit un cadeau.
                                </p>
                            </>
                        )}
                    </Form>
                </section>
            </div>
        </Layout>
    );
}

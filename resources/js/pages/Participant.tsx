import { Form } from "@inertiajs/react";
import Layout from "../components/Layout";
import { Details, Field, TextArea, type Exchange } from "../components/Fields";
type Person = { name: string; wishes: string | null };
export default function Participant({
    exchange,
    participant,
    recipient,
}: {
    exchange: Exchange;
    participant: Person;
    recipient: Person | null;
}) {
    return (
        <Layout title="Mon enveloppe">
            <section className="participant-page">
                <h1>
                    {exchange.drawn
                        ? `${participant.name}, un secret pour vous.`
                        : `Vous êtes de la fête, ${participant.name}.`}
                </h1>
                <p>{exchange.name}</p>
                <Details exchange={exchange} />
                {exchange.drawn ? (
                    recipient ? (
                        <div className="recipient paper" aria-live="polite">
                            <p>Cette année, vous allez faire plaisir à</p>
                            <h2>{recipient.name}</h2>
                            <div className="wishes">
                                <h3>Quelques pistes pour le cadeau</h3>
                                <p>
                                    {recipient.wishes ||
                                        "Pas d’envie particulière. À vous de trouver la petite attention qui fera sourire."}
                                </p>
                            </div>
                            <p className="fine-print">
                                Gardez le secret jusqu’au jour des cadeaux.
                            </p>
                        </div>
                    ) : (
                        <Form
                            action={`/events/${exchange.uuid}/reveal`}
                            method="post"
                        >
                            {({ processing }) => (
                                <button
                                    className="reveal-envelope"
                                    disabled={processing}
                                >
                                    <span
                                        className="envelope-lines"
                                        aria-hidden="true"
                                    />
                                    <span className="seal" aria-hidden="true">
                                        S
                                    </span>
                                    <strong>
                                        {processing
                                            ? "Votre enveloppe s’ouvre…"
                                            : "Ouvrir mon enveloppe"}
                                    </strong>
                                    <span>
                                        Votre destinataire se cache ici.
                                    </span>
                                </button>
                            )}
                        </Form>
                    )
                ) : (
                    <div className="paper participant-wait">
                        <h2>Le hasard se prépare.</h2>
                        <p>
                            Votre participation est confirmée. Un e-mail vous
                            préviendra quand votre enveloppe sera prête.
                        </p>
                        <Form
                            action={`/events/${exchange.uuid}/me`}
                            method="patch"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <Field
                                        label="Votre prénom ou petit nom"
                                        name="name"
                                        defaultValue={participant.name}
                                        maxLength={80}
                                        required
                                        error={errors.name}
                                    />
                                    <TextArea
                                        label="Vos envies de cadeaux (facultatif)"
                                        name="wishes"
                                        defaultValue={participant.wishes ?? ""}
                                        maxLength={1000}
                                        error={errors.wishes}
                                    />
                                    <button
                                        className="button secondary"
                                        disabled={processing}
                                    >
                                        {processing
                                            ? "Enregistrement…"
                                            : "Enregistrer mes envies"}
                                    </button>
                                </>
                            )}
                        </Form>
                    </div>
                )}
            </section>
        </Layout>
    );
}

import { Form, Link, router } from "@inertiajs/react";
import { useState } from "react";
import Layout from "../components/Layout";
import { Details, type Exchange } from "../components/Fields";
type Person = {
    id: number;
    name: string;
    confirmed: boolean;
    revealed: boolean;
};
type Exclusion = { id: number; giver_id: number; recipient_id: number };
export default function Manage({
    exchange,
    inviteUrl,
    participants,
    exclusions,
}: {
    exchange: Exchange;
    inviteUrl: string;
    participants: Person[];
    exclusions: Exclusion[];
}) {
    const [copied, setCopied] = useState(false);
    const [copyError, setCopyError] = useState(false);
    const base = `/events/${exchange.uuid}`;
    const confirmed = participants.filter((p) => p.confirmed).length;
    async function copy() {
        try {
            await navigator.clipboard.writeText(inviteUrl);
            setCopied(true);
            setCopyError(false);
        } catch {
            setCopyError(true);
        }
    }
    return (
        <Layout title={`Organiser ${exchange.name}`}>
            <div className="workspace">
                <div className="workspace-heading">
                    <div>
                        <h1>{exchange.name}</h1>
                        <Details exchange={exchange} />
                    </div>
                    <span className="status">
                        {exchange.drawn
                            ? "Tirage terminé"
                            : "Les inscriptions sont ouvertes"}
                    </span>
                </div>
                <div className="workspace-grid">
                    <div>
                        <section className="paper">
                            <h2>
                                {exchange.drawn
                                    ? "L’équipe est prête."
                                    : "Invitez les bonnes personnes."}
                            </h2>
                            {!exchange.drawn && (
                                <>
                                    <p>
                                        Partagez ce lien à votre équipe. Pour
                                        participer vous aussi, ouvrez
                                        l’invitation.
                                    </p>
                                    <div className="copy-field">
                                        <label
                                            className="sr-only"
                                            htmlFor="invite"
                                        >
                                            Lien d’invitation
                                        </label>
                                        <input
                                            id="invite"
                                            value={inviteUrl}
                                            readOnly
                                            onFocus={(e) => e.target.select()}
                                        />
                                        <button
                                            className="button secondary"
                                            onClick={copy}
                                        >
                                            {copied ? "Copié !" : "Copier"}
                                        </button>
                                    </div>
                                    {copyError && (
                                        <p className="error" role="alert">
                                            Sélectionnez le lien ci-dessus pour
                                            le copier manuellement.
                                        </p>
                                    )}
                                    <Link className="inline-link" href={base}>
                                        Ouvrir l’invitation ↗
                                    </Link>
                                </>
                            )}
                            <div className="section-heading">
                                <h3>Les participants</h3>
                                <button
                                    className="text-button"
                                    onClick={() =>
                                        router.reload({
                                            only: [
                                                "participants",
                                                "exclusions",
                                                "exchange",
                                            ],
                                        })
                                    }
                                >
                                    Actualiser
                                </button>
                                <span>
                                    {confirmed} / {participants.length}{" "}
                                    confirmés
                                </span>
                            </div>
                            {participants.length === 0 ? (
                                <div className="empty">
                                    <p>
                                        Votre équipe n’est pas encore arrivée.
                                    </p>
                                    <span>
                                        Faites passer le lien : les prénoms
                                        apparaîtront ici.
                                    </span>
                                </div>
                            ) : (
                                <ul className="people">
                                    {participants.map((p) => (
                                        <li key={p.id}>
                                            <span
                                                className="avatar"
                                                aria-hidden="true"
                                            >
                                                {p.name
                                                    .slice(0, 1)
                                                    .toUpperCase()}
                                            </span>
                                            <span className="person-name">
                                                {p.name}
                                            </span>
                                            <span className="person-state">
                                                {exchange.drawn
                                                    ? p.revealed
                                                        ? "Enveloppe ouverte"
                                                        : "Enveloppe fermée"
                                                    : p.confirmed
                                                      ? "Confirmé"
                                                      : "E-mail à confirmer"}
                                            </span>
                                            {!exchange.drawn && (
                                                <button
                                                    className="text-button remove"
                                                    aria-label={`Retirer ${p.name}`}
                                                    onClick={() => {
                                                        if (
                                                            window.confirm(
                                                                `Retirer ${p.name} du groupe ?`,
                                                            )
                                                        )
                                                            router.delete(
                                                                `${base}/participants/${p.id}`,
                                                            );
                                                    }}
                                                >
                                                    Retirer
                                                </button>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </section>
                        {!exchange.drawn && (
                            <section className="paper exclusions">
                                <h2>Un peu de hasard, quelques règles.</h2>
                                <p>
                                    Empêchez une personne de tirer un nom
                                    précis. Pour une exclusion réciproque,
                                    ajoutez les deux sens.
                                </p>
                                {participants.length < 2 ? (
                                    <p className="muted">
                                        Les exclusions seront disponibles dès
                                        que deux personnes seront inscrites.
                                    </p>
                                ) : (
                                    <Form
                                        action={`${base}/exclusions`}
                                        method="post"
                                        resetOnSuccess
                                    >
                                        {({ errors, processing }) => (
                                            <>
                                                <div className="exclusion-form">
                                                    <div className="field">
                                                        <label htmlFor="giver_id">
                                                            Cette personne…
                                                        </label>
                                                        <select
                                                            id="giver_id"
                                                            name="giver_id"
                                                            required
                                                            defaultValue=""
                                                        >
                                                            <option
                                                                value=""
                                                                disabled
                                                            >
                                                                Choisir
                                                            </option>
                                                            {participants.map(
                                                                (p) => (
                                                                    <option
                                                                        key={
                                                                            p.id
                                                                        }
                                                                        value={
                                                                            p.id
                                                                        }
                                                                    >
                                                                        {p.name}
                                                                    </option>
                                                                ),
                                                            )}
                                                        </select>
                                                    </div>
                                                    <div className="field">
                                                        <label htmlFor="recipient_id">
                                                            … ne peut pas tirer
                                                        </label>
                                                        <select
                                                            id="recipient_id"
                                                            name="recipient_id"
                                                            required
                                                            defaultValue=""
                                                        >
                                                            <option
                                                                value=""
                                                                disabled
                                                            >
                                                                Choisir
                                                            </option>
                                                            {participants.map(
                                                                (p) => (
                                                                    <option
                                                                        key={
                                                                            p.id
                                                                        }
                                                                        value={
                                                                            p.id
                                                                        }
                                                                    >
                                                                        {p.name}
                                                                    </option>
                                                                ),
                                                            )}
                                                        </select>
                                                    </div>
                                                    <button
                                                        className="button secondary"
                                                        disabled={processing}
                                                    >
                                                        Ajouter
                                                    </button>
                                                </div>
                                                {Object.values(errors).map(
                                                    (e, i) => (
                                                        <p
                                                            className="error"
                                                            role="alert"
                                                            key={i}
                                                        >
                                                            {e}
                                                        </p>
                                                    ),
                                                )}
                                            </>
                                        )}
                                    </Form>
                                )}
                                <ul className="exclusion-list">
                                    {exclusions.map((e) => (
                                        <li key={e.id}>
                                            <span>
                                                {
                                                    participants.find(
                                                        (p) =>
                                                            p.id === e.giver_id,
                                                    )?.name
                                                }{" "}
                                                <span aria-label="ne tire pas">
                                                    ↛
                                                </span>{" "}
                                                {
                                                    participants.find(
                                                        (p) =>
                                                            p.id ===
                                                            e.recipient_id,
                                                    )?.name
                                                }
                                            </span>
                                            <button
                                                className="text-button"
                                                onClick={() =>
                                                    router.delete(
                                                        `${base}/exclusions/${e.id}`,
                                                    )
                                                }
                                            >
                                                Supprimer
                                                <span className="sr-only">
                                                    {" "}
                                                    cette exclusion
                                                </span>
                                            </button>
                                        </li>
                                    ))}
                                </ul>
                            </section>
                        )}
                    </div>
                    <aside className="draw-panel">
                        <span className="mini-seal" aria-hidden="true">
                            S
                        </span>
                        <h2>
                            {exchange.drawn
                                ? "À chacun son secret."
                                : "Le grand mélange."}
                        </h2>
                        <p>
                            {exchange.drawn
                                ? "Chacun reçoit son lien personnel par e-mail. Vous pouvez suivre les ouvertures, mais les destinataires restent secrets."
                                : "Tout le monde est là ? Le tirage attribue un destinataire à chacun, en respectant vos exclusions."}
                        </p>
                        {!exchange.drawn && (
                            <Form action={`${base}/draw`} method="post">
                                {({ errors, processing }) => (
                                    <>
                                        <label className="checkbox">
                                            <input
                                                type="checkbox"
                                                name="confirm"
                                                value="1"
                                                required
                                            />
                                            <span>
                                                L’équipe est au complet. Je
                                                comprends que le tirage est
                                                définitif.
                                            </span>
                                        </label>
                                        {Object.values(errors).map((e, i) => (
                                            <p
                                                key={i}
                                                className="error"
                                                role="alert"
                                            >
                                                {e}
                                            </p>
                                        ))}
                                        <button
                                            className="button full"
                                            disabled={
                                                processing ||
                                                confirmed < 3 ||
                                                confirmed !==
                                                    participants.length
                                            }
                                        >
                                            {processing
                                                ? "Le hasard travaille…"
                                                : "Lancer le tirage"}
                                        </button>
                                        {confirmed < 3 ? (
                                            <p className="fine-print">
                                                Il faut au moins 3
                                                participations confirmées.
                                            </p>
                                        ) : (
                                            confirmed !==
                                                participants.length && (
                                                <p className="fine-print">
                                                    Attendez les confirmations
                                                    ou retirez les inscriptions
                                                    en attente.
                                                </p>
                                            )
                                        )}
                                    </>
                                )}
                            </Form>
                        )}
                    </aside>
                </div>
            </div>
        </Layout>
    );
}

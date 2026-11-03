import { Link } from "@inertiajs/react";
import Layout from "../components/Layout";
const messages: Record<number, [string, string]> = {
    403: [
        "Cette enveloppe est privée.",
        "Ouvrez le lien personnel reçu par e-mail pour accéder à votre espace.",
    ],
    404: [
        "Cette invitation est introuvable.",
        "Vérifiez que vous avez ouvert le lien complet envoyé par e-mail.",
    ],
    409: [
        "Le groupe a changé.",
        "Le tirage a peut-être déjà eu lieu. Ouvrez à nouveau votre lien personnel pour voir son état actuel.",
    ],
    410: [
        "Ce Secret Santa est terminé.",
        "Les liens expirent 30 jours après la date des cadeaux. À l’année prochaine !",
    ],
    419: [
        "Votre session a expiré.",
        "Revenez à l’accueil ou ouvrez votre lien personnel, puis réessayez.",
    ],
    429: [
        "Un petit instant…",
        "Vous avez effectué plusieurs demandes rapprochées. Patientez avant de réessayer et consultez votre boîte mail.",
    ],
};
export default function Error({ status }: { status: number }) {
    const [title, text] = messages[status] ?? [
        "Un petit imprévu.",
        "Nous ne pouvons pas terminer cette demande. Réessayez dans quelques instants.",
    ];
    return (
        <Layout title={title}>
            <section className="centered paper">
                <h1>{title}</h1>
                <p>{text}</p>
                <Link className="button" href="/">
                    Retour à l’accueil
                </Link>
            </section>
        </Layout>
    );
}

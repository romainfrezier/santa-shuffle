import { Link } from "@inertiajs/react";
import Layout, { Mark } from "../components/Layout";
import type { Exchange } from "../components/Fields";
export default function Pending({ exchange }: { exchange: Exchange }) {
    return (
        <Layout title="Regardez votre boîte mail">
            <section className="centered paper">
                <span className="mail-symbol" aria-hidden="true">
                    <Mark />
                </span>
                <h1>
                    Une invitation
                    <br />
                    dans votre boîte mail.
                </h1>
                <p>
                    Si votre adresse est valide, vous allez recevoir votre lien
                    personnel pour <strong>{exchange.name}</strong>.
                </p>
                <p>
                    Ouvrez-le puis confirmez votre participation. Pensez à
                    vérifier les courriers indésirables.
                </p>
                <Link
                    className="button secondary"
                    href={`/events/${exchange.uuid}`}
                >
                    Revenir à l’invitation
                </Link>
            </section>
        </Layout>
    );
}

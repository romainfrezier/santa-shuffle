import { Form } from "@inertiajs/react";
import Layout from "../components/Layout";
export default function Access({
    eventName,
    action,
    organizer,
}: {
    eventName: string;
    action: string;
    organizer: boolean;
}) {
    return (
        <Layout title="Votre invitation privée">
            <section className="centered paper">
                <h1>
                    {organizer
                        ? "Bienvenue dans les coulisses."
                        : "Une place vous attend."}
                </h1>
                <p>{eventName}</p>
                <Form action={action} method="post">
                    {({ processing }) => (
                        <button className="button" disabled={processing}>
                            {processing
                                ? "Ouverture…"
                                : organizer
                                  ? "Gérer mon Secret Santa"
                                  : "Ouvrir mon espace personnel"}
                        </button>
                    )}
                </Form>
                <p className="fine-print">
                    Ce lien est privé. Gardez-le pour revenir quand vous voulez.
                </p>
            </section>
        </Layout>
    );
}

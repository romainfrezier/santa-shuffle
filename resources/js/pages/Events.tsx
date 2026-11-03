import { EventDetails } from "../components/EventDetails";
import { useTranslation } from "../i18n/useTranslation";
import type { Exchange } from "../types/exchange";
import { Link, usePage } from "@inertiajs/react";
import { Plus, ArrowUpRight } from "lucide-react";
import Layout from "../components/Layout";

export default function Events({ events }: { events: Exchange[] }) {
    const { t } = useTranslation();

    const { organizerEmail } = usePage<{ organizerEmail: string }>().props;
    return (
        <Layout title={t("My events")}>
            <div className="workspace">
                <div className="workspace-heading">
                    <div>
                        <span className="eyebrow">
                            {t("YOUR WORKSHOP OF SMILES")}
                        </span>
                        <h1>{t("My events")}</h1>
                        <p>{organizerEmail}</p>
                    </div>
                    <Link href="/create" className="button">
                        <Plus size={18} aria-hidden="true" />
                        {t("Create a Secret Santa")}
                    </Link>
                </div>
                {events.length ? (
                    <div className="event-grid">
                        {events.map((event) => (
                            <article className="paper" key={event.uuid}>
                                <span className="status">
                                    {event.cancelled
                                        ? t("Cancelled")
                                        : event.drawn
                                          ? t("Draw complete")
                                          : t("Registration open")}
                                </span>
                                <h2>{event.name}</h2>
                                <EventDetails exchange={event} />
                                <Link
                                    className="inline-link"
                                    href={`/events/${event.uuid}/manage`}
                                >
                                    {t("Manage this event")}{" "}
                                    <ArrowUpRight
                                        size={16}
                                        aria-hidden="true"
                                    />
                                </Link>
                            </article>
                        ))}
                    </div>
                ) : (
                    <section className="paper">
                        <h2>{t("Your first surprise awaits.")}</h2>
                        <p>
                            {t(
                                "Create your first event to bring your favorite people together. Expired events and events with revoked access are not shown.",
                            )}
                        </p>
                    </section>
                )}
            </div>
        </Layout>
    );
}

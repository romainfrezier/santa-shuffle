import { EventDetails } from "../components/EventDetails";
import { useTranslation } from "../i18n/useTranslation";
import Layout from "../components/Layout";

import type { Exchange, Person, Exclusion } from "../types/exchange";
import ParticipantsPanel from "../features/events/ParticipantsPanel";
import EventSettings from "../features/events/EventSettings";
import ExclusionsPanel from "../features/events/ExclusionsPanel";
import DrawPanel from "../features/events/DrawPanel";
import { useEventAction } from "../features/events/useEventAction";
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
    const { t } = useTranslation();

    const { action, busy, errors } = useEventAction(exchange.uuid);
    return (
        <Layout title={t("Manage {name}", { name: exchange.name })}>
            <div className="workspace">
                <div className="workspace-heading">
                    <div>
                        <h1>{exchange.name}</h1>
                        <EventDetails exchange={exchange} />
                    </div>
                    <span className="status">
                        {exchange.cancelled
                            ? t("Event cancelled")
                            : exchange.drawn
                              ? t("Draw complete")
                              : t("Registration is open")}
                    </span>
                </div>
                {errors.map((error, i) => (
                    <p className="error" role="alert" key={i}>
                        {error}
                    </p>
                ))}
                <div className="workspace-grid">
                    <div>
                        <ParticipantsPanel
                            exchange={exchange}
                            inviteUrl={inviteUrl}
                            participants={participants}
                            action={action}
                            busy={busy}
                        />
                        <EventSettings
                            exchange={exchange}
                            action={action}
                            busy={busy}
                        />
                        <ExclusionsPanel
                            exchange={exchange}
                            participants={participants}
                            exclusions={exclusions}
                        />
                    </div>
                    <DrawPanel
                        exchange={exchange}
                        participants={participants}
                        action={action}
                        busy={busy}
                    />
                </div>
            </div>
        </Layout>
    );
}

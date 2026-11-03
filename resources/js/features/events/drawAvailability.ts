import type { Exchange, Person } from "../../types/exchange";
export function drawAvailability(
    exchange: Pick<Exchange, "drawn" | "cancelled">,
    participants: Pick<Person, "confirmed" | "revealed">[],
) {
    const confirmed = participants.filter((person) => person.confirmed).length;
    return {
        confirmed,
        canDraw:
            !exchange.cancelled &&
            !exchange.drawn &&
            confirmed >= 3 &&
            confirmed === participants.length,
        canReopen:
            !exchange.cancelled &&
            exchange.drawn &&
            !participants.some((person) => person.revealed),
    };
}

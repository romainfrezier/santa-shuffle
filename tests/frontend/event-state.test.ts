import { expect, it } from "vitest";
import { drawAvailability } from "../../resources/js/features/events/drawAvailability";
const person = (confirmed = true, revealed = false) => ({
    confirmed,
    revealed,
});
const open = { drawn: false, cancelled: false };
it("requires three or more confirmed participants and no pending invitations", () => {
    expect(drawAvailability(open, [person(), person()]).canDraw).toBe(false);
    expect(drawAvailability(open, [person(), person(), person()]).canDraw).toBe(
        true,
    );
    expect(
        drawAvailability(open, [person(), person(), person(), person(false)])
            .canDraw,
    ).toBe(false);
});
it("never offers another draw on cancelled or drawn events", () => {
    const people = [person(), person(), person()];
    expect(drawAvailability({ ...open, cancelled: true }, people).canDraw).toBe(
        false,
    );
    expect(drawAvailability({ ...open, drawn: true }, people).canDraw).toBe(
        false,
    );
});
it("allows reopening only before any reveal and never after cancellation", () => {
    expect(
        drawAvailability({ ...open, drawn: true }, [person()]).canReopen,
    ).toBe(true);
    expect(
        drawAvailability({ ...open, drawn: true }, [person(true, true)])
            .canReopen,
    ).toBe(false);
    expect(
        drawAvailability({ ...open, drawn: true, cancelled: true }, [person()])
            .canReopen,
    ).toBe(false);
    expect(drawAvailability(open, [person()]).canReopen).toBe(false);
});

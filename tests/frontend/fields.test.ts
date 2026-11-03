import { createElement } from "react";
import { renderToStaticMarkup } from "react-dom/server";
import { expect, it } from "vitest";
import { Field, TextArea } from "../../resources/js/components/Fields";
it("associates explicit field IDs and validation messages accessibly", () => {
    const html = renderToStaticMarkup(
        createElement(Field, {
            name: "name",
            id: "event-name",
            label: "Event name",
            error: "Required",
        }),
    );
    expect(html).toContain('for="event-name"');
    expect(html).toContain('aria-invalid="true"');
    expect(html).toContain('aria-describedby="event-name-error"');
    expect(html).toContain('id="event-name-error"');
});
it("escapes errors and user values and does not describe a nonexistent error", () => {
    const html = renderToStaticMarkup(
        createElement(TextArea, {
            name: "wishes",
            label: "Wishes",
            defaultValue: "<script>alert(1)</script>",
        }),
    );
    expect(html).toContain("&lt;script&gt;");
    expect(html).not.toContain("<script>");
    expect(html).not.toContain("aria-describedby");
});

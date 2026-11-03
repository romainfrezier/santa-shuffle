import "../css/app.css";
import { createInertiaApp } from "@inertiajs/react";
createInertiaApp({
    title: (title) => `${title} · Santa Shuffle`,
    strictMode: true,
});

import type { InputHTMLAttributes, TextareaHTMLAttributes } from "react";
export function Field({
    label,
    error,
    name,
    ...props
}: InputHTMLAttributes<HTMLInputElement> & {
    label: string;
    name: string;
    error?: string;
}) {
    return (
        <div className="field">
            <label htmlFor={name}>{label}</label>
            <input
                {...props}
                id={name}
                name={name}
                aria-invalid={!!error}
                aria-describedby={error ? `${name}-error` : undefined}
            />
            {error && (
                <span id={`${name}-error`} className="error" role="alert">
                    {error}
                </span>
            )}
        </div>
    );
}
export function TextArea({
    label,
    error,
    name,
    ...props
}: TextareaHTMLAttributes<HTMLTextAreaElement> & {
    label: string;
    name: string;
    error?: string;
}) {
    return (
        <div className="field">
            <label htmlFor={name}>{label}</label>
            <textarea
                {...props}
                id={name}
                name={name}
                rows={3}
                aria-invalid={!!error}
                aria-describedby={error ? `${name}-error` : undefined}
            />
            {error && (
                <span id={`${name}-error`} className="error" role="alert">
                    {error}
                </span>
            )}
        </div>
    );
}
export type Exchange = {
    uuid: string;
    name: string;
    gift_date: string;
    budget: number;
    message: string | null;
    drawn: boolean;
};
export function Details({ exchange }: { exchange: Exchange }) {
    return (
        <div className="event-details">
            <span>
                {new Intl.DateTimeFormat("fr-FR", {
                    day: "numeric",
                    month: "long",
                    year: "numeric",
                }).format(new Date(`${exchange.gift_date}T12:00:00`))}
            </span>
            <span>{exchange.budget} € par cadeau</span>
        </div>
    );
}

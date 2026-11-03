import type { InputHTMLAttributes, TextareaHTMLAttributes } from "react";
export function Field({
    label,
    error,
    name,
    id = name,
    ...props
}: InputHTMLAttributes<HTMLInputElement> & {
    label: string;
    name: string;
    error?: string;
}) {
    return (
        <div className="field">
            <label htmlFor={id}>{label}</label>
            <input
                {...props}
                id={id}
                name={name}
                aria-invalid={!!error}
                aria-describedby={error ? `${id}-error` : undefined}
            />
            {error && (
                <span id={`${id}-error`} className="error" role="alert">
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
    id = name,
    ...props
}: TextareaHTMLAttributes<HTMLTextAreaElement> & {
    label: string;
    name: string;
    error?: string;
}) {
    return (
        <div className="field">
            <label htmlFor={id}>{label}</label>
            <textarea
                {...props}
                id={id}
                name={name}
                rows={3}
                aria-invalid={!!error}
                aria-describedby={error ? `${id}-error` : undefined}
            />
            {error && (
                <span id={`${id}-error`} className="error" role="alert">
                    {error}
                </span>
            )}
        </div>
    );
}

export type Exchange = {
    uuid: string;
    name: string;
    gift_date: string;
    budget: number;
    currency: string;
    message: string | null;
    drawn: boolean;
    cancelled: boolean;
};
export type Person = {
    id: number;
    name: string;
    confirmed: boolean;
    revealed: boolean;
    delivery: "pending" | "accepted" | "failed" | "obsolete" | null;
};
export type Exclusion = { id: number; giver_id: number; recipient_id: number };

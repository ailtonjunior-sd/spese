export type TransactionType = 'income' | 'expense';

export type Option = {
    value: string;
    label: string;
};

export type Account = {
    id: number;
    name: string;
    type: string;
    type_label: string;
    color: string;
    initial_balance: number;
    balance?: number;
    archived: boolean;
    transactions_count?: number;
};

export type Category = {
    id: number;
    name: string;
    type: TransactionType;
    color: string;
    transactions_count?: number;
};

export type Transaction = {
    id: number;
    type: TransactionType;
    amount: number;
    description: string;
    date: string;
    paid: boolean;
    notes: string | null;
    account_id: number;
    category_id: number | null;
    account?: { name: string; color: string };
    category?: { name: string; color: string } | null;
};

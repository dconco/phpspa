export type StateObject = {
    url: string;
    title: string;
    targetID: string;
    content: string;
    root?: boolean;
    exact?: boolean;
    reloadTime?: number;
    defaultContent?: string;
};
export type ComponentObject = {
    content: string;
    title?: string;
    targetID?: any;
    reloadTime?: number;
    exact?: boolean;
};
export type StateValueType = string | number | boolean | null | {
    [key: string]: StateValueType;
} | StateValueType[];
//# sourceMappingURL=StateObjectTypes.d.ts.map
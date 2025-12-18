export type EffectType = {
    callback: Function;
    dependencies: Array<string> | null;
    cleanup: Function | null;
};
export interface EventObject {
    beforeload: ((payload: EventPayload) => void)[];
    load: ((payload: EventPayload) => void)[];
}
export interface EventPayload {
    route: string;
    success?: boolean;
    error?: any;
    data?: string;
}
export type CurrentRoutesObject = Record<string, {
    route: URL;
    exact: boolean;
    defaultContent: string;
}>;
//# sourceMappingURL=RuntimeInterfaces.d.ts.map
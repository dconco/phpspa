export declare const clearPreloadedStylesForScope: (scopeKey: string) => void;
export declare const retainStylesheetLinks: (sourceContent: string, updatedContent: string) => string;
export declare const preloadStylesFromContent: (content: string, scopeKey?: string) => {
    element: HTMLDivElement;
    ready: Promise<void>;
};
//# sourceMappingURL=preloadStylesFromContent.d.ts.map
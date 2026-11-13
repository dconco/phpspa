export declare const clearPreloadedStylesForScope: (scopeKey: string) => void;
/**
 * Keeps the source content's style tags on a live-HTML snapshot, because they are
 * moved to <head> on navigation and so are missing from the target's current markup.
 */
export declare const retainStyleTags: (sourceContent: string, updatedContent: string) => string;
/**
 * Prepares a component's styles without letting them affect the current page.
 *
 * Stylesheets download inert. `commitStyles` then switches styles over in one
 * synchronous step, so calling it in the same task as the DOM swap leaves the
 * browser no opportunity to paint a half-styled page.
 *
 * @param content - The component HTML
 * @param scopeKey - The target that owns these styles
 * @param baseUrl - The URL the content was rendered for, which relative hrefs are resolved against
 */
export declare const preloadStylesFromContent: (content: string, scopeKey?: string, baseUrl?: string | URL) => {
    element: HTMLDivElement;
    ready: Promise<void[]>;
    commitStyles: () => void;
};
//# sourceMappingURL=preloadStylesFromContent.d.ts.map
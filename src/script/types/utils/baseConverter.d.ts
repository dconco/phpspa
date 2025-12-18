/**
 * UTF-8 safe base64 encoding function
 * Handles Unicode characters that btoa cannot process
 */
export declare function utf8ToBase64(str: string): string;
/**
 * UTF-8 safe base64 decoding function
 * Handles Unicode characters that atob cannot process
 */
export declare function base64ToUtf8(str: string): string;
//# sourceMappingURL=baseConverter.d.ts.map
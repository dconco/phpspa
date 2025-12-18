/**
 * UTF-8 safe base64 encoding function
 * Handles Unicode characters that btoa cannot process
 */
export function utf8ToBase64(str: string): string {
   try {
      // First try the native btoa for performance
      return btoa(str);
   } catch (e) {
      // If btoa fails (due to non-Latin1 characters), use UTF-8 safe encoding
      try {
         // Modern replacement for unescape(encodeURIComponent(str))
         const utf8Bytes = new TextEncoder().encode(str);
         const binaryString = Array.from(utf8Bytes, byte => String.fromCharCode(byte)).join('');
         return btoa(binaryString);
      } catch (fallbackError) {
         // Final fallback: encode each character individually
         return btoa(
            str.split('').map(function (c) {
               return String.fromCharCode(c.charCodeAt(0) & 0xff);
            }).join('')
         );
      }
   }
}

/**
 * UTF-8 safe base64 decoding function
 * Handles Unicode characters that atob cannot process
 */
export function base64ToUtf8(str: string): string {
   try {
      // Try modern UTF-8 safe decoding first
      const binaryString = atob(str);
      const bytes = new Uint8Array(binaryString.length);
      for (let i = 0; i < binaryString.length; i++) {
         bytes[i] = binaryString.charCodeAt(i);
      }
      return new TextDecoder().decode(bytes);
   } catch (e) {
      // Fallback to regular atob
      return atob(str);
   }
}

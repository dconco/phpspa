#ifndef HTML_COMPRESSOR_H
#define HTML_COMPRESSOR_H

#include <string>

class HtmlCompressor {
   public:
      enum Level {
         BASIC = 1,
         AGGRESSIVE = 2,
         EXTREME = 3
      };

      static Level currentLevel;

      /**
       * Compress HTML content based on specified level
       * @param html The HTML content to compress
       * @param level Compression level (1-3)
       * @return Compressed HTML string
       */
      static std::string compress(const std::string& html);

      // --- Minify inline CSS content ---
      static void minifyCSS(std::string& css);

      // --- Minify inline JavaScript content ---
      static void minifyJS(std::string& js);

   private:

      // --- Remove HTML comments (<!-- -->) ---
      static void removeComments(std::string& html);
      
      // --- Remove unnecessary whitespace (multiple spaces, newlines, tabs) ---
      static void minifyHTML(std::string& html);

      // --- Optimize attributes (remove quotes where safe, trim values) ---
      static void optimizeAttributes(std::string& tagContent);
};

#endif // HTML_COMPRESSOR_H

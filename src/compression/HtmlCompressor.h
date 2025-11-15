#ifndef HTML_COMPRESSOR_H
#define HTML_COMPRESSOR_H

#include <string>

class HtmlCompressor {
   public:
      enum Level {
         BASIC = 1,
         AGGRESSIVE = 3,
         EXTREME = 4
      };

      /**
       * Compress HTML content based on specified level
       * @param html The HTML content to compress
       * @param level Compression level (1-3)
       * @return Compressed HTML string
       */
      static std::string compress(const std::string& html, const Level level);

   private:
      // --- Remove HTML comments (<!-- -->) ---
      static std::string removeComments(const std::string& html);
      
      // --- Remove unnecessary whitespace (multiple spaces, newlines, tabs) ---
      static std::string removeWhitespace(const std::string& html);

      // --- Remove whitespace from special tags like <pre>, <code>, <textarea> ---
      static std::string removeWhitespaceFromSpecialTags(const std::string& html);

      // --- Optimize attributes (remove quotes where safe, trim values) ---
      static std::string optimizeAttributes(const std::string& html);
      
      // --- Check if we're inside a special tag where whitespace matters ---
      static bool isInsideSpecialTag(const std::string& html, size_t pos);
};

#endif // HTML_COMPRESSOR_H

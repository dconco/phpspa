#include "../HtmlCompressor.h"

namespace {

   constexpr char kCommentOpen[] = "<!--";
   constexpr char kCommentClose[] = "-->";

   bool isCommentStart(const std::string& html, size_t pos) {
      return pos + 3 < html.size() &&
         html[pos] == '<' && html[pos + 1] == '!' && html[pos + 2] == '-' && html[pos + 3] == '-';
   }

} // namespace

void HtmlCompressor::removeComments(std::string& html) {
   if (html.empty()) {
      return;
   }

   size_t readPos = 0;
   size_t writePos = 0;
   const size_t length = html.length();

   while (readPos < length) {
      if (isCommentStart(html, readPos)) {
         const size_t commentEnd = html.find(kCommentClose, readPos + sizeof(kCommentOpen) - 1);

         if (commentEnd == std::string::npos) {
            html.resize(writePos); // Unclosed comment drops trailing content, matching previous behavior.
            return;
         }

         readPos = commentEnd + sizeof(kCommentClose) - 1;
         continue;
      }

      html[writePos++] = html[readPos++];
   }

   html.resize(writePos);
}
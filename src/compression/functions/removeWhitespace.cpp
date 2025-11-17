#include <algorithm>
#include <cctype>
#include <cstring>
#include <vector>
#include "../HtmlCompressor.h"
#include "../../helper/explode.h"
#include "../../utils/trim.h"

namespace {

   bool isSpecialTag(const std::string& tag) {
      return tag == "pre" || tag == "script" || tag == "style" || tag == "textarea" || tag == "code";
   }

   void toLowerInPlace(std::string& text) {
      std::transform(text.begin(), text.end(), text.begin(), [](unsigned char ch) -> char {
         return static_cast<char>(std::tolower(ch));
      });
   }

   bool isSelfClosing(const std::string& tagContent) {
      for (size_t i = tagContent.size(); i > 0; --i) {
         const char ch = tagContent[i - 1];
         if (ch == '>') {
            continue;
         }
         if (std::isspace(static_cast<unsigned char>(ch))) {
            continue;
         }
         return ch == '/';
      }
      return false;
   }

   void writeChunk(std::string& html, const std::string& chunk, size_t& writePos) {
      const size_t needed = writePos + chunk.size();
      if (needed > html.size()) {
         html.resize(needed);
      }
      std::memcpy(&html[writePos], chunk.data(), chunk.size());
      writePos = needed;
   }

   void writeChar(std::string& html, char ch, size_t& writePos) {
      if (writePos == html.size()) {
         html.push_back(ch);
      } else {
         html[writePos] = ch;
      }
      ++writePos;
   }

} // namespace

void HtmlCompressor::removeWhitespace(std::string& html) {
   if (html.empty()) {
      return;
   }

   const size_t originalLength = html.length();
   size_t readPos = 0;
   size_t writePos = 0;
   std::vector<std::string> tagStack;
   tagStack.reserve(16);
   bool insideSpecial = false;
   bool pendingSpace = false;
   std::string tagContent;
   std::string tagName;

   auto refreshInsideSpecial = [&]() {
      insideSpecial = false;
      for (auto it = tagStack.rbegin(); it != tagStack.rend(); ++it) {
         if (isSpecialTag(*it)) {
            insideSpecial = true;
            break;
         }
      }
   };

   while (readPos < originalLength) {
      char current = html[readPos];

      if (current == '<') {
         const size_t tagEnd = html.find('>', readPos);
         if (tagEnd == std::string::npos) {
            break;
         }

         tagContent.assign(html.data() + readPos, tagEnd - readPos + 1);
         const bool isComment = tagContent.size() >= 4 && tagContent[1] == '!' && tagContent[2] == '-' && tagContent[3] == '-';
         const bool isClosingTag = tagContent.size() >= 3 && tagContent[1] == '/';

         if (isComment) {
            writeChunk(html, tagContent, writePos);
            readPos = tagEnd + 1;
            pendingSpace = false;
            continue;
         }

         if (isClosingTag) {
            size_t nameStart = 2;
            while (nameStart < tagContent.size() && std::isspace(static_cast<unsigned char>(tagContent[nameStart]))) {
               ++nameStart;
            }

            size_t nameEnd = nameStart;
            while (nameEnd < tagContent.size() && !std::isspace(static_cast<unsigned char>(tagContent[nameEnd])) && tagContent[nameEnd] != '>') {
               ++nameEnd;
            }

            tagName.assign(tagContent.begin() + nameStart, tagContent.begin() + nameEnd);
            toLowerInPlace(tagName);

            if (!tagStack.empty()) {
               auto it = std::find(tagStack.rbegin(), tagStack.rend(), tagName);
               if (it != tagStack.rend()) {
                  const size_t removeCount = static_cast<size_t>(std::distance(tagStack.rbegin(), it)) + 1;
                  for (size_t count = 0; count < removeCount && !tagStack.empty(); ++count) {
                     tagStack.pop_back();
                  }
               }
            }

            refreshInsideSpecial();
         } else {
            size_t nameStart = 1;
            while (nameStart < tagContent.size() && std::isspace(static_cast<unsigned char>(tagContent[nameStart]))) {
               ++nameStart;
            }

            size_t nameEnd = nameStart;
            while (nameEnd < tagContent.size() && !std::isspace(static_cast<unsigned char>(tagContent[nameEnd])) && tagContent[nameEnd] != '>' && tagContent[nameEnd] != '/') {
               ++nameEnd;
            }

            tagName.assign(tagContent.begin() + nameStart, tagContent.begin() + nameEnd);
            toLowerInPlace(tagName);

            const bool selfClosing = isSelfClosing(tagContent);
            if (!selfClosing) {
               tagStack.push_back(tagName);
               if (isSpecialTag(tagName)) {
                  insideSpecial = true;
               }
            }
         }

         optimizeAttributes(tagContent);
         writeChunk(html, tagContent, writePos);

         pendingSpace = false;
         readPos = tagEnd + 1;
         continue;
      }

      if (insideSpecial) {
         if (!tagStack.empty()) {
            const std::string& currentTag = tagStack.back();
            if (currentTag == "script" || currentTag == "style") {
               std::string closingTag = "</" + currentTag;
               const size_t closingPos = html.find(closingTag, readPos);
               if (closingPos != std::string::npos) {
                  std::string content = html.substr(readPos, closingPos - readPos);
                  if (currentTag == "script") {
                     minifyJS(content);
                  } else {
                     minifyCSS(content);
                  }
                  writeChunk(html, content, writePos);
                  readPos = closingPos;
                  continue;
               }
            }
         }

         writeChar(html, current, writePos);
         ++readPos;
         continue;
   }

      if (isWhitespace(current)) {
         pendingSpace = true;
         ++readPos;
         continue;
      }

      if (pendingSpace && writePos > 0 && html[writePos - 1] != '>') {
         writeChar(html, ' ', writePos);
      }

      writeChar(html, current, writePos);
      pendingSpace = false;
      ++readPos;
   }

   html.resize(writePos);
}
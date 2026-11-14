#include <iostream>
#include "../HtmlCompressor.h"
#include "../../utils/trim.h"

void HtmlCompressor::optimizeAttributes(std::string& tagContent, const std::string& tagName) {
   if (HtmlCompressor::currentLevel < AGGRESSIVE) return;

   bool isMeta = (tagName == "meta");
   if (!isMeta && tagName.empty()) {
      // Fallback: detect if tag is <meta> from tagContent
      size_t pos = 0;
      while (pos < tagContent.length() && (isWhitespace(tagContent[pos]) || tagContent[pos] == '<')) {
         if (tagContent[pos] == '<') { ++pos; break; }
         ++pos;
      }
      if (pos + 4 <= tagContent.length()) {
         if ((tagContent[pos] == 'm' || tagContent[pos] == 'M') &&
             (tagContent[pos + 1] == 'e' || tagContent[pos + 1] == 'E') &&
             (tagContent[pos + 2] == 't' || tagContent[pos + 2] == 'T') &&
             (tagContent[pos + 3] == 'a' || tagContent[pos + 3] == 'A')) {
            char next = (pos + 4 < tagContent.length()) ? tagContent[pos + 4] : '>';
            if (isWhitespace(next) || next == '>' || next == '/') {
               isMeta = true;
            }
         }
      }
   }

   std::string optimizedContent;
   optimizedContent.reserve(tagContent.length()); // Pre-allocate to avoid reallocations
   bool lastWasSpace = false;

   for (size_t i = 0; i < tagContent.length(); ++i) {
      char currentChar = tagContent[i];

      if (isWhitespace(currentChar)) {
         // Mark that we encountered whitespace, but don't add it yet
         lastWasSpace = true;
         continue;
      }

      // If we had pending whitespace and current char is not '>', add a single space
      if (lastWasSpace && currentChar != '>' && currentChar != '=' && currentChar != '"' && currentChar != '\'') {
         optimizedContent += ' ';
      }

      optimizedContent += currentChar;
      lastWasSpace = false;
   }

   // --- META TAGS: ALWAYS PRESERVE (AND ENSURE) ATTRIBUTE QUOTES ---
   // Social crawlers (WhatsApp, Facebook, iMessage) rely on quoted attributes
   // for OpenGraph and metadata tags (<meta property="og:..." content="...">).
   // Stripping quotes causes them to fail parsing link previews.
   if (isMeta) {
      std::string result;
      result.reserve(optimizedContent.length() + 8);
      for (size_t i = 0; i < optimizedContent.length(); ++i) {
         char current = optimizedContent[i];

         if (current == '=' && i + 1 < optimizedContent.length()) {
            char nextChar = optimizedContent[i + 1];

            if (nextChar == '"' || nextChar == '\'') {
               result += '=';
               result += nextChar;
               char quoteChar = nextChar;
               size_t valueStart = i + 2;
               size_t valueEnd = optimizedContent.find(quoteChar, valueStart);

               if (valueEnd != std::string::npos) {
                  result += optimizedContent.substr(valueStart, valueEnd - valueStart + 1);
                  i = valueEnd;
                  continue;
               }
            } else if (nextChar != '>' && nextChar != '/' && !isWhitespace(nextChar)) {
               // Unquoted attribute value on meta tag: wrap in double quotes for strict crawler compatibility
               result += "=\"";
               size_t valEnd = i + 1;
               while (valEnd < optimizedContent.length() &&
                      !isWhitespace(optimizedContent[valEnd]) &&
                      optimizedContent[valEnd] != '>' &&
                      optimizedContent[valEnd] != '/') {
                  result += optimizedContent[valEnd];
                  ++valEnd;
               }
               result += '"';
               i = valEnd - 1;
               continue;
            }
         }

         result += current;
      }

      tagContent = result;
      return;
   }

   // --- EXTREME LEVEL OPTIMIZATIONS ---

   if (HtmlCompressor::currentLevel < EXTREME) {
      tagContent = optimizedContent;
      return;
   }

   // --- REMOVE QUOTES FROM ATTRIBUTES WHERE SAFE ---

   std::string result;
   for (size_t i = 0; i < optimizedContent.length(); ++i) {
      char current = optimizedContent[i];

      if (current == '=' && i + 1 < optimizedContent.length()) {
         char nextChar = optimizedContent[i + 1];

         if (nextChar == '"' || nextChar == '\'') {
            if (i + 2 < optimizedContent.length() && optimizedContent[i + 2] == nextChar) {
               i += 2;  // Skip ="" or =''
               continue;
            }

            result += '=';  // Add the equals sign

            // Find the closing quote
            char quoteChar = nextChar;
            size_t valueStart = i + 2;
            size_t valueEnd = optimizedContent.find(quoteChar, valueStart);

            if (valueEnd != std::string::npos) {
               std::string attributeValue = optimizedContent.substr(valueStart, valueEnd - valueStart);

               // Check if the value is safe to unquote
               bool canUnquote = !attributeValue.empty();
               for (char ch : attributeValue) {
                  if (isWhitespace(ch) || ch == '>' || ch == '<' || ch == '=' || ch == '"' || ch == '\'' || ch == '`') {
                     canUnquote = false;
                     break;
                  }
               }

               if (canUnquote) {
                  // Add value without quotes
                  result += attributeValue;
               } else {
                  // Keep the quotes
                  result += quoteChar;
                  result += attributeValue;
                  result += quoteChar;
               }

               i = valueEnd;  // Move to closing quote position
               continue;
            }
         }
      }

      result += current;
   }

   tagContent = result;
}
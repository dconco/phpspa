#include <iostream>
#include "../HtmlCompressor.h"
#include "../../utils/trim.h"

void HtmlCompressor::optimizeAttributes(std::string& tagContent) {
   if (HtmlCompressor::currentLevel < AGGRESSIVE) return;

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
#include <string>
#include <iostream>
#include "../HtmlCompressor.h"
#include "../../utils/trim.h"

std::string HtmlCompressor::optimizeAttributes(const std::string& tagContent) {
   if (HtmlCompressor::currentLevel < AGGRESSIVE) return tagContent;

   std::string optimizedContent;
   bool lastWasSpace = false;

   for (size_t i = 0; i < tagContent.size(); ++i) {
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

   if (HtmlCompressor::currentLevel < EXTREME) return optimizedContent;

   for (size_t i = 0; i < optimizedContent.size(); ++i) {
      if (optimizedContent[i] == '=' && i + 1 < optimizedContent.size()) {
         char nextChar = optimizedContent[i + 1];

         if (nextChar == '"' || nextChar == '\'') {
            if (i + 2 < optimizedContent.size() && optimizedContent[i + 2] == nextChar) {
               optimizedContent.erase(i, 3); // Remove ="" or =''
               continue;
            }

            // Find the closing quote
            char quoteChar = nextChar;
            size_t valueStart = i + 2;
            size_t valueEnd = optimizedContent.find(quoteChar, valueStart);

            if (valueEnd != std::string::npos) {
               std::string attributeValue = optimizedContent.substr(valueStart, valueEnd - valueStart);

               // Check if the value is safe to unquote
               bool canUnquote = true;
               for (char ch : attributeValue) {
                  if (isWhitespace(ch) || ch == '>' || ch == '<' || ch == '=' || ch == '"' || ch == '\'' || ch == '`') {
                     canUnquote = false;
                     break;
                  }
               }

               if (canUnquote) {
                  // Remove the quotes
                  optimizedContent.erase(valueEnd, 1); // Remove closing quote
                  optimizedContent.erase(i + 1, 1);    // Remove opening quote
                  i = valueEnd - 2; // Adjust index after removal
               } else {
                  i = valueEnd; // Skip to the end of the quoted value
               }
            }
         }
      }
   }


   return optimizedContent;
}
#include <cctype>
#include <string_view>
#include "../HtmlCompressor.h"

namespace {

   bool isIdentifierStart(char ch) {
      return std::isalpha(static_cast<unsigned char>(ch)) || ch == '_' || ch == '$';
   }

   bool isIdentifierBody(char ch) {
      return std::isalnum(static_cast<unsigned char>(ch)) || ch == '_' || ch == '$';
   }

   bool isStatementEndChar(char ch) {
      return std::isalnum(static_cast<unsigned char>(ch)) || ch == '_' || ch == '$' ||
         ch == ')' || ch == ']' || ch == '}' || ch == '"' || ch == '\'' || ch == '`';
   }

   bool isStatementStartChar(char ch) {
      return isIdentifierStart(ch) || ch == '(' || ch == '[' || ch == '+' || ch == '-' || ch == '!';
   }

   std::string_view readKeyword(const std::string& source, size_t pos) {
      if (pos >= source.size() || !isIdentifierStart(source[pos])) {
         return {};
      }

      size_t end = pos + 1;
      while (end < source.size() && isIdentifierBody(source[end])) {
         ++end;
      }

      return std::string_view(source.data() + pos, end - pos);
   }

   bool isControlFlowFollower(std::string_view keyword) {
      return keyword == "else" || keyword == "catch" || keyword == "finally" || keyword == "while";
   }

} // namespace

void HtmlCompressor::minifyJS(std::string& js) {
   if (currentLevel < AGGRESSIVE) {
      return;
   }

   std::string result;
   result.reserve(js.length());

   bool inString = false;
   bool inRegex = false;
   bool inSingleComment = false;
   bool inMultiComment = false;
   bool pendingSpace = false;
   bool pendingLinebreak = false;
   bool controlKeywordActive = false;
   bool forceSpaceBeforeNextToken = false;
   char stringChar = '\0';
   char lastSignificant = '\0';
   size_t controlKeywordLength = 0;
   size_t controlKeywordProgress = 0;
   size_t i = 0;

   // --- append helpers keep spacing + keyword state in sync ---
   auto appendChar = [&](char ch) {
      if (forceSpaceBeforeNextToken && !std::isspace(static_cast<unsigned char>(ch))) {
         result += ' ';
         forceSpaceBeforeNextToken = false;
      }

      result += ch;
      if (!std::isspace(static_cast<unsigned char>(ch))) {
         lastSignificant = ch;
         if (controlKeywordActive) {
            ++controlKeywordProgress;
            if (controlKeywordProgress >= controlKeywordLength) {
               controlKeywordActive = false;
               forceSpaceBeforeNextToken = true;
            }
         }
      }
   };

   // --- treat alnum juxtaposition as identifiers needing space ---
   auto needsSpaceBetween = [&](char prev, char current) {
      return isIdentifierBody(prev) && isIdentifierBody(current);
   };

   // --- flag else/catch/finally/while so next token gets a space ---
   auto beginControlKeyword = [&](std::string_view keyword) {
      if (keyword.empty()) {
         return;
      }
      controlKeywordActive = true;
      controlKeywordLength = keyword.size();
      controlKeywordProgress = 0;
      forceSpaceBeforeNextToken = true;
   };

   // --- newline boundary decides semicolon insertion rules ---
   auto handleLinebreakBoundary = [&](char upcoming, std::string_view keyword) {
      if (upcoming == '\0') {
         return;
      }

      if (isStatementEndChar(lastSignificant) && isStatementStartChar(upcoming) && !isControlFlowFollower(keyword)) {
         if (lastSignificant != ';') {
            appendChar(';');
            if (isIdentifierStart(upcoming)) {
               forceSpaceBeforeNextToken = true;
            }
         }
         return;
      }

      if (lastSignificant == '}' && isControlFlowFollower(keyword)) {
         beginControlKeyword(keyword);
         return;
      }

      if (needsSpaceBetween(lastSignificant, upcoming) && (result.empty() || result.back() != ' ')) {
         appendChar(' ');
      }
   };

   while (i < js.length()) {
      char current = js[i];
      char next = (i + 1 < js.length()) ? js[i + 1] : '\0';

      // --- trim block comments only at EXTREME level ---
      if (currentLevel == EXTREME) {
         if (!inString && !inRegex && !inSingleComment && current == '/' && next == '*') {
            inMultiComment = true;
            i += 2;
            continue;
         }
         if (inMultiComment) {
            if (current == '*' && next == '/') {
               inMultiComment = false;
               i += 2;
               continue;
            }
            ++i;
            continue;
         }
      }

      // --- strip single-line comments, remember newline boundary ---
      if (!inString && !inRegex && !inMultiComment && current == '/' && next == '/') {
         inSingleComment = true;
         i += 2;
         continue;
      }
      if (inSingleComment) {
         if (current == '\n' || current == '\r') {
            inSingleComment = false;
            pendingLinebreak = true;
         }
         ++i;
         continue;
      }

      // --- string literal boundaries (" ' `) ---
      if (!inRegex && (current == '"' || current == '\'' || current == '`')) {
         if (!inString) {
            inString = true;
            stringChar = current;
         } else if (current == stringChar && (result.empty() || result.back() != '\\')) {
            inString = false;
         }
         appendChar(current);
         ++i;
         continue;
      }

      if (inString) {
         appendChar(current);
         ++i;
         continue;
      }

      // --- whitespace collapsed into pending state ---
      if (std::isspace(static_cast<unsigned char>(current))) {
         if (current == '\n' || current == '\r') {
            pendingLinebreak = true;
            pendingSpace = false;
         } else if (!pendingLinebreak) {
            pendingSpace = true;
         }
         ++i;
         continue;
      }

      // --- newline boundary may inject semicolons or spaces ---
      if (pendingLinebreak) {
         std::string_view keyword = readKeyword(js, i);
         handleLinebreakBoundary(current, keyword);
         pendingLinebreak = false;
         pendingSpace = false;
      } else if (pendingSpace) {
         std::string_view keyword = readKeyword(js, i);
         if (lastSignificant == '}' && isControlFlowFollower(keyword)) {
            beginControlKeyword(keyword);
         } else if (needsSpaceBetween(lastSignificant, current) && (result.empty() || result.back() != ' ')) {
            appendChar(' ');
         }
         pendingSpace = false;
      }

      // --- default: copy token into output ---
      appendChar(current);
      ++i;
   }

   js = result;
}

#include <algorithm>
#include <cctype>
#include <string>
#include "trim.h"

std::string ltrim(const std::string& s) {
   size_t start = 0;
   while (start < s.length() && isWhitespace(s[start])) {
      start++;
   }
   return s.substr(start);
}

std::string rtrim(const std::string& s) {
   size_t end = s.length();
   while (end > 0 && isWhitespace(s[end - 1])) {
      end--;
   }
   return s.substr(0, end);
}

std::string trim(const std::string& s) {
   return ltrim(rtrim(s));
}

bool isWhitespace(char c) {
   return c == ' ' || c == '\n' || c == '\t' || c == '\r';
}

std::string trimWhitespace(const std::string& s) {
   std::string result;
   bool lastWasWhitespace = false;
   std::string trimmed = trim(s);

   for (char c : trimmed) {
      if (isWhitespace(c)) {
         if (!lastWasWhitespace) {
            result += ' ';
            lastWasWhitespace = true;
         }
      } else {
         result += c;
         lastWasWhitespace = false;
      }
   }

   return result;
}

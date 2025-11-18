#include "../HtmlCompressor.h"

#include <algorithm>
#include <cctype>
#include <regex>
#include <vector>

void HtmlCompressor::minifyCSS(std::string& css) {
   if (currentLevel < AGGRESSIVE) return;

   const std::string placeholderPrefix = "___CSS_PH_";
   std::vector<std::string> placeholders;
   placeholders.reserve(16);

   auto makePlaceholder = [&](const std::string& value) {
      std::string token = placeholderPrefix + std::to_string(placeholders.size()) + "___";
      placeholders.push_back(value);
      return token;
   };

   auto isUrlStart = [&](size_t pos) {
      if (pos + 4 > css.size()) return false;
      return (std::tolower(static_cast<unsigned char>(css[pos])) == 'u' &&
            std::tolower(static_cast<unsigned char>(css[pos + 1])) == 'r' &&
            std::tolower(static_cast<unsigned char>(css[pos + 2])) == 'l' &&
            css[pos + 3] == '(');
   };

   std::string working;
   working.reserve(css.size());
   size_t i = 0;
   while (i < css.size()) {
      char ch = css[i];

      if (ch == '"' || ch == '\'') {
         size_t start = i++;
         while (i < css.size()) {
            char current = css[i++];
            if (current == '\\' && i < css.size()) {
               ++i;
               continue;
            }
            if (current == ch) break;
         }
         working += makePlaceholder(css.substr(start, i - start));
         continue;
      }

      if (isUrlStart(i)) {
         size_t start = i;
         i += 4; // Skip "url("
         while (i < css.size()) {
            char current = css[i++];
            if (current == '\\' && i < css.size()) {
               ++i;
               continue;
            }
            if (current == ')') break;
         }
         working += makePlaceholder(css.substr(start, i - start));
         continue;
      }

      working += ch;
      ++i;
   }

   auto stripComments = [](const std::string& input) {
      std::string out;
      out.reserve(input.size());
      size_t idx = 0;
      while (idx < input.size()) {
         if (input[idx] == '/' && idx + 1 < input.size() && input[idx + 1] == '*') {
            size_t end = input.find("*/", idx + 2);
            if (end == std::string::npos) {
               out.append(input.substr(idx));
               break;
            }
            idx = end + 2;
            continue;
         }
         out += input[idx++];
      }
      return out;
   };

   std::string compressed = stripComments(working);

   auto collapseWhitespace = [](const std::string& input) {
      std::string out;
      out.reserve(input.size());
      bool inSpace = false;
      for (char c : input) {
         if (std::isspace(static_cast<unsigned char>(c))) {
            if (!inSpace) {
               out += ' ';
               inSpace = true;
            }
         } else {
            out += c;
            inSpace = false;
         }
      }
      size_t start = out.find_first_not_of(' ');
      if (start == std::string::npos) return std::string();
      size_t end = out.find_last_not_of(' ');
      return out.substr(start, end - start + 1);
   };

   compressed = collapseWhitespace(compressed);

   auto stripSpaceAround = [](std::string& input, char target) {
      if (input.empty()) return;
      std::string out;
      out.reserve(input.size());
      for (size_t idx = 0; idx < input.size(); ++idx) {
         char c = input[idx];
         if (c == ' ') {
            bool drop = false;
            if (!out.empty() && out.back() == target) {
               drop = true;
            } else {
               size_t lookAhead = idx + 1;
               while (lookAhead < input.size() && input[lookAhead] == ' ') ++lookAhead;
               if (lookAhead < input.size() && input[lookAhead] == target) {
                  drop = true;
                  idx = lookAhead - 1;
               }
            }
            if (drop) continue;
         }
         out += c;
      }
      input.swap(out);
   };

   stripSpaceAround(compressed, '{');
   stripSpaceAround(compressed, '}');
   stripSpaceAround(compressed, ';');
   stripSpaceAround(compressed, ':');
   stripSpaceAround(compressed, ',');

   auto stripSemicolonBeforeBrace = [](std::string& input) {
      if (input.empty()) return;
      std::string out;
      out.reserve(input.size());
      for (size_t idx = 0; idx < input.size(); ++idx) {
         char c = input[idx];
         if (c == ';') {
            size_t lookAhead = idx + 1;
            while (lookAhead < input.size() && std::isspace(static_cast<unsigned char>(input[lookAhead]))) ++lookAhead;
            if (lookAhead < input.size() && input[lookAhead] == '}') {
               continue;
            }
         }
         out += c;
      }
      input.swap(out);
   };

   stripSemicolonBeforeBrace(compressed);

   const std::regex zeroUnits(R"(\b0+(px|em|rem|%|pt|pc|in|cm|mm|ex|ch|vw|vh|vmin|vmax)\b)", std::regex_constants::icase);
   compressed = std::regex_replace(compressed, zeroUnits, "0");

   const std::regex leadingZeroDecimals(R"(\b0+(\.\d+))");
   compressed = std::regex_replace(compressed, leadingZeroDecimals, "$1");

   const std::regex rgbPattern(R"(rgb\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\))", std::regex_constants::icase);
   std::string rgbProcessed;
   rgbProcessed.reserve(compressed.size());
   std::string::const_iterator searchStart = compressed.cbegin();
   std::smatch match;
   while (std::regex_search(searchStart, compressed.cend(), match, rgbPattern)) {
      rgbProcessed.append(searchStart, match[0].first);

      auto toHex = [](int value) {
         const char* hex = "0123456789abcdef";
         std::string res(2, '0');
         res[0] = hex[(value >> 4) & 0xF];
         res[1] = hex[value & 0xF];
         return res;
      };

      int r = std::stoi(match[1].str());
      int g = std::stoi(match[2].str());
      int b = std::stoi(match[3].str());
      r = std::clamp(r, 0, 255);
      g = std::clamp(g, 0, 255);
      b = std::clamp(b, 0, 255);

      std::string rHex = toHex(r);
      std::string gHex = toHex(g);
      std::string bHex = toHex(b);

      bool canShorten = (rHex[0] == rHex[1]) && (gHex[0] == gHex[1]) && (bHex[0] == bHex[1]);
      if (canShorten) {
         rgbProcessed += '#';
         rgbProcessed.push_back(rHex[0]);
         rgbProcessed.push_back(gHex[0]);
         rgbProcessed.push_back(bHex[0]);
      } else {
         rgbProcessed += '#';
         rgbProcessed += rHex + gHex + bHex;
      }

      searchStart = match[0].second;
   }
   rgbProcessed.append(searchStart, compressed.cend());
   compressed.swap(rgbProcessed);

   for (size_t idx = 0; idx < placeholders.size(); ++idx) {
      const std::string placeholder = placeholderPrefix + std::to_string(idx) + "___";
      size_t pos = 0;
      while ((pos = compressed.find(placeholder, pos)) != std::string::npos) {
         compressed.replace(pos, placeholder.length(), placeholders[idx]);
         pos += placeholders[idx].length();
      }
   }

   css = compressed;
}

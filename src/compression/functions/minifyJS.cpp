#include <cctype>
#include <string_view>
#include <filesystem>
#include <fstream>
#include <vector>
#include <cstdlib>
#include <chrono>
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

   std::string toLower(std::string value) {
      for (auto& ch : value) {
         ch = static_cast<char>(std::tolower(static_cast<unsigned char>(ch)));
      }
      return value;
   }

   std::string makeTempFilename(const std::string& prefix, const std::string& extension) {
      const auto now = std::chrono::high_resolution_clock::now().time_since_epoch().count();
      return prefix + std::to_string(now) + extension;
   }

   struct Placeholder {
      std::string token;
      std::string original;
   };

   std::string protectPhpVars(const std::string& input, std::vector<Placeholder>& placeholders) {
      std::string output;
      output.reserve(input.size());

      for (size_t i = 0; i < input.size(); ++i) {
         if (input[i] == '{' && i + 2 < input.size() && input[i + 1] == '$') {
            size_t j = i + 2;
            if (std::isalpha(static_cast<unsigned char>(input[j])) || input[j] == '_') {
               ++j;
               while (j < input.size()) {
                  char ch = input[j];
                  if (std::isalnum(static_cast<unsigned char>(ch)) || ch == '_') {
                     ++j;
                     continue;
                  }
                  break;
               }

               if (j < input.size() && input[j] == '}') {
                  std::string original = input.substr(i, j - i + 1);
                  std::string token = "__PHPSPA_PHP_VAR_" + std::to_string(placeholders.size()) + "__";
                  placeholders.push_back({token, original});
                  output.append(token);
                  i = j;
                  continue;
               }
            }
         }

         output.push_back(input[i]);
      }

      return output;
   }

   std::string restorePhpVars(std::string input, const std::vector<Placeholder>& placeholders) {
      for (const auto& entry : placeholders) {
         size_t pos = 0;
         while ((pos = input.find(entry.token, pos)) != std::string::npos) {
            input.replace(pos, entry.token.size(), entry.original);
            pos += entry.original.size();
         }
      }

      return input;
   }

   std::string getBundlerPath() {
      #if defined(_WIN32)
            char* envPath = nullptr;
            size_t length = 0;
            if (_dupenv_s(&envPath, &length, "PHPSPA_JS_BUNDLER") == 0 && envPath != nullptr && envPath[0] != '\0') {
               std::string value = envPath;
               free(envPath);
               return value;
            }
            if (envPath != nullptr) {
               free(envPath);
            }
      #else
            const char* envPath = std::getenv("PHPSPA_JS_BUNDLER");
            if (envPath != nullptr && envPath[0] != '\0') {
               return envPath;
            }
      #endif

      return "npx esbuild";
   }


   bool runBundler(const std::string& input, const std::string& scope, int level, std::string& output) {
      std::vector<Placeholder> placeholders;
      std::string prepared = protectPhpVars(input, placeholders);

      std::filesystem::path tempDir = std::filesystem::temp_directory_path();
      std::filesystem::path inputPath = tempDir / makeTempFilename("phpspa_js_", ".js");
      std::filesystem::path outputPath = tempDir / makeTempFilename("phpspa_js_out_", ".js");

      {
         std::ofstream out(inputPath, std::ios::binary);
         if (!out.is_open()) {
            return false;
         }
         out << prepared;
      }

      const std::string bundler = getBundlerPath();
      const std::string normalizedScope = toLower(scope);

      std::string command = bundler;
      command += " \"" + inputPath.string() + "\"";
      command += " --outfile=\"" + outputPath.string() + "\"";
      command += " --platform=browser --log-level=error";

      if (normalizedScope == "scoped") {
         if (level == 3) { // EXTREME
            command += " --bundle --minify --tree-shaking=true --format=iife";
         } else { // AGGRESSIVE
            command += " --bundle --minify-whitespace --tree-shaking=true --format=iife";
         }
      } else { // global
         if (level == 3) { // EXTREME
            command += " --minify-syntax --minify-whitespace --keep-names --tree-shaking=false";
         } else { // AGGRESSIVE
            command += " --minify-whitespace --keep-names --tree-shaking=false";
         }
      }

      int status = std::system(command.c_str());
      
      if (status != 0 || !std::filesystem::exists(outputPath)) {
         std::error_code ec;
         std::filesystem::remove(inputPath, ec);
         std::filesystem::remove(outputPath, ec);
         return false;
      }

      std::ifstream in(outputPath, std::ios::binary);
      if (!in.is_open()) {
         std::error_code ec;
         std::filesystem::remove(inputPath, ec);
         std::filesystem::remove(outputPath, ec);
         return false;
      }

      std::string bundled((std::istreambuf_iterator<char>(in)), std::istreambuf_iterator<char>());
      in.close();
      
      output = restorePhpVars(bundled, placeholders);

      std::error_code ec;
      std::filesystem::remove(inputPath, ec);
      std::filesystem::remove(outputPath, ec);

      return true;
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

void HtmlCompressor::minifyJS(std::string& js, const std::string& scope) {
   if (currentLevel < AGGRESSIVE) {
      return;
   }

   // BASIC level: use internal minifier only
   if (currentLevel == BASIC) {
      minifyJS(js);
      return;
   }

   // AGGRESSIVE and EXTREME: use esbuild bundler
   std::string bundled;
   if (runBundler(js, scope, currentLevel, bundled)) {
      js = bundled;
      return;
   }

   // fallback to internal minifier if bundler fails
   minifyJS(js);
}

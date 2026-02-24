#include <cctype>
#include <string_view>
#include <filesystem>
#include <fstream>
#include <vector>
#include <cstdlib>
#include <cstring>
#ifdef _WIN32
#include <windows.h>
#endif
#include <chrono>
#include "../HtmlCompressor.h"

namespace {

#ifdef _WIN32
   int runCommandHiddenWindows(const std::string& command, DWORD timeoutMillis = 20000) {
      std::string cmdLine = "cmd.exe /C " + command;
      std::vector<char> buffer(cmdLine.begin(), cmdLine.end());
      buffer.push_back('\0');

      STARTUPINFOA si;
      PROCESS_INFORMATION pi;
      ZeroMemory(&si, sizeof(si));
      ZeroMemory(&pi, sizeof(pi));
      si.cb = sizeof(si);
      si.dwFlags = STARTF_USESHOWWINDOW;
      si.wShowWindow = SW_HIDE;

      BOOL created = CreateProcessA(
         nullptr,
         buffer.data(),
         nullptr,
         nullptr,
         FALSE,
         CREATE_NO_WINDOW,
         nullptr,
         nullptr,
         &si,
         &pi
      );

      if (!created) {
         return -1;
      }

      DWORD waitResult = WaitForSingleObject(pi.hProcess, timeoutMillis);
      
      if (waitResult == WAIT_TIMEOUT) {
         TerminateProcess(pi.hProcess, 1);
         CloseHandle(pi.hProcess);
         CloseHandle(pi.hThread);
         return -2; // Signal timeout
      }

      DWORD exitCode = 0;
      GetExitCodeProcess(pi.hProcess, &exitCode);

      CloseHandle(pi.hProcess);
      CloseHandle(pi.hThread);

      return static_cast<int>(exitCode);
   }
#endif

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

   void appendDebug(char* buffer, const std::string& message) {
      if (!buffer) return;
      size_t currentLen = strlen(buffer);
      if (currentLen >= 1023) return;

      std::string formatted = (currentLen == 0 ? "" : "\n") + message;
      strncat(buffer, formatted.c_str(), 1023 - currentLen);
      buffer[1023] = '\0';
   }

   std::string getBundlerPath(char* debugOutput) {
      #if defined(_WIN32)
            char* envPath = nullptr;
            size_t length = 0;
            if (_dupenv_s(&envPath, &length, "PHPSPA_JS_BUNDLER") == 0 && envPath != nullptr && envPath[0] != '\0') {
               std::string value = envPath;
               free(envPath);
               if (debugOutput) {
                  appendDebug(debugOutput, "Using env: " + value);
               }
               return value;
            }
            if (envPath != nullptr) {
               free(envPath);
            }
      #else
            const char* envPath = std::getenv("PHPSPA_JS_BUNDLER");
            if (envPath != nullptr && envPath[0] != '\0') {
               if (debugOutput) {
                  appendDebug(debugOutput, "Using env: " + std::string(envPath));
               }
               return envPath;
            }
      #endif

      // Try global esbuild
      #ifdef _WIN32
         if (runCommandHiddenWindows("esbuild --version", 2000) == 0) {
            appendDebug(debugOutput, "Using global: esbuild");
            return "esbuild";
         }
      #else
         if (std::system("esbuild --version > /dev/null 2>&1") == 0) {
            appendDebug(debugOutput, "Using global: esbuild");
            return "esbuild";
         }
      #endif

      appendDebug(debugOutput, "Using fallback: npx esbuild, consider installing esbuild globally for better performance");
      return "npx --yes esbuild";
   }


   bool runBundler(const std::string& input, const std::string& scope, int level, std::string& output, char* debugOutput) {
      std::filesystem::path tempDir = std::filesystem::temp_directory_path();
      std::filesystem::path inputPath = tempDir / makeTempFilename("phpspa_js_", ".js");
      std::filesystem::path outputPath = tempDir / makeTempFilename("phpspa_js_out_", ".js");

      appendDebug(debugOutput, "Input: " + inputPath.string());

      {
         std::ofstream out(inputPath, std::ios::binary);
         if (!out.is_open()) {
            return false;
         }
         out << input;
         out.flush();
         out.close();
      }

      const std::string bundler = getBundlerPath(debugOutput);
      const std::string normalizedScope = toLower(scope);

      std::string command = bundler;
      command += " \"" + inputPath.string() + "\"";
      command += " --outfile=\"" + outputPath.string() + "\"";
      command += " --platform=browser --log-level=error";

      if (normalizedScope == "scoped") {
         if (level == 3) { // EXTREME
            command += " --bundle --minify --minify-identifiers --tree-shaking=true --format=iife";
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

      appendDebug(debugOutput, "Running: " + command);

      std::filesystem::path errorPath = tempDir / makeTempFilename("phpspa_js_err_", ".txt");

      // Run bundler
      #ifdef _WIN32
         command += " 2>\"" + errorPath.string() + "\"";
         int status = runCommandHiddenWindows(command, 20000); // 20 second timeout
      #else
         command = "timeout 20s " + command + " 2>\"" + errorPath.string() + "\"";
         int status = std::system(command.c_str());
      #endif

      if (status != 0 || !std::filesystem::exists(outputPath)) {
         std::string errorMsg;
         if (std::filesystem::exists(errorPath)) {
            std::ifstream errFile(errorPath, std::ios::binary);
            if (errFile.is_open()) {
               errorMsg = std::string((std::istreambuf_iterator<char>(errFile)), std::istreambuf_iterator<char>());
               errFile.close();
            }
         }
         std::string reason = (status != 0) ? "Status code: " + std::to_string(status) : "Output file not found";
         appendDebug(debugOutput, "Bundler failed! " + reason + ". Error: " + errorMsg);

         std::error_code ec;
         std::filesystem::remove(inputPath, ec);
         std::filesystem::remove(outputPath, ec);
         std::filesystem::remove(errorPath, ec);
         return false;
      }

      std::error_code ec_clean;
      std::filesystem::remove(errorPath, ec_clean);

      std::ifstream in(outputPath, std::ios::binary);
      if (!in.is_open()) {
         std::error_code ec;
         std::filesystem::remove(inputPath, ec);
         std::filesystem::remove(outputPath, ec);
         return false;
      }

      std::string bundled((std::istreambuf_iterator<char>(in)), std::istreambuf_iterator<char>());
      in.close();
      
      output = bundled;

      std::error_code ec;
      std::filesystem::remove(inputPath, ec);
      std::filesystem::remove(outputPath, ec);

      return true;
   }

} // namespace

void HtmlCompressor::minifyJS(std::string& js, const std::string& scope) {
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


   if (scope == "scoped" && !result.empty()) {
      // trim the trailing ";" and whitespace
      while (!result.empty() && (std::isspace(static_cast<unsigned char>(result.back())) || result.back() == ';')) {
         result.pop_back();
      }
      result = "(()=>{" + result + ";})();";
   }
   js = result;
}

void HtmlCompressor::minifyJS(std::string& js, const std::string& scope, char* debugOutput) {
   // BASIC level: use internal minifier only
   if (currentLevel == BASIC) {
      if (debugOutput) {
         std::string debugStr = "Using internal minifier for " + scope + " (Level: BASIC)";
         strncpy(debugOutput, debugStr.c_str(), 1023);
         debugOutput[1023] = '\0';
      }
      minifyJS(js, scope);
      return;
   }

   if (debugOutput) {
      debugOutput[0] = '\0';
   }

   // AGGRESSIVE and EXTREME: use esbuild bundler
   std::string bundled;
   if (runBundler(js, scope, currentLevel, bundled, debugOutput)) {
      js = bundled;
      return;
   }

   // fallback to internal minifier if bundler fails
   if (debugOutput && debugOutput[0] == '\0') {
      std::string debugStr = "Esbuild failed (no info), falling back to internal minifier for " + scope;
      strncpy(debugOutput, debugStr.c_str(), 1023);
      debugOutput[1023] = '\0';
   }
   minifyJS(js, scope);
}

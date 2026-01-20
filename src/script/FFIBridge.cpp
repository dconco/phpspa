#include "../compression/HtmlCompressor.h"

#include <cstdlib>
#include <cstring>
#include <string>

#if defined(_WIN32) || defined(_WIN64)
#define PHPSPA_EXPORT __declspec(dllexport)
#else
#define PHPSPA_EXPORT __attribute__((visibility("default")))
#endif

extern "C" {
   PHPSPA_EXPORT char* phpspa_compress_html(const char* input, int level, const char* type, size_t* out_len) {
      if (!input || !out_len) return nullptr;

      HtmlCompressor::currentLevel = static_cast<HtmlCompressor::Level>(level);

      // Reserve to avoid reallocs during compression
      std::string result;
      result.reserve(strlen(input));

      try {
         result = input;
         if (strcmp(type, "HTML") == 0) {
            result = HtmlCompressor::compress(input);
         } else {
            std::string content{input};

            if (strcmp(type, "CSS") == 0) {
               HtmlCompressor::minifyCSS(content);
            } else if (strcmp(type, "JS") == 0) {
               HtmlCompressor::minifyJS(content);
            }

            result = content;
         }
      } catch (...) {
         return nullptr;
      }

      *out_len = result.size();

      // allocate once
      char* buffer = (char*) malloc(result.size() + 1);
      if (!buffer) return nullptr;

      memcpy(buffer, result.c_str(), result.size() + 1);

      return buffer;
   }

   PHPSPA_EXPORT char* phpspa_compress_html_ex(const char* input, int level, const char* type, const char* scope, size_t* out_len) {
      if (!input || !out_len) return nullptr;

      HtmlCompressor::currentLevel = static_cast<HtmlCompressor::Level>(level);

      std::string result;
      result.reserve(strlen(input));

      try {
         result = input;
         if (strcmp(type, "HTML") == 0) {
            result = HtmlCompressor::compress(input);
         } else {
            std::string content{input};

            if (strcmp(type, "CSS") == 0) {
               HtmlCompressor::minifyCSS(content);
            } else if (strcmp(type, "JS") == 0) {
               result = scope;
               std::string scopeValue = (scope == nullptr || scope[0] == '\0') ? "global" : scope;
               HtmlCompressor::minifyJS(content, scopeValue);
            }

            result = content;
         }
      } catch (...) {
         return nullptr;
      }

      *out_len = result.size();

      char* buffer = (char*) malloc(result.size() + 1);
      if (!buffer) return nullptr;

      memcpy(buffer, result.c_str(), result.size() + 1);

      return buffer;
   }

   PHPSPA_EXPORT void phpspa_free_string(char* buffer) {
      free(buffer);
   }
}

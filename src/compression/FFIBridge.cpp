#include "HtmlCompressor.h"

#include <cstdlib>
#include <cstring>
#include <string>

#if defined(_WIN32) || defined(_WIN64)
#define PHPSPA_EXPORT __declspec(dllexport)
#else
#define PHPSPA_EXPORT __attribute__((visibility("default")))
#endif

extern "C" {
   PHPSPA_EXPORT char* phpspa_compress_html(const char* input, int level) {
      if (input == nullptr) {
         return nullptr;
      }

      HtmlCompressor::Level compressorLevel = HtmlCompressor::BASIC;

      if (level >= HtmlCompressor::BASIC && level <= HtmlCompressor::EXTREME) {
         compressorLevel = static_cast<HtmlCompressor::Level>(level);
      }

      std::string result;

      try {
         result = HtmlCompressor::compress(std::string(input), compressorLevel);
      } catch (...) {
         return nullptr;
      }

      const std::size_t outputSize = result.size() + 1;
      char* buffer = static_cast<char*>(std::malloc(outputSize));

      if (buffer == nullptr) {
         return nullptr;
      }

      std::memcpy(buffer, result.c_str(), outputSize);
      return buffer;
   }

   PHPSPA_EXPORT void phpspa_free_string(char* buffer) {
      if (buffer != nullptr) {
         std::free(buffer);
      }
   }
}

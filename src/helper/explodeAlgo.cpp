#include <list>
#include "explode.h"

std::list<std::string> explode(const std::string& delimiter, const std::string &str) {
   std::list<std::string> elements;
   size_t start = 0;
   size_t end = str.find(delimiter);

   while (end != std::string::npos) {
      elements.push_back(str.substr(start, end - start));
      start = end + delimiter.length();
      end = str.find(delimiter, start);
   }

   elements.emplace_back(str.substr(start));
   return elements;
}
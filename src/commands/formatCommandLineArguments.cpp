#include <string>
#include <iostream>
#include <vector>
#include <map>
#include "formatCommandLineArguments.hh"

std::map<std::string, std::string> formatCommandLineArguments(int argc, char* argv[]) {
   std::string commands = argv[0];
   std::map<std::string, std::string> arguments;

   for (int i = 1; i < argc; i++) {
      std::string opt = argv[i];

      if (opt.starts_with("--")) {
         if (i + 1 < argc) {
            arguments[opt.substr(2)] = argv[i + 1];
            i++;
         }
      } else if (opt.rfind('-', 0) == 0 && opt.length() > 1) {
         std::string key(1, opt[1]);

         if (i + 1 < argc && !std::string(argv[i + 1]).starts_with('-')) {
            arguments[key] = argv[i + 1];
            i++;
         } else {
            arguments[key] = "";
         }
      }
   }

   return arguments;
}

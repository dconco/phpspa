#include <iostream>
#include <fstream>
#include <string>
#include "commands/formatCommandLineArguments.hh"
#include "compression/HtmlCompressor.h"

int main(int argc, char* argv[]) {
    std::map<std::string, std::string> arguments = formatCommandLineArguments(argc, argv);

    if (!arguments.contains("level") || !(arguments.contains("content") || arguments.contains("file"))) {
        std::cout << "--level && --content/file is required" << std::endl;
        return 1;
    }

    std::string htmlContent;

    if (arguments.contains("file")) {
        std::string filePath = arguments["file"];
        std::ifstream fileStream(filePath);

        if (!fileStream.is_open()) {
            std::cout << "Failed to open file: " << filePath << std::endl;
            return 1;
        }

        htmlContent = std::string((std::istreambuf_iterator<char>(fileStream)),
                                 std::istreambuf_iterator<char>());
    } else if (arguments["content"] == "w") {
        htmlContent = std::string((std::istreambuf_iterator<char>(std::cin)),
                                   std::istreambuf_iterator<char>());
    } else {
        htmlContent = arguments["content"];
    }

    HtmlCompressor::Level compressorLevel = static_cast<HtmlCompressor::Level>(std::stoi(arguments["level"]));

    if (compressorLevel < HtmlCompressor::BASIC || compressorLevel > HtmlCompressor::EXTREME) {
        std::cout << "Compressor level must be between 1 and 3." << std::endl;
        return 1;
    }

    htmlContent = HtmlCompressor::compress(htmlContent, compressorLevel);
    std::cout << htmlContent << std::endl;
    return 0;
}

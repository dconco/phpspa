#include <iostream>
#include <string>
#include "commands/formatCommandLineArguments.hh"
#include "compression/HtmlCompressor.h"

int main(int argc, char* argv[]) {
    enum CompressorLevel {
        LEVEL_1 = 1,
        LEVEL_2 = 2,
        LEVEL_3 = 3
    };

    std::map<std::string, std::string> arguments = formatCommandLineArguments(argc, argv);

    if (!arguments.contains("level") || !arguments.contains("content")) {
        std::cout << "--level && --content is required" << std::endl;
        return 1;
    }

    std::string htmlContent = arguments["content"]; // --- HTML Content ---
    int compressorLevel = std::stoi(arguments["level"]); // --- Compressor Level ---

    if (compressorLevel < HtmlCompressor::Level::BASIC || compressorLevel > HtmlCompressor::Level::EXTREME) {
        std::cout << "Compressor level must be between 1 and 3." << std::endl;
        return 1;
    }

    std::cout << "Compressor Level: " << compressorLevel << std::endl;
    std::cout << "HTML Content: " << htmlContent << std::endl;
    return 0;
}

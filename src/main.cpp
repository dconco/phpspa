#include <iostream>
#include <string>
#include "commands/formatCommandLineArguments.hh"
#include "compression/HtmlCompressor.h"

int main(int argc, char* argv[]) {
    std::map<std::string, std::string> arguments = formatCommandLineArguments(argc, argv);

    if (!arguments.contains("level") || !arguments.contains("content")) {
        std::cout << "--level && --content is required" << std::endl;
        return 1;
    }

    std::string htmlContent = arguments["content"];
    HtmlCompressor::Level compressorLevel = static_cast<HtmlCompressor::Level>(std::stoi(arguments["level"]));

    if (compressorLevel < HtmlCompressor::BASIC || compressorLevel > HtmlCompressor::EXTREME) {
        std::cout << "Compressor level must be between 1 and 3." << std::endl;
        return 1;
    }

    htmlContent = HtmlCompressor::compress(htmlContent, compressorLevel);

    std::cout << "Compressor Level: " << compressorLevel << std::endl;
    std::cout << "HTML Content: " << htmlContent << std::endl;
    return 0;
}

#include <string>
#pragma once

// --- Trim from start (left) ---
std::string ltrim(const std::string& s);

// --- Trim from end (right) ---
std::string rtrim(const std::string& s);

// --- Trim both sides ---
std::string trim(const std::string& s);

bool isWhitespace(char c);

std::string trimWhitespace(const std::string& s);
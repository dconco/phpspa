@echo off
echo Building PhpSPA C++ Compressor...
echo.

REM Compile with g++ (MinGW)
g++ main.cpp -o ../bin/compressor.exe -std=c++17 -O2

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ✓ Build successful!
    echo Binary created: ../bin/compressor.exe
    echo.
    echo Testing the binary...
    echo.
    ../bin/compressor.exe
) else (
    echo.
    echo ✗ Build failed!
    echo Make sure you have g++ installed (MinGW or similar)
)

pause

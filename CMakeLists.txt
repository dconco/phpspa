cmake_minimum_required(VERSION 3.10)
project(PhpSPA)

# Set C++ standard
set(CMAKE_CXX_STANDARD 20)

# Size optimization flags
if(CMAKE_BUILD_TYPE STREQUAL "Release")
    if(MSVC)
        # MSVC: Optimize for size and speed
        add_compile_options(/O2 /Oi /Ot /GL)  # O2=optimize, Oi=intrinsics, Ot=favor speed, GL=whole program
        set(CMAKE_EXE_LINKER_FLAGS_RELEASE "${CMAKE_EXE_LINKER_FLAGS_RELEASE} /LTCG /OPT:REF /OPT:ICF")
    else()
        # GCC/Clang (Linux/macOS): Strip symbols and optimize
        add_compile_options(-Os -ffunction-sections -fdata-sections)  # Os=optimize for size
        set(CMAKE_EXE_LINKER_FLAGS "${CMAKE_EXE_LINKER_FLAGS} -s -Wl,--gc-sections")  # Strip + remove unused sections
    endif()
endif()

# Force ccache usage
find_program(CCACHE_PROGRAM ccache)
if(CCACHE_PROGRAM)
   set(CMAKE_CXX_COMPILER_LAUNCHER "${CCACHE_PROGRAM}")
   set(CMAKE_C_COMPILER_LAUNCHER "${CCACHE_PROGRAM}")
else()
   message("ccache not found")
endif()

# Add DEBUG options for debug builds
if(MSVC)
    set(CMAKE_CXX_FLAGS_DEBUG "/Zi /Od")
    set(CMAKE_CXX_FLAGS_RELEASE "/O2 /DNDEBUG /GL")
else()
    set(CMAKE_CXX_FLAGS_DEBUG "-g -O0")
    set(CMAKE_CXX_FLAGS_RELEASE "-O3 -DNDEBUG")
endif()

# MSVC-specific optimizations
if(MSVC)
    set(CMAKE_EXE_LINKER_FLAGS_RELEASE "${CMAKE_EXE_LINKER_FLAGS_RELEASE} /LTCG")  # Link-time code generation
endif()

# Find all C++ source files recursively in src/ and subdirectories
file(GLOB_RECURSE SOURCES 
   "src/*.cpp"
   "src/*.cxx" 
   "src/*.cc"
   "src/*.c++"
   "src/*.cp"
)

# Find all C source files recursively if you have any
file(GLOB_RECURSE C_SOURCES "src/*.c")

# Find header files recursively (for IDE support, not compilation)
file(GLOB_RECURSE HEADERS 
   "src/*.hh"
   "src/*.hpp"
   "src/*.hxx"
   "src/*.h++"
   "src/*.hp"
)

# Create executable with all source files
add_executable(compressor ${SOURCES} ${C_SOURCES} ${HEADERS})
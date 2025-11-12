cmake_minimum_required(VERSION 3.10)
project(PhpSPA)

# Set C++ standard
set(CMAKE_CXX_STANDARD 17)

# Force ccache usage
find_program(CCACHE_PROGRAM ccache)
if(CCACHE_PROGRAM)
   set(CMAKE_CXX_COMPILER_LAUNCHER "${CCACHE_PROGRAM}")
   set(CMAKE_C_COMPILER_LAUNCHER "${CCACHE_PROGRAM}")
else()
   message("ccache not found")
endif()

# Add DEBUG options for debug builds
set(CMAKE_CXX_FLAGS_DEBUG "-g -O0")
set(CMAKE_CXX_FLAGS_RELEASE "-O2")

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
add_executable(phpspa-app ${SOURCES} ${C_SOURCES} ${HEADERS})
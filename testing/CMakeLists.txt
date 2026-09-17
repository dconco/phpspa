# -----------------------------------------------------------------------------
# C++ compressor test suite.
#
# Enabled only when the root project is configured with -DBUILD_TESTING_CPP=ON.
# Builds a static library from the project sources (excluding src/main.cpp,
# which defines the CLI's own main() and would clash with the test runner's
# main()) and links each test translation unit against it. Each test executable
# is registered with add_test() so ctest can drive the suite.
# -----------------------------------------------------------------------------

# Collect the same recursive source set the root build uses so the test static
# library picks up every dependency the compression sources need (helper/
# explode, utils/trim, etc.) without a brittle hand-maintained file list.
file(GLOB_RECURSE TEST_LIB_SOURCES
   "${CMAKE_SOURCE_DIR}/src/*.cpp"
   "${CMAKE_SOURCE_DIR}/src/*.cxx"
   "${CMAKE_SOURCE_DIR}/src/*.cc"
   "${CMAKE_SOURCE_DIR}/src/*.c++"
   "${CMAKE_SOURCE_DIR}/src/*.cp"
   "${CMAKE_SOURCE_DIR}/src/*.c"
)

# Exclude the CLI entry point: it provides its own int main() which would
# conflict with the test harness runner main().
list(REMOVE_ITEM TEST_LIB_SOURCES "${CMAKE_SOURCE_DIR}/src/main.cpp")

# Some tests (e.g. test_concurrency.cpp) use std::thread, which on several
# toolchains (notably Linux/pthread) requires linking against the platform
# threading library. Threads::Threads resolves to -pthread on Linux and is a
# harmless no-op on Windows (MSVC/MinGW threads are built in). Scoped to the
# test build only -- the default `compressor` shared library (root CMakeLists)
# is unaffected.
find_package(Threads REQUIRED)

# Static library the test executables link against.
add_library(compressor_testlib STATIC ${TEST_LIB_SOURCES})

# Activate the compile-time test seam in minifyJS.cpp (the bundler-decision
# observers under `#ifdef PHPSPA_TESTING`). PUBLIC so linking test targets also
# see PHPSPA_TESTING when they include the seam header. This ONLY affects the
# test static library; the default `compressor` shared library is built by the
# root CMakeLists.txt without this definition and is therefore unchanged.
target_compile_definitions(compressor_testlib PUBLIC PHPSPA_TESTING)

# The compression sources include headers via relative paths (e.g.
# "../HtmlCompressor.h"), so no extra include dirs are strictly required, but
# expose src/ for tests that include compressor headers directly.
target_include_directories(compressor_testlib PUBLIC "${CMAKE_SOURCE_DIR}/src")

# On Windows, minifyJS.cpp pulls in <windows.h> and spawns processes; link the
# platform libraries it needs. (No-op / harmless on non-Windows toolchains.)
if(WIN32)
   target_link_libraries(compressor_testlib PUBLIC kernel32 user32)
endif()

# Link the threading library PUBLICly so every test executable that links
# compressor_testlib (e.g. test_concurrency) inherits it and std::thread builds
# on Linux (pthread) and Windows alike.
target_link_libraries(compressor_testlib PUBLIC Threads::Threads)

# Helper: build a test executable from a single .cpp, link it against the
# compressor static lib and the header-only test framework, and register it
# with ctest.
function(phpspa_add_test test_name test_source)
   add_executable(${test_name} ${test_source})
   target_include_directories(${test_name} PRIVATE "${CMAKE_CURRENT_SOURCE_DIR}")
   target_link_libraries(${test_name} PRIVATE compressor_testlib)
   add_test(NAME ${test_name} COMMAND ${test_name})
endfunction()

# Registered tests. Later tasks add more test translation units here.
phpspa_add_test(test_placeholder test_placeholder.cpp)

# Task 2: bug condition exploration test (Property 1). EXPECTED TO FAIL on the
# unfixed code -- the failure confirms the bug; it passes unchanged after the
# fix (tasks 6-9).
phpspa_add_test(test_bug_exploration test_bug_exploration.cpp)

# Task 3: preservation characterization tests (Property 2). These pin down the
# CURRENT native-minifier / minifyCSS / compress() behavior and are EXPECTED TO
# PASS on the unfixed code, establishing the baseline the fix must preserve.
phpspa_add_test(test_minify_js test_minify_js.cpp)
phpspa_add_test(test_minify_css test_minify_css.cpp)
phpspa_add_test(test_compress test_compress.cpp)

# Task 4: concurrency / thread-safety test (Property 2 - Preservation). Spawns
# many threads calling the process-free native minifyJS and compress() seams
# with a fixed currentLevel and asserts each thread's output matches the
# single-threaded reference. EXPECTED TO PASS on the unfixed code (baseline
# concurrent-read safety with a fixed level). Links Threads::Threads via
# compressor_testlib for std::thread on Linux/pthread.
phpspa_add_test(test_concurrency test_concurrency.cpp)

# Task 5: temp-filename uniqueness test (Property 2 - Preservation). Spawns many
# threads calling makeTempFilename (exposed via the PHPSPA_TESTING seam) and
# asserts every generated name is unique (documents design root cause 4). The
# assertion encodes the POST-FIX expectation (zero duplicates), so it MAY FAIL
# on the unfixed clock-only generator if the CI clock is coarse -- that is
# acceptable for this pre-fix task; Task 9's hardening makes it reliably pass
# and Task 12 re-runs it. Links Threads::Threads via compressor_testlib.
phpspa_add_test(test_temp_filename test_temp_filename.cpp)

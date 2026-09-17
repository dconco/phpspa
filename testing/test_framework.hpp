#ifndef PHPSPA_TEST_FRAMEWORK_HPP
#define PHPSPA_TEST_FRAMEWORK_HPP

// -----------------------------------------------------------------------------
// phpSPA C++ test framework
//
// A single-header, dependency-free assertion harness for the compressor tests.
//
// Features:
//   - CHECK(cond)         : assert a boolean condition is true
//   - CHECK_EQ(a, b)      : assert a == b (values are streamed into the report)
//   - CHECK_NE(a, b)      : assert a != b
//   - TEST(name) { ... }  : define and self-register a test case
//   - RUN_ALL_TESTS()     : run every registered test; returns non-zero on any
//                           failure (ctest-friendly exit code)
//
// The harness is header-only. Exactly ONE translation unit must define the
// runner main() by defining PHPSPA_TEST_MAIN before including this header (the
// convention here is to let each test file be linked with a small main.cpp, but
// for simplicity a default main() is provided when PHPSPA_TEST_MAIN is defined).
//
// Usage:
//   #define PHPSPA_TEST_MAIN         // in exactly one .cpp per test executable
//   #include "test_framework.hpp"
//
//   TEST(my_case) {
//       CHECK(1 + 1 == 2);
//       CHECK_EQ(std::string("a"), "a");
//   }
// -----------------------------------------------------------------------------

#include <cstddef>
#include <functional>
#include <iostream>
#include <sstream>
#include <string>
#include <vector>

namespace phpspa_test {

// A single registered test case: a human-readable name plus its body.
struct TestCase {
   std::string name;
   std::function<void(struct TestContext&)> fn;
};

// Per-test execution state. Assertions record failures here rather than
// aborting, so a single test reports as many failures as it hits.
struct TestContext {
   std::string current_test;
   int failed_checks = 0;

   void record_failure(const std::string& expr,
                       const std::string& file,
                       int line,
                       const std::string& detail) {
      ++failed_checks;
      std::cerr << "  [FAIL] " << file << ":" << line << " in \"" << current_test
                << "\"\n"
                << "         " << expr;
      if (!detail.empty()) {
         std::cerr << "\n         " << detail;
      }
      std::cerr << std::endl;
   }
};

// Global registry of all self-registered tests. Function-local static ensures
// a well-defined initialization order across translation units.
inline std::vector<TestCase>& registry() {
   static std::vector<TestCase> tests;
   return tests;
}

// Helper used by the TEST macro to register a case at static-init time.
struct Registrar {
   Registrar(const std::string& name,
             std::function<void(TestContext&)> fn) {
      registry().push_back(TestCase{name, std::move(fn)});
   }
};

// Best-effort stringification for CHECK_EQ / CHECK_NE diagnostics. Falls back
// to a placeholder for types that are not streamable.
template <typename T>
std::string to_report_string(const T& value) {
   std::ostringstream oss;
   oss << value;
   return oss.str();
}

inline std::string to_report_string(const std::string& value) {
   return "\"" + value + "\"";
}

inline std::string to_report_string(const char* value) {
   return std::string("\"") + (value ? value : "(null)") + "\"";
}

inline std::string to_report_string(bool value) {
   return value ? "true" : "false";
}

// Runs every registered test and prints a summary. Returns 0 when all tests
// pass, 1 otherwise (suitable as a process/ctest exit code).
inline int run_all() {
   int failed_tests = 0;
   int total_tests = static_cast<int>(registry().size());

   std::cout << "[==========] Running " << total_tests << " test(s)."
             << std::endl;

   for (auto& test : registry()) {
      TestContext ctx;
      ctx.current_test = test.name;
      std::cout << "[ RUN      ] " << test.name << std::endl;

      try {
         test.fn(ctx);
      } catch (const std::exception& ex) {
         ctx.record_failure("unexpected exception", "<runtime>", 0, ex.what());
      } catch (...) {
         ctx.record_failure("unexpected non-standard exception", "<runtime>", 0,
                            "");
      }

      if (ctx.failed_checks == 0) {
         std::cout << "[       OK ] " << test.name << std::endl;
      } else {
         ++failed_tests;
         std::cout << "[  FAILED  ] " << test.name << " ("
                   << ctx.failed_checks << " check(s) failed)" << std::endl;
      }
   }

   std::cout << "[==========] " << total_tests << " test(s) ran." << std::endl;
   if (failed_tests == 0) {
      std::cout << "[  PASSED  ] All tests passed." << std::endl;
      return 0;
   }
   std::cout << "[  FAILED  ] " << failed_tests << " test(s) failed."
             << std::endl;
   return 1;
}

} // namespace phpspa_test

// -----------------------------------------------------------------------------
// Test-definition macro. Each TEST(name) defines a free function and a
// self-registering Registrar instance. The context parameter `_ctx` is what the
// CHECK macros use to record failures, and is available inside the test body.
// -----------------------------------------------------------------------------
#define PHPSPA_TEST_CONCAT_INNER(a, b) a##b
#define PHPSPA_TEST_CONCAT(a, b) PHPSPA_TEST_CONCAT_INNER(a, b)

#define TEST(test_name)                                                        \
   static void test_name(phpspa_test::TestContext& _ctx);                      \
   static phpspa_test::Registrar PHPSPA_TEST_CONCAT(test_name, _registrar_)(   \
      #test_name, test_name);                                                  \
   static void test_name(phpspa_test::TestContext& _ctx)

// -----------------------------------------------------------------------------
// Assertion macros. They record failures via the enclosing test's context
// (`_ctx`) and continue executing so a test can surface multiple problems.
// -----------------------------------------------------------------------------
#define CHECK(cond)                                                            \
   do {                                                                        \
      if (!(cond)) {                                                           \
         _ctx.record_failure("CHECK(" #cond ") failed", __FILE__, __LINE__,    \
                             "");                                              \
      }                                                                        \
   } while (0)

#define CHECK_EQ(a, b)                                                         \
   do {                                                                        \
      auto&& _phpspa_a = (a);                                                  \
      auto&& _phpspa_b = (b);                                                  \
      if (!(_phpspa_a == _phpspa_b)) {                                         \
         _ctx.record_failure(                                                  \
            "CHECK_EQ(" #a ", " #b ") failed", __FILE__, __LINE__,             \
            "expected: " + phpspa_test::to_report_string(_phpspa_b) +          \
               "\n         actual:   " +                                       \
               phpspa_test::to_report_string(_phpspa_a));                      \
      }                                                                        \
   } while (0)

#define CHECK_NE(a, b)                                                         \
   do {                                                                        \
      auto&& _phpspa_a = (a);                                                  \
      auto&& _phpspa_b = (b);                                                  \
      if (!(_phpspa_a != _phpspa_b)) {                                         \
         _ctx.record_failure(                                                  \
            "CHECK_NE(" #a ", " #b ") failed", __FILE__, __LINE__,             \
            "both values: " + phpspa_test::to_report_string(_phpspa_a));       \
      }                                                                        \
   } while (0)

// Convenience alias so test files can call RUN_ALL_TESTS().
#define RUN_ALL_TESTS() phpspa_test::run_all()

// -----------------------------------------------------------------------------
// Optional default runner main(). Define PHPSPA_TEST_MAIN in exactly one
// translation unit per test executable to emit a main() that runs every
// registered test.
// -----------------------------------------------------------------------------
#ifdef PHPSPA_TEST_MAIN
int main() {
   return phpspa_test::run_all();
}
#endif // PHPSPA_TEST_MAIN

#endif // PHPSPA_TEST_FRAMEWORK_HPP

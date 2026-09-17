// Placeholder test that validates the test harness itself configures, builds,
// and runs under ctest. Real minifier tests are added in later tasks.
#define PHPSPA_TEST_MAIN
#include "test_framework.hpp"

TEST(placeholder_arithmetic_holds) {
   CHECK(1 + 1 == 2);
   CHECK_EQ(2 + 2, 4);
   CHECK_NE(1, 2);
}

TEST(placeholder_string_equality) {
   std::string greeting = "phpspa";
   CHECK_EQ(greeting, std::string("phpspa"));
}

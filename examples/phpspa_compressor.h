// PHP.INI CONFIGURATION
// ffi.enable = "preload"
// ffi.preload = "/absolute/path/to/this/file/php_preload.h"
// opcache.enable = 1

// path to compressor library
#define FFI_LIB "/path/to/project/vendor/dconco/src/bin/libcompressor.so"
#define FFI_SCOPE "phpspa_compressor"

char* phpspa_compress_html(const char* content, int level, int type, size_t* out_len);
char* phpspa_compress_html_esbuild(const char* content, int level, int type, const char* scope, char* debug_output, size_t* out_len);
void phpspa_free_string(char* ptr);
#include <stdio.h>
#include <stdlib.h>
#include <time.h>

int main() {
    time_t t = time(NULL);
    struct tm *tm = localtime(&t);
    char time_str[64];
    strftime(time_str, sizeof(time_str), "%c", tm);

    char *ip = getenv("REMOTE_ADDR");
    if (!ip) ip = "Unknown";

    printf("Content-type: application/json\n\n");
    
    // Manual JSON Formatting
    printf("{\n");
    printf("  \"message\": \"Hello from Team Carlos Lugo\",\n");
    printf("  \"language\": \"C\",\n");
    printf("  \"date\": \"%s\",\n", time_str);
    printf("  \"ip\": \"%s\"\n", ip);
    printf("}\n");
    return 0;
}

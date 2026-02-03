#include <stdio.h>
#include <stdlib.h>
#include <time.h>

int main() {
    // 1. Get Date/Time
    time_t t = time(NULL);
    struct tm *tm = localtime(&t);
    char time_str[64];
    strftime(time_str, sizeof(time_str), "%c", tm);

    // 2. Get IP Address
    char *ip = getenv("REMOTE_ADDR");
    if (ip == NULL) ip = "Unknown";

    // 3. Output
    printf("Content-type: text/html\n\n");
    printf("<html><head><title>Hello C</title></head>");
    printf("<body>");
    printf("<h1>Hello from Team: Carlos Lugo</h1>");
    printf("<h2>Language: C (GCC Compiled)</h2>");
    printf("<p><b>Date Generated:</b> %s</p>", time_str);
    printf("<p><b>Your IP Address:</b> %s</p>", ip);
    printf("</body></html>");
    return 0;
}

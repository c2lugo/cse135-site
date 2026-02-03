#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <time.h>

int main() {
    // --- Metadata ---
    char hostname[256];
    gethostname(hostname, 256);
    char *method = getenv("REQUEST_METHOD");
    char *ip = getenv("REMOTE_ADDR");
    char *ua = getenv("HTTP_USER_AGENT");
    
    time_t t = time(NULL);
    char time_str[64];
    strftime(time_str, sizeof(time_str), "%c", localtime(&t));

    // --- Input Parsing ---
    char *query = NULL;
    char buffer[4096]; // Static buffer for POST data

    if (method && strcmp(method, "POST") == 0) {
        // Read STDIN
        int len = atoi(getenv("CONTENT_LENGTH") ? getenv("CONTENT_LENGTH") : "0");
        if (len > 4095) len = 4095; // Safety cap
        fread(buffer, 1, len, stdin);
        buffer[len] = '\0';
        query = buffer;
    } else {
        // Read Query String
        query = getenv("QUERY_STRING");
    }

    // --- Output ---
    printf("Content-type: text/html\n\n");
    printf("<!DOCTYPE html><html><body style='font-family:sans-serif'>");
    printf("<h1>Echo Response (C)</h1>");
    
    printf("<h3>Server Details:</h3><ul>");
    printf("<li><b>Hostname:</b> %s</li>", hostname);
    printf("<li><b>Time:</b> %s</li>", time_str);
    printf("<li><b>IP:</b> %s</li>", ip ? ip : "?");
    printf("<li><b>UA:</b> %s</li>", ua ? ua : "?");
    printf("</ul>");

    printf("<h3>Received Data:</h3>");
    printf("<p><b>Raw String:</b> %s</p>", query ? query : "None");
    
    // Parse Key-Values (Split by & and =)
    if (query && strlen(query) > 0) {
        printf("<table border='1'><tr><th>Key</th><th>Value</th></tr>");
        char query_copy[4096];
        strncpy(query_copy, query, 4096);
        
        char *pair = strtok(query_copy, "&");
        while (pair) {
            char *val = strchr(pair, '=');
            if (val) {
                *val = '\0'; // Split
                val++;
                printf("<tr><td>%s</td><td>%s</td></tr>", pair, val);
            }
            pair = strtok(NULL, "&");
        }
        printf("</table>");
    }
    printf("</body></html>");
    return 0;
}

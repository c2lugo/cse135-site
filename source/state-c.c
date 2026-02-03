#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <time.h>
#include <unistd.h>

// Helper to find a specific cookie value
void get_cookie_value(const char *cookie_header, const char *key, char *dest, int max_len) {
    if (!cookie_header) return;
    char *start = strstr(cookie_header, key);
    if (start) {
        start += strlen(key) + 1; // Skip "KEY="
        int i = 0;
        while (start[i] && start[i] != ';' && i < max_len - 1) {
            dest[i] = start[i];
            i++;
        }
        dest[i] = '\0';
    }
}

int main() {
    char session_id[128] = {0};
    char new_data[256] = {0};
    int destroy = 0;
    
    // 1. Parse Input (POST)
    char *method = getenv("REQUEST_METHOD");
    if (method && strcmp(method, "POST") == 0) {
        char buffer[1024];
        int len = atoi(getenv("CONTENT_LENGTH") ? getenv("CONTENT_LENGTH") : "0");
        if (len > 1023) len = 1023;
        fread(buffer, 1, len, stdin);
        buffer[len] = '\0';
        
        // Simple string check for action=destroy
        if (strstr(buffer, "action=destroy")) destroy = 1;
        
        // Simple parse for mydata=VALUE
        char *data_pos = strstr(buffer, "mydata=");
        if (data_pos) {
            sscanf(data_pos + 7, "%[^&]", new_data);
        }
    }

    // 2. Handle Cookies
    get_cookie_value(getenv("HTTP_COOKIE"), "MY_C_SESSION", session_id, 128);

    if (destroy) {
        // Delete file
        char filepath[256];
        sprintf(filepath, "/tmp/csess_%s", session_id);
        unlink(filepath);
        
        // Expire Cookie
        printf("Set-Cookie: MY_C_SESSION=deleted; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT\n");
        session_id[0] = '\0'; // Clear ID
    } else if (strlen(session_id) == 0) {
        // Create New Session
        srand(time(NULL));
        sprintf(session_id, "%d", rand());
        printf("Set-Cookie: MY_C_SESSION=%s; path=/;\n", session_id);
    }

    // 3. File I/O (Save/Read)
    char filepath[256];
    sprintf(filepath, "/tmp/csess_%s", session_id);
    
    if (strlen(new_data) > 0 && strlen(session_id) > 0) {
        FILE *f = fopen(filepath, "w");
        if (f) { fprintf(f, "%s", new_data); fclose(f); }
    }

    char current_data[256] = "Nothing set yet";
    if (strlen(session_id) > 0) {
        FILE *f = fopen(filepath, "r");
        if (f) { 
            fgets(current_data, 256, f); 
            fclose(f); 
        }
    }

    // 4. Output
    printf("Content-type: text/html\n\n");
    printf("<html><body><h1>C State Management</h1>");
    printf("<p>Session ID: %s</p>", session_id);
    printf("<p>Saved Data: %s</p>", current_data);
    
    printf("<h3>Update Data:</h3>");
    printf("<form method='POST'><input type='text' name='mydata'><input type='submit' value='Save'></form>");
    
    printf("<form method='POST'><input type='hidden' name='action' value='destroy'><input type='submit' value='Destroy'></form>");
    printf("</body></html>");
    return 0;
}

#include <stdio.h>

// The global environment variable array
extern char **environ;

int main() {
    printf("Content-type: text/html\n\n");
    printf("<html><body><h1>Environment Variables (C)</h1>");
    printf("<table border='1'>");
    
    char **s = environ;
    for (; *s; s++) {
        printf("<tr><td>%s</td></tr>", *s);
    }
    
    printf("</table></body></html>");
    return 0;
}

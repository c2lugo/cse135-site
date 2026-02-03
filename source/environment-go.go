package main

import (
    "fmt"
    "os"
    "strings"
)

func main() {
    fmt.Println("Content-Type: text/html")
    fmt.Println("") 

    fmt.Println("<!DOCTYPE html><html><body>")
    fmt.Println("<h1>Environment Variables (Go)</h1>")
    fmt.Println("<table border='1' style='border-collapse: collapse;'>")

    // Loop through all environment variables
    for _, e := range os.Environ() {
        pair := strings.SplitN(e, "=", 2)
        fmt.Printf("<tr><td><b>%s</b></td><td>%s</td></tr>\n", pair[0], pair[1])
    }

    fmt.Println("</table></body></html>")
}

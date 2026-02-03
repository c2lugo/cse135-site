package main

import (
    "fmt"
    "io/ioutil"
    "net/url"
    "os"
    "time"
)

func main() {
    // 1. Gather Metadata
    method := os.Getenv("REQUEST_METHOD")
    ip := os.Getenv("REMOTE_ADDR")
    userAgent := os.Getenv("HTTP_USER_AGENT")
    hostname, _ := os.Hostname()
    currentTime := time.Now().Format(time.RFC1123)

    // 2. Read Input Data
    var inputData string
    if method == "POST" || method == "PUT" {
        bytes, _ := ioutil.ReadAll(os.Stdin)
        inputData = string(bytes)
    } else {
        inputData = os.Getenv("QUERY_STRING")
    }

    // 3. Parse Data (try to make it readable)
    parsedData, _ := url.ParseQuery(inputData)

    // 4. Output
    fmt.Println("Content-Type: text/html")
    fmt.Println("") 

    fmt.Println("<!DOCTYPE html><html><body style='font-family: sans-serif;'>")
    fmt.Printf("<h1>Echo Response (Go)</h1>")
    
    // Metadata Section
    fmt.Println("<h3>Server Details:</h3>")
    fmt.Printf("<ul>")
    fmt.Printf("<li><b>Hostname:</b> %s</li>", hostname)
    fmt.Printf("<li><b>Date/Time:</b> %s</li>", currentTime)
    fmt.Printf("<li><b>Your IP:</b> %s</li>", ip)
    fmt.Printf("<li><b>User Agent:</b> %s</li>", userAgent)
    fmt.Printf("<li><b>Method:</b> %s</li>", method)
    fmt.Printf("</ul>")

    // Data Section
    fmt.Println("<h3>Received Data:</h3>")
    fmt.Printf("<p><b>Raw String:</b> %s</p>", inputData)
    
    if len(parsedData) > 0 {
        fmt.Println("<table border='1'><tr><th>Key</th><th>Value</th></tr>")
        for k, v := range parsedData {
            fmt.Printf("<tr><td>%s</td><td>%s</td></tr>", k, v[0])
        }
        fmt.Println("</table>")
    }

    fmt.Println("</body></html>")
}

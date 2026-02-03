package main

import (
    "fmt"
    "os"
    "time"
)

func main() {
    // 1. Headers
    fmt.Println("Content-Type: text/html")
    fmt.Println("") 

    // 2. Data Gathering
    ip := os.Getenv("REMOTE_ADDR")
    currentTime := time.Now().Format(time.RFC1123)

    // 3. HTML Output
    fmt.Println("<!DOCTYPE html><html>")
    fmt.Println("<head><title>Hello Go</title></head>")
    fmt.Println("<body>")
    fmt.Println("<h1>Hello from Team: Carlos Lugo</h1>")
    fmt.Println("<h2>Language: Go (Golang)</h2>")
    fmt.Printf("<p><b>Date Generated:</b> %s</p>\n", currentTime)
    fmt.Printf("<p><b>Your IP Address:</b> %s</p>\n", ip)
    fmt.Println("</body></html>")
}

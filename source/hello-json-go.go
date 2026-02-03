package main

import (
    "encoding/json"
    "fmt"
)

func main() {
    // 1. Set Header to JSON
    fmt.Println("Content-Type: application/json")
    fmt.Println("") // Blank line

    // 2. Create a simple map to represent data
    data := map[string]string{
        "message": "Hello World from Go",
        "language": "Golang",
        "status": "success",
    }

    // 3. Encode to JSON and print
    jsonData, _ := json.Marshal(data)
    fmt.Println(string(jsonData))
}

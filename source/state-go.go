package main

import (
    "fmt"
    "io/ioutil"
    "math/rand"
    "net/url"
    "os"
    "strings"
    "time"
)

// --- HELPER FUNCTION ---
// This looks through the "HTTP_COOKIE" environment variable
// to see if our specific cookie (MY_GO_SESSION) exists.
func getCookie(name string) string {
    cookies := os.Getenv("HTTP_COOKIE")
    for _, cookie := range strings.Split(cookies, ";") {
        parts := strings.SplitN(strings.TrimSpace(cookie), "=", 2)
        if len(parts) == 2 && parts[0] == name {
            return parts[1]
        }
    }
    return ""
}

func main() {
    // 1. GET THE SESSION ID
    sessionID := getCookie("MY_GO_SESSION")
    var newData string
    
    // 2. PROCESS INPUT (If the user clicked 'Save' or 'Destroy')
    if os.Getenv("REQUEST_METHOD") == "POST" {
        bytes, _ := ioutil.ReadAll(os.Stdin)
        postData, _ := url.ParseQuery(string(bytes))
        
        // If user clicked "Destroy Session"
        if postData.Get("action") == "destroy" {
            if sessionID != "" {
                // Delete the file on the server
                os.Remove("/tmp/cgisess_" + sessionID)
            }
            // Tell browser to expire (delete) the cookie
            fmt.Println("Set-Cookie: MY_GO_SESSION=deleted; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT")
            sessionID = "" // Clear ID for the rest of this run
        } else if val := postData.Get("mydata"); val != "" {
            // If user submitted new data, save it to a variable
            newData = val
        }
    }

    // 3. START NEW SESSION (If no ID exists)
    if sessionID == "" && newData != "" {
        rand.Seed(time.Now().UnixNano())
        sessionID = fmt.Sprintf("%d", rand.Int())
        // Send the header to the browser: "Keep this ID!"
        fmt.Printf("Set-Cookie: MY_GO_SESSION=%s; path=/;\n", sessionID)
    }

    // 4. SAVE DATA TO FILE
    // We use /tmp/ because it's a safe place for temporary files
    sessFile := "/tmp/cgisess_" + sessionID
    if sessionID != "" && newData != "" {
        ioutil.WriteFile(sessFile, []byte(newData), 0644)
    }

    // 5. READ DATA FROM FILE (To display it)
    currentData := "Nothing set yet"
    if sessionID != "" {
        content, err := ioutil.ReadFile(sessFile)
        if err == nil {
            currentData = string(content)
        }
    }

    // 6. OUTPUT HTML
    // Must print the content-type header first
    fmt.Println("Content-Type: text/html\n")

    fmt.Println("<html><body>")
    fmt.Println("<h1>Go State Management</h1>")
    fmt.Printf("<p><b>Current Session ID:</b> %s</p>", sessionID)
    fmt.Printf("<p><b>Saved Data:</b> %s</p>", currentData)
    
    // Form to save data
    fmt.Println("<h3>Set New Data:</h3>")
    fmt.Println("<form method='POST'>")
    fmt.Println("<input type='text' name='mydata' placeholder='Enter something...'>")
    fmt.Println("<input type='submit' value='Save'>")
    fmt.Println("</form>")

    // Form to destroy session
    fmt.Println("<form method='POST'>")
    fmt.Println("<input type='hidden' name='action' value='destroy'>")
    fmt.Println("<input type='submit' value='Destroy Session'>")
    fmt.Println("</form>")
    fmt.Println("</body></html>")
}

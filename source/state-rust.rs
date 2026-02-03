use std::env;
use std::fs::{self, File};
use std::io::{self, Read, Write}; // Fixed: Added 'self' so we can use 'io::'
use std::path::Path;

// Helper to get a specific cookie
fn get_cookie(key: &str) -> Option<String> {
    if let Ok(cookie_header) = env::var("HTTP_COOKIE") {
        for cookie in cookie_header.split(';') {
            let parts: Vec<&str> = cookie.trim().splitn(2, '=').collect();
            if parts.len() == 2 && parts[0] == key {
                return Some(parts[1].to_string());
            }
        }
    }
    None
}

// Generate simple random ID from /dev/urandom
fn generate_id() -> String {
    let mut f = File::open("/dev/urandom").unwrap();
    let mut buf = [0u8; 8];
    f.read_exact(&mut buf).unwrap();
    // Convert bytes to hex string manually
    let mut s = String::new();
    for &byte in &buf {
        s.push_str(&format!("{:02x}", byte));
    }
    s
}

fn main() {
    let mut session_id = get_cookie("MY_RUST_SESSION");
    let mut new_data = String::new();
    let mut destroy = false;

    // 1. Parse POST Data
    let method = env::var("REQUEST_METHOD").unwrap_or("GET".to_string());
    if method == "POST" {
        let mut buffer = String::new();
        // This line caused the error before. Now it works because of 'use std::io::{self...}'
        io::stdin().read_to_string(&mut buffer).unwrap_or(0);
        
        // Check for destroy
        if buffer.contains("action=destroy") {
            destroy = true;
        }
        
        // Check for mydata (Manual parse)
        for pair in buffer.split('&') {
            let parts: Vec<&str> = pair.splitn(2, '=').collect();
            if parts.len() == 2 && parts[0] == "mydata" {
                new_data = parts[1].to_string();
            }
        }
    }

    // 2. Logic
    if destroy {
        if let Some(ref id) = session_id {
            let _ = fs::remove_file(format!("/tmp/rsess_{}", id));
        }
        println!("Set-Cookie: MY_RUST_SESSION=deleted; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT");
        session_id = None;
    } else if session_id.is_none() {
        let new_id = generate_id();
        println!("Set-Cookie: MY_RUST_SESSION={}; path=/;", new_id);
        session_id = Some(new_id);
    }

    // 3. File I/O
    let mut current_data = "Nothing set yet".to_string();
    
    if let Some(ref id) = session_id {
        let path_str = format!("/tmp/rsess_{}", id);
        let path = Path::new(&path_str);
        
        // Write if we have new data
        if !new_data.is_empty() {
            // Unwrap safely or handle error silently for CGI
            if let Ok(mut f) = File::create(path) {
                let _ = f.write_all(new_data.as_bytes());
            }
        }
        
        // Read current data
        if path.exists() {
            if let Ok(mut f) = File::open(path) {
                current_data.clear();
                let _ = f.read_to_string(&mut current_data);
            }
        }
    }

    // 4. Output
    println!("Content-type: text/html\n\n");
    println!("<html><body><h1>Rust State Management</h1>");
    println!("<p>Session ID: {:?}</p>", session_id.unwrap_or("None".to_string()));
    println!("<p>Saved Data: {}</p>", current_data);
    
    println!("<h3>Update Data:</h3>");
    println!("<form method='POST'><input type='text' name='mydata'><input type='submit' value='Save'></form>");
    
    println!("<form method='POST'><input type='hidden' name='action' value='destroy'><input type='submit' value='Destroy'></form>");
    println!("</body></html>");
}

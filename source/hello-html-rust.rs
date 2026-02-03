use std::env;
use std::time::{SystemTime, UNIX_EPOCH};

fn main() {
    // 1. Get IP
    let ip = env::var("REMOTE_ADDR").unwrap_or("Unknown".to_string());

    // 2. Get Simple Timestamp (Standard lib doesn't have fancy formatting without crates)
    let start = SystemTime::now();
    let since_the_epoch = start.duration_since(UNIX_EPOCH).expect("Time went backwards");
    let timestamp = since_the_epoch.as_secs();

    // 3. Output
    println!("Content-type: text/html\n\n");
    println!("<html><head><title>Hello Rust</title></head>");
    println!("<body>");
    println!("<h1>Hello from Team: Carlos Lugo</h1>");
    println!("<h2>Language: Rust (rustc compiled)</h2>");
    println!("<p><b>Timestamp:</b> {} (Unix Seconds)</p>", timestamp);
    println!("<p><b>Your IP Address:</b> {}</p>", ip);
    println!("</body></html>");
}

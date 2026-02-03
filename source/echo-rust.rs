use std::env;
use std::io::{self, Read};

fn main() {
    // --- Metadata ---
    let method = env::var("REQUEST_METHOD").unwrap_or("GET".to_string());
    let ip = env::var("REMOTE_ADDR").unwrap_or("Unknown".to_string());
    
    // --- Input Parsing ---
    let mut input_data = String::new();
    
    if method == "POST" {
        // Read from STDIN
        io::stdin().read_to_string(&mut input_data).unwrap_or(0);
    } else {
        // Read from Query String
        input_data = env::var("QUERY_STRING").unwrap_or("".to_string());
    }

    // --- Output ---
    println!("Content-type: text/html\n\n");
    println!("<!DOCTYPE html><html><body style='font-family:sans-serif'>");
    println!("<h1>Echo Response (Rust)</h1>");
    
    println!("<h3>Server Details:</h3><ul>");
    println!("<li><b>Method:</b> {}</li>", method);
    println!("<li><b>IP:</b> {}</li>", ip);
    println!("</ul>");

    println!("<h3>Received Data:</h3>");
    println!("<p><b>Raw String:</b> {}</p>", input_data);
    
    if !input_data.is_empty() {
        println!("<table border='1'><tr><th>Key</th><th>Value</th></tr>");
        
        // Manual Parsing: Split by '&' then '='
        for pair in input_data.split('&') {
            let mut parts = pair.splitn(2, '=');
            let key = parts.next().unwrap_or("?");
            let val = parts.next().unwrap_or("");
            println!("<tr><td>{}</td><td>{}</td></tr>", key, val);
        }
        println!("</table>");
    }
    
    println!("</body></html>");
}

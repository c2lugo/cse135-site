use std::env;
use std::time::{SystemTime, UNIX_EPOCH};

fn main() {
    let ip = env::var("REMOTE_ADDR").unwrap_or("Unknown".to_string());
    
    let start = SystemTime::now();
    let timestamp = start.duration_since(UNIX_EPOCH).unwrap().as_secs();

    println!("Content-type: application/json\n\n");
    
    // Manual JSON String construction
    println!("{{");
    println!("  \"message\": \"Hello from Team Carlos Lugo\",");
    println!("  \"language\": \"Rust\",");
    println!("  \"timestamp\": {},", timestamp);
    println!("  \"ip\": \"{}\"", ip);
    println!("}}");
}

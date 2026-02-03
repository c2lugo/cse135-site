use std::env;

fn main() {
    println!("Content-type: text/html\n\n");
    println!("<html><body><h1>Environment Variables (Rust)</h1>");
    println!("<table border='1'>");
    
    for (key, value) in env::vars() {
        println!("<tr><td>{}</td><td>{}</td></tr>", key, value);
    }
    
    println!("</table></body></html>");
}

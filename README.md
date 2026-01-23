# CSE 135 Website

## Student Information
* **Name:** Carlos Lugo
* **Email:** c2lugo@ucsd.edu
* **Domain:** https://carloslugo.dev

## Grader Access 
* **Username:** grader
* **Password:** vDYVfY5nRQANkmE4
* **SSH Private Key:** 
-----BEGIN OPENSSH PRIVATE KEY-----
b3BlbnNzaC1rZXktdjEAAAAABG5vbmUAAAAEbm9uZQAAAAAAAAABAAAAMwAAAAtzc2gtZW
QyNTUxOQAAACBDC1/C0Qn9pcGauoZPDIcmSx4PHSchAo8JMNtOE9prlgAAAJAn+ITRJ/iE
0QAAAAtzc2gtZWQyNTUxOQAAACBDC1/C0Qn9pcGauoZPDIcmSx4PHSchAo8JMNtOE9prlg
AAAEB/4pB/MnhiW6lhbKnKxC16Dpy3va0pleJLQuKl8mduFUMLX8LRCf2lwZq6hk8MhyZL
Hg8dJyECjwkw204T2muWAAAACmdyYWRlcl9rZXkBAgM=
-----END OPENSSH PRIVATE KEY-----

## Required Links
* **Homepage:** https://carloslugo.dev
* **My Page:** https://carloslugo.dev/members/carlos.html
* **PHP Info:** https://carloslugo.dev/hello.php
* **GoAccess Report:** https://carloslugo.dev/report.html
* **Collector Site:** https://collector.carloslugo.dev
* **Reporting Site:** https://reporting.carloslugo.dev

## Deployment
1. All code is hosted in this GitHub repository.
2. I created a workflow file at `.github/workflows/deploy.yml`.
3. Whenever code is pushed to the `main` branch, the GitHub Action is triggered.
4. The Action logs into my DigitalOcean Droplet using an SSH Key (stored as a GitHub Secret).
    * It navigates to the web server directory (`/var/www/carloslugo.dev/html`).
    * It executes `git pull origin main` to fetch the latest changes immediately.

## Authentication
To access the site:
* **Username:** carlos
* **Password:** cse135pw

## Compression
I enabled `mod_deflate` on Apache to compress text files.
* **Observation:** When inspecting the site in DevTools the response headers for HTML and CSS files now show `Content-Encoding: gzip`.
* **Result:** The size of the transferred data is reduced compared to the actual file size, improving load times.

## Server Identity Obfuscation
I modified the HTTP `Server` header to display "CSE135 Server" instead of the default Apache version.

1.  **Installed ModSecurity:** I installed the `libapache2-mod-security2` module.
2.  **Configured Signature:** I added the directive `SecServerSignature "CSE135 Server"` to the security config.
3.  **Adjusted Tokens:** I set `ServerTokens Full` and `ServerSignature Off` in the main config to allow ModSecurity to be able rewrite the header string.

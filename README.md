# CSE 135 Website

* **Name:** Carlos Lugo
* **Email:** c2lugo@ucsd.edu
* **Domain:** https://carloslugo.dev

## Deployment
1. All code is hosted in this GitHub repository.
2. I created a workflow file at `.github/workflows/deploy.yml`.
3. Whenever code is pushed to the `main` branch, the GitHub Action is triggered.
4. The Action logs into my DigitalOcean Droplet using an SSH Key (stored as a GitHub Secret).
    * It navigates to the web server directory (`/var/www/carloslugo.dev/html`).
    * It executes `git pull origin main` to fetch the latest changes immediately.

## Authentication
To access site:
* **Username:** carlos
* **Password:** cse135pw

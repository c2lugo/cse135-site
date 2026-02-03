# Part 3: Third Party Analytics Done 3 Ways — Walkthrough

This walkthrough covers **Approach 1 (Google Analytics)** and **Approach 2 (LogRocket)**. Your assignment says "3 Ways" in the title; the two approaches below are the ones explicitly required. If your syllabus lists a third (e.g., another tool or a custom analytics comparison), add that separately.

---

## Approach 1: Google Analytics

### 1. Create an Analytics account (if needed)

1. Go to **https://analytics.google.com**
2. Sign in with your Google account.
3. If it’s your first time: click **Start measuring**. Otherwise go to **Admin** (gear icon) → **Create** → **Account**, name it (e.g. "CSE 135"), set data-sharing options, then **Next**.

### 2. Create a property and web data stream

1. **Create property**: Enter property name (e.g. "carloslugo.dev"), time zone, currency → **Next** → choose industry/size and how you’ll use Analytics → **Create** (and accept terms if prompted).
2. **Add a Web data stream**:
   - Under **Data collection and modification** → **Data Streams** → **Add stream**.
   - Choose **Web**.
   - **Website URL**: e.g. `https://carloslugo.dev` (your real domain).
   - **Stream name**: e.g. "carloslugo.dev (web)".
   - Leave **Enhanced measurement** on (recommended).
   - Click **Create stream**.

### 3. Get your Measurement ID and install the tag

1. On the stream you just created, under **Stream details**, copy the **Measurement ID** (starts with `G-`).
2. **Install manually**:
   - In the same stream, under **Google tag** → **View tag instructions**.
   - Choose **Install manually**.
   - Copy the full snippet (from `<!-- Google tag (gtag.js) -->` through the closing `</script>`).
3. **Add to your site**: Paste that snippet in the `<head>` of every page you want to track (or in a shared layout). In this repo, placeholders are in `index.html` (and optionally other pages)—replace the placeholder `G-XXXXXXXXXX` with your real Measurement ID.

### 4. Verify and screenshot

- **Realtime**: In GA, go to **Report** → **Realtime**. Open your site in another tab, click around. You should see at least 1 user in Realtime.
- **Screenshot**: Once you see data (even one visit), take a screenshot of the GA dashboard (e.g. Realtime or Overview) and save it as **ga-dashboard.png**.

**Note:** It can take up to ~30 minutes for non-Realtime data to appear; Realtime should work within a few minutes.

---

## Approach 2: LogRocket

### 1. Sign up and create a project

1. Go to **https://logrocket.com** → **Get Started Free**.
2. Sign up (email or Google).
3. Create a project; name it (e.g. "carloslugo.dev" or "cse135-site").

### 2. Get your App ID and install the agent

After creating the project, LogRocket will show:

- **App ID** (e.g. `abc123/your-project`).

**Option A — Script tag (good for your static site):**

Add to the `<head>` (or before `</body>`) of each page you want to record:

```html
<script src="https://cdn.logr-i.com/LogRocket.min.js" crossorigin="anonymous"></script>
<script>window.LogRocket && window.LogRocket.init('YOUR_APP_ID');</script>
```

Replace `YOUR_APP_ID` with the App ID from the LogRocket dashboard.

**Option B — NPM:**  
If you had a JS build step you could use `npm i logrocket` and `LogRocket.init('YOUR_APP_ID')` in your app entry. For a static site, the script tag is simpler.

Placeholder scripts are in `index.html` (and optionally in `members/carloslugo.html`). Replace the placeholder App ID with your real one.

### 3. Verify in the browser and dashboard

1. Deploy or serve your site and open it in the browser.
2. **DevTools**: Open DevTools → **Network** tab. Filter by "logr" or "LogRocket". Reload and click/scroll; you should see requests to LogRocket’s servers.
3. **LogRocket dashboard**: In your project, open **Sessions** (or **Recordings**). After a short delay, you should see a session that matches your visit. Open it to confirm it’s a replay of your actions.

### 4. Screenshots and recording

- **Screenshot**: Take a screenshot of your LogRocket dashboard (e.g. project overview or sessions list) and save it as **logrocket.png**.
- **Session replay**: Start a new session (refresh and use the site for a few seconds). In the dashboard, open that session and play the replay. Record your screen while the replay plays and save as **logrocket-session.gif** or **logrocket-session.mp4**.

---

## Checklist

| Task | File / Action |
|------|----------------|
| GA configured and data visible | Screenshot → **ga-dashboard.png** |
| LogRocket installed and sessions visible | Screenshot → **logrocket.png** |
| Session replay captured | Screen recording → **logrocket-session.gif** or **logrocket-session.mp4** |

---

## Placeholders in this repo

- **Google Analytics**: In `index.html` (and any other pages you add the tag to), replace `G-XXXXXXXXXX` with your actual Measurement ID.
- **LogRocket**: Replace `YOUR_APP_ID` with your actual LogRocket App ID (e.g. `abc123/carloslugo-dev`).

After replacing those, deploy (e.g. push to trigger your DigitalOcean deploy) and then verify both tools as above.

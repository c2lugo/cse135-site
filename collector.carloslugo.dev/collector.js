(function() {
console.log("🚨 COLLECTOR SCRIPT IS ALIVE AND RUNNING! 🚨");
const ENDPOINT = "https://collector.carloslugo.dev/api/track/index.php";
    let eventQueue = [];
    
    /*** SESSION MANAGEMENT ***/
    let sessionId = sessionStorage.getItem('cse135_session_id');
    if (!sessionId) {
        sessionId = crypto.randomUUID ? crypto.randomUUID() : Date.now().toString(36);
        sessionStorage.setItem('cse135_session_id', sessionId);
    }

    function sendData(type, payload) {
        const data = {
            sessionId: sessionId,
            type: type,
            url: window.location.href,
            timestamp: Date.now(),
            data: payload
        };
        
        console.log("Intercepted Data Payload:", data);
        
	// Use sendBeacon 
	let blob = new Blob([JSON.stringify(data)], { type: 'application/x-www-form-urlencoded' });
        navigator.sendBeacon(ENDPOINT, blob);
    }

    // Helper to queue continuous activity data
    function queueActivity(activityType, payload) {
        eventQueue.push({ activityType, timestamp: Date.now(), ...payload });
    }

    // Flush the queue to the server every 5 seconds
    setInterval(() => {
        if (eventQueue.length > 0) {
            sendData('activity_batch', eventQueue);
            eventQueue = []; // Clear queue after sending
        }
    }, 5000);

    /*** 1. STATIC DATA ***/
    function collectStaticData() {
        // Test if images are enabled using a tiny 1x1 pixel
        let imagesEnabled = false;
        const testImg = new Image();
        testImg.onload = () => imagesEnabled = true;
        testImg.src = "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7";

        // Test if CSS is enabled by checking if a dynamically injected style applies
        const testDiv = document.createElement('div');
        testDiv.style.color = 'rgb(1, 2, 3)';
        testDiv.style.display = 'none';
        document.body.appendChild(testDiv);
        let cssEnabled = window.getComputedStyle(testDiv).color === 'rgb(1, 2, 3)';
        document.body.removeChild(testDiv);

        const staticData = {
            userAgent: navigator.userAgent,
            language: navigator.language,
            cookiesEnabled: navigator.cookieEnabled,
            jsEnabled: true, // If this script runs, JS is on!
            imagesEnabled: imagesEnabled,
            cssEnabled: cssEnabled,
            screenW: window.screen.width,
            screenH: window.screen.height,
            windowW: window.innerWidth,
            windowH: window.innerHeight,
            connection: navigator.connection ? navigator.connection.effectiveType : 'unknown'
        };
        sendData('static', staticData);
    }

    /***  2. PERFORMANCE DATA ***/
    window.addEventListener('load', () => {
        setTimeout(() => { // Small timeout to ensure loadEventEnd is fully populated
            const perf = window.performance.timing;
            const loadTime = perf.loadEventEnd - perf.navigationStart;
            
            const perfData = {
                timingObject: perf,
                startLoad: perf.navigationStart,
                endLoad: perf.loadEventEnd,
                totalLoadTime: loadTime
            };
            sendData('performance', perfData);
            
            // Collect static data after load as requested
            collectStaticData();
        }, 100);
    });

    /*** 3. ACTIVITY DATA ***/
    // Errors
    window.addEventListener('error', (e) => queueActivity('error', { message: e.message, file: e.filename, line: e.lineno }));

    // Mouse Activity
    window.addEventListener('mousemove', (e) => queueActivity('mousemove', { x: e.clientX, y: e.clientY }));
    window.addEventListener('click', (e) => queueActivity('click', { button: e.button, x: e.clientX, y: e.clientY }));
    window.addEventListener('scroll', () => queueActivity('scroll', { scrollX: window.scrollX, scrollY: window.scrollY }));

    // Keyboard Activity
    window.addEventListener('keydown', (e) => queueActivity('keydown', { key: e.key }));
    window.addEventListener('keyup', (e) => queueActivity('keyup', { key: e.key }));

    // Idle Time Logic
    let idleTimer;
    let idleStart = Date.now();
    let isIdle = false;

    function resetIdleTimer() {
        if (isIdle) {
            const idleEnd = Date.now();
            queueActivity('idle_end', { breakEnded: idleEnd, durationMs: idleEnd - idleStart });
            isIdle = false;
        }
        clearTimeout(idleTimer);
        // Set to trigger after 2 seconds of no activity
        idleTimer = setTimeout(() => {
            isIdle = true;
            idleStart = Date.now();
        }, 2000);
    }

    // Attach idle reset to user interactions
    ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart'].forEach(evt => 
        window.addEventListener(evt, resetIdleTimer, { passive: true })
    );
    resetIdleTimer();

    // Enter/Leave Page
    queueActivity('page_enter', { enterTime: Date.now() });

    window.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
            queueActivity('page_leave', { leaveTime: Date.now() });
            // Flush remaining queue immediately before they leave
            if (eventQueue.length > 0) {
                sendData('activity_batch', eventQueue);
                eventQueue = [];
            }
        }
    });

})();

/**
 * User Behavior Tracking
 * 
 * Tracks user interactions and sends them to the backend
 * for AI-powered behavior analytics.
 */

class BehaviorTracker {
    constructor(options = {}) {
        this.sessionId = this.getSessionId();
        this.endpoint = options.endpoint || '/api/v1/behavior/track';
        this.batchSize = options.batchSize || 10;
        this.flushInterval = options.flushInterval || 30000; // 30 seconds
        this.queue = [];
        this.pageStartTime = Date.now();
        this.maxScrollDepth = 0;

        this.init();
    }

    init() {
        // Track page view
        this.trackPageView();

        // Track scroll depth
        this.trackScroll();

        // Track clicks on important elements
        this.trackClicks();

        // Track form interactions
        this.trackForms();

        // Flush queue on page unload
        window.addEventListener('beforeunload', () => this.flush(true));

        // Periodic flush
        setInterval(() => this.flush(), this.flushInterval);

        // Track time on page before leaving
        window.addEventListener('beforeunload', () => {
            const timeOnPage = Math.round((Date.now() - this.pageStartTime) / 1000);
            this.track('page_view', {
                time_on_page: timeOnPage,
                scroll_depth: this.maxScrollDepth,
            });
        });
    }

    getSessionId() {
        let sessionId = localStorage.getItem('behavior_session');
        if (!sessionId) {
            sessionId = 'sess_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem('behavior_session', sessionId);
        }
        return sessionId;
    }

    trackPageView() {
        this.track('page_view', {
            url: window.location.href,
            path: window.location.pathname,
            referrer: document.referrer,
            screen_resolution: `${screen.width}x${screen.height}`,
        });
    }

    trackScroll() {
        let ticking = false;

        window.addEventListener('scroll', () => {
            if (!ticking) {
                window.requestAnimationFrame(() => {
                    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                    const docHeight = document.documentElement.scrollHeight - window.innerHeight;
                    const scrollPercent = Math.round((scrollTop / docHeight) * 100);

                    if (scrollPercent > this.maxScrollDepth) {
                        this.maxScrollDepth = scrollPercent;
                    }

                    ticking = false;
                });
                ticking = true;
            }
        });
    }

    trackClicks() {
        document.addEventListener('click', (e) => {
            const target = e.target.closest('[data-track]');
            if (target) {
                const trackType = target.dataset.track;
                const trackData = {};

                // Extract data attributes
                for (const [key, value] of Object.entries(target.dataset)) {
                    if (key !== 'track') {
                        trackData[key] = value;
                    }
                }

                this.track(trackType, {
                    element_id: target.id,
                    element_text: target.textContent?.trim().substring(0, 100),
                    element_type: target.tagName.toLowerCase(),
                    ...trackData,
                });
            }
        });
    }

    trackForms() {
        // Track form starts
        document.addEventListener('focusin', (e) => {
            if (e.target.form && !e.target.form.dataset.tracked) {
                e.target.form.dataset.tracked = 'true';
                this.track('form_start', {
                    element_id: e.target.form.id,
                    element_type: 'form',
                });
            }
        });

        // Track form submissions
        document.addEventListener('submit', (e) => {
            this.track('form_submit', {
                element_id: e.target.id,
                element_type: 'form',
            });
        });
    }

    track(eventType, data = {}) {
        const event = {
            event_type: eventType,
            session_id: this.sessionId,
            timestamp: new Date().toISOString(),
            url: window.location.href,
            path: window.location.pathname,
            ...data,
        };

        this.queue.push(event);

        if (this.queue.length >= this.batchSize) {
            this.flush();
        }
    }

    async flush(sync = false) {
        if (this.queue.length === 0) return;

        const events = [...this.queue];
        this.queue = [];

        const payload = JSON.stringify({ events });

        if (sync && navigator.sendBeacon) {
            navigator.sendBeacon(this.endpoint, payload);
            return;
        }

        try {
            await fetch(this.endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
                body: payload,
                keepalive: true,
            });
        } catch (error) {
            // Re-queue on failure (but don't grow indefinitely)
            if (this.queue.length < 100) {
                this.queue.unshift(...events);
            }
        }
    }

    /**
     * Track a custom event
     */
    trackEvent(eventType, data = {}) {
        this.track(eventType, data);
        this.flush();
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    window.behaviorTracker = new BehaviorTracker();

    // Track property views
    const propertyId = document.querySelector('meta[name="property-id"]')?.content;
    if (propertyId) {
        window.behaviorTracker.trackEvent('page_view', {
            property_id: propertyId,
        });
    }
});

export default BehaviorTracker;
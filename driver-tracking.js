// This would be included in the driver's mobile app or mobile web interface
class DriverTracker {
    constructor() {
        this.trackingInterval = null;
        this.isTracking = false;
        this.currentSessionId = null;
        
        this.initElements();
        this.bindEvents();
    }
    
    initElements() {
        this.toggleBtn = document.getElementById('toggle-tracking');
        this.statusIndicator = document.getElementById('tracking-status');
        this.lastUpdateEl = document.getElementById('last-update');
    }
    
    bindEvents() {
        if (this.toggleBtn) {
            this.toggleBtn.addEventListener('click', () => {
                this.isTracking ? this.stopTracking() : this.startTracking();
            });
        }
    }
    
    startTracking() {
        if (!navigator.geolocation) {
            alert("Geolocation is not supported by your browser");
            return;
        }
        
        this.isTracking = true;
        this.updateUI();
        
        // Start sending location updates every 30 seconds
        this.trackingInterval = setInterval(() => {
            navigator.geolocation.getCurrentPosition(
                this.sendLocation.bind(this),
                this.handleLocationError.bind(this),
                { enableHighAccuracy: true, maximumAge: 10000, timeout: 5000 }
            );
        }, 30000);
        
        // Get initial position immediately
        navigator.geolocation.getCurrentPosition(
            this.sendLocation.bind(this),
            this.handleLocationError.bind(this),
            { enableHighAccuracy: true }
        );
    }
    
    stopTracking() {
        if (this.trackingInterval) {
            clearInterval(this.trackingInterval);
            this.trackingInterval = null;
        }
        
        this.isTracking = false;
        this.updateUI();
        
        // Send end session to server
        if (this.currentSessionId) {
            fetch('tracking.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'end_session', session_id: this.currentSessionId })
            });
        }
    }
    
    sendLocation(position) {
        const locationData = {
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
            speed: position.coords.speed || null,
            accuracy: position.coords.accuracy
        };
        
        fetch('tracking.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(locationData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.session_id) {
                this.currentSessionId = data.session_id;
            }
            this.lastUpdateEl.textContent = new Date().toLocaleTimeString();
        })
        .catch(error => {
            console.error('Error sending location:', error);
        });
    }
    
    handleLocationError(error) {
        console.error('Geolocation error:', error);
        this.statusIndicator.className = 'status-indicator status-inactive';
    }
    
    updateUI() {
        if (this.isTracking) {
            this.toggleBtn.textContent = 'Stop Tracking';
            this.statusIndicator.className = 'status-indicator status-active';
        } else {
            this.toggleBtn.textContent = 'Start Tracking';
            this.statusIndicator.className = 'status-indicator status-inactive';
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('toggle-tracking')) {
        new DriverTracker();
    }
});
// assets/js/utils.js

class ParkEaseUtils {
    // Format date
    static formatDate(date, format = 'full') {
        const d = new Date(date);
        const options = {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        };
        
        if (format === 'full') {
            options.hour = '2-digit';
            options.minute = '2-digit';
        }
        
        return d.toLocaleDateString('en-US', options);
    }
    
    // Format currency
    static formatCurrency(amount, currency = 'NPR') {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency
        }).format(amount);
    }
    
    // Generate random ID
    static generateId(prefix = '') {
        return prefix + Date.now().toString(36) + Math.random().toString(36).substr(2);
    }
    
    // Debounce function
    static debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // Validate email
    static validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    // Validate phone number (Nepal)
    static validatePhone(phone) {
        const re = /^[9][6-8]\d{8}$/;
        return re.test(phone);
    }
    
    // Get query parameters
    static getQueryParams() {
        const params = {};
        window.location.search.substring(1).split('&').forEach(pair => {
            const [key, value] = pair.split('=');
            if (key) {
                params[decodeURIComponent(key)] = decodeURIComponent(value || '');
            }
        });
        return params;
    }
    
    // Set query parameter
    static setQueryParam(key, value) {
        const params = new URLSearchParams(window.location.search);
        params.set(key, value);
        window.history.replaceState({}, '', `${window.location.pathname}?${params}`);
    }
    
    // Show loading overlay
    static showLoading(message = 'Loading...') {
        // Remove existing overlay
        this.hideLoading();
        
        const overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.innerHTML = `
            <div style="text-align: center;">
                <div class="loading-spinner"></div>
                <p style="margin-top: 20px;">${message}</p>
            </div>
        `;
        document.body.appendChild(overlay);
        return overlay;
    }
    
    // Hide loading overlay
    static hideLoading() {
        const overlays = document.querySelectorAll('.loading-overlay');
        overlays.forEach(overlay => overlay.remove());
    }
    
    // Show confirmation dialog
    static confirm(options) {
        return new Promise((resolve) => {
            const dialog = document.createElement('div');
            dialog.className = 'modal';
            dialog.style.display = 'flex';
            
            dialog.innerHTML = `
                <div class="confirm-dialog">
                    <h3>${options.title || 'Confirm Action'}</h3>
                    <p>${options.message || 'Are you sure?'}</p>
                    <div class="confirm-actions">
                        <button class="btn btn-secondary" id="confirmCancel">Cancel</button>
                        <button class="btn ${options.danger ? 'btn-danger' : 'btn-primary'}" id="confirmOk">
                            ${options.okText || 'OK'}
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(dialog);
            
            dialog.querySelector('#confirmCancel').addEventListener('click', () => {
                dialog.remove();
                resolve(false);
            });
            
            dialog.querySelector('#confirmOk').addEventListener('click', () => {
                dialog.remove();
                resolve(true);
            });
        });
    }
    
    // Copy to clipboard
    static copyToClipboard(text) {
        return new Promise((resolve, reject) => {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text)
                    .then(resolve)
                    .catch(reject);
            } else {
                const textArea = document.createElement('textarea');
                textArea.value = text;
                textArea.style.position = 'fixed';
                textArea.style.left = '-999999px';
                textArea.style.top = '-999999px';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                try {
                    document.execCommand('copy');
                    resolve();
                } catch (err) {
                    reject(err);
                }
                textArea.remove();
            }
        });
    }
    
    // Generate QR code (mock for now)
    static generateQRCode(text, size = 200) {
        // In production, you would use a QR code library
        return `https://api.qrserver.com/v1/create-qr-code/?size=${size}x${size}&data=${encodeURIComponent(text)}`;
    }
    
    // Calculate distance between coordinates (Haversine formula)
    static calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371; // Earth's radius in km
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = 
            Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * 
            Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c; // Distance in km
    }
    
    // Format time duration
    static formatDuration(minutes) {
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        
        if (hours === 0) {
            return `${mins}m`;
        } else if (mins === 0) {
            return `${hours}h`;
        } else {
            return `${hours}h ${mins}m`;
        }
    }
    
    // Parse time string to minutes
    static parseTimeToMinutes(timeStr) {
        const [hours, minutes] = timeStr.split(':').map(Number);
        return hours * 60 + minutes;
    }
    
    // Check if time is between two times
    static isTimeBetween(startTime, endTime, checkTime) {
        const start = this.parseTimeToMinutes(startTime);
        const end = this.parseTimeToMinutes(endTime);
        const check = this.parseTimeToMinutes(checkTime);
        
        if (start <= end) {
            return check >= start && check <= end;
        } else {
            return check >= start || check <= end;
        }
    }
    
    // Get current location
    static getCurrentLocation() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error('Geolocation not supported'));
                return;
            }
            
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    resolve({
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy
                    });
                },
                (error) => {
                    reject(error);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        });
    }
    
    // Generate parking slot numbers
    static generateSlotNumbers(totalSlots, prefix = 'A') {
        const slots = [];
        for (let i = 1; i <= totalSlots; i++) {
            slots.push({
                id: i,
                number: `${prefix}${i.toString().padStart(2, '0')}`,
                available: true
            });
        }
        return slots;
    }
    
    // Calculate parking price
    static calculateParkingPrice(startTime, endTime, ratePerHour) {
        const start = new Date(startTime);
        const end = new Date(endTime);
        const diffMs = end - start;
        const diffHours = diffMs / (1000 * 60 * 60);
        return Math.ceil(diffHours) * ratePerHour;
    }
}

// Make utils available globally
window.ParkEaseUtils = ParkEaseUtils;
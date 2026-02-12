document.addEventListener('DOMContentLoaded', () => {

    // Modals
    setupModals();

    // Mobile Menu
    const hamburger = document.querySelector('.hamburger');
    const navLinks = document.querySelector('.nav-links');
    if (hamburger) {
        hamburger.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });
    }

    // Forms
    setupForms();

    // Init Logic
    checkAuthStatus(); // Update nav
    if (document.getElementById('parkingList')) {
        initMap();
        loadLocations();
    }

    // Scroll Animations
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.glass-panel, h2, .btn').forEach(el => {
        el.classList.add('animate-fade-in');
        observer.observe(el);
    });

    // Navbar Scroll Effect
    window.addEventListener('scroll', () => {
        const nav = document.querySelector('.navbar');
        if (window.scrollY > 50) {
            nav.style.background = 'rgba(2, 6, 23, 0.9)';
            nav.style.backdropFilter = 'blur(12px)';
        } else {
            nav.style.background = 'transparent';
            nav.style.backdropFilter = 'none';
        }
    });
});

function setupModals() {
    // Only booking modal remains
    const modal = document.getElementById('bookingModal');

    // Legacy modal cleanup (if any)
    const oldAuth = document.getElementById('authModal');
    if (oldAuth) oldAuth.remove(); // Ensure it doesn't conflict

    // Close logic
    window.onclick = function (event) {
        if (event.target == modal) modal.style.display = "none";
    }
}

function setupForms() {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const adminLoginForm = document.getElementById('adminLoginForm');

    if (loginForm) loginForm.addEventListener('submit', (e) => handleAuth(e, 'login'));
    if (registerForm) registerForm.addEventListener('submit', (e) => handleAuth(e, 'register'));
    if (adminLoginForm) adminLoginForm.addEventListener('submit', (e) => handleAuth(e, 'login', true));
}

async function handleAuth(event, action, isFormAdmin = false) {
    event.preventDefault();
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn ? submitBtn.innerText : 'Submit';
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerText = "Processing...";
    }

    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());

    try {
        const response = await fetch(`backend/router.php?action=${action}`, {
            method: 'POST',
            widthCredentials: true, // For axios
            credentials: 'include', // For fetch
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();

        if (result.success) {
            if (result.user) {
                const role = result.user.role;
                if (role === 'admin') window.location.href = 'dashboard_admin.html';
                else if (role === 'vendor') window.location.href = 'dashboard_vendor.html';
                else window.location.href = 'dashboard_user.html';
            } else if (action === 'register') {
                alert(result.message || "Account created! Please login.");
                if (typeof switchTab === 'function') {
                    switchTab('login');
                } else {
                    window.location.reload();
                }
            }
        } else {
            alert("Error: " + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert("Connection failed.");
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerText = originalText;
        }
    }
}

async function checkAuthStatus() {
    try {
        const response = await fetch(`backend/router.php?action=check_auth`, { credentials: 'include' });
        const result = await response.json();
        const userInfo = document.getElementById('userInfo');

        if (result.success && result.authenticated) {
            const loginBtn = document.getElementById('loginBtn');
            if (loginBtn) {
                loginBtn.textContent = "Go to Dashboard";
                loginBtn.classList.add("btn-secondary"); // Change style slightly
                loginBtn.href = result.user.role === 'admin' ? 'dashboard_admin.html' : (result.user.role === 'vendor' ? 'dashboard_vendor.html' : 'dashboard_user.html');
            }
            if (userInfo) {
                // Fallback for name if full_name is used
                const name = result.user.full_name || result.user.name;
                userInfo.textContent = `${name} | ${result.user.role.toUpperCase()}`;
            }

            const logoutBtn = document.getElementById('logoutBtn');
            if (logoutBtn) {
                logoutBtn.onclick = async (e) => {
                    e.preventDefault();
                    await fetch(`backend/router.php?action=logout`);
                    window.location.href = 'index.html';
                };
            }
        } else {
            // Access control for dashboards
            if (window.location.pathname.includes('dashboard')) {
                window.location.href = 'index.html';
            }
        }
    } catch (e) {
        console.log("Guest mode");
    }
}

// --- Leaflet Map Logic ---
let mapInstance = null;
let markerLayer = null;

function initMap() {
    const mapContainer = document.getElementById('map');
    if (!mapContainer) return;

    // Check if Leaflet is loaded
    if (typeof L === 'undefined') {
        console.warn("Leaflet not loaded yet. Retrying...");
        setTimeout(initMap, 500);
        return;
    }

    if (mapInstance) {
        mapInstance.invalidateSize();
        return;
    }

    try {
        // Hetauda Coordinates default
        mapInstance = L.map('map', {
            center: [27.4293, 85.0305],
            zoom: 14,
            zoomControl: false,
            attributionControl: false
        });
    } catch (error) {
        console.error("Error initializing Leaflet map:", error);
        return;
    }

    // Add Zoom Control to top-right
    L.control.zoom({ position: 'topright' }).addTo(mapInstance);

    // Dark Matter Tile Layer
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a> &copy; <a href="https://carto.com/attributions">CARTO</a>',
        subdomains: 'abcd',
        maxZoom: 19
    }).addTo(mapInstance);

    markerLayer = L.layerGroup().addTo(mapInstance);

    // Invalidate size to ensure proper rendering if container size changes
    setTimeout(() => {
        if (mapInstance) mapInstance.invalidateSize();
    }, 200);
}

function updateMapMarkers(locations, shouldFitBounds = true) {
    if (!mapInstance) initMap();
    if (!mapInstance) return;

    markerLayer.clearLayers();

    if (locations && locations.length > 0) {
        // Optional: Fit bounds to show all markers
        const bounds = L.latLngBounds();

        locations.forEach(loc => {
            const lat = parseFloat(loc.latitude);
            const lng = parseFloat(loc.longitude);
            if (isNaN(lat) || isNaN(lng)) return;

            bounds.extend([lat, lng]);

            const icon = L.divIcon({
                className: 'custom-marker',
                html: `<div class="marker-pin"></div>`,
                iconSize: [30, 30],
                iconAnchor: [15, 15]
            });

            const marker = L.marker([lat, lng], { icon: icon });

            // Safe string escaping for the onclick handler
            const safeName = loc.name.replace(/'/g, "\\'").replace(/"/g, '&quot;');
            const safeQr = loc.qr_code_url ? loc.qr_code_url : '';

            const popupContent = `
                <div style="text-align: center; min-width: 150px; padding: 0.5rem;">
                    <strong style="display: block; font-size: 1.1em; margin-bottom: 0.25rem; color: var(--primary);">${loc.name}</strong>
                    <span style="color: #94a3b8; font-size: 0.9em;">NPR ${loc.price_per_hour}/hr</span>
                    <button onclick="openBookingModal(${loc.id}, '${safeName}', ${loc.price_per_hour}, '${safeQr}')" 
                            class="btn" style="margin-top: 0.75rem; width: 100%; padding: 0.4rem; font-size: 0.85rem;">
                        Book Spot
                    </button>
                </div>
            `;

            marker.bindPopup(popupContent);
            markerLayer.addLayer(marker);
        });

        if (shouldFitBounds) {
            try {
                if (bounds.isValid()) {
                    mapInstance.fitBounds(bounds, { padding: [50, 50], maxZoom: 16 });
                } else {
                    mapInstance.setView([27.4293, 85.0305], 14);
                }
            } catch (e) { console.log("Map bounds error", e); }
        }
    }
}

async function loadLocations() {
    try {
        const response = await fetch('backend/router.php?action=get_approved_locations', { credentials: 'include' });
        const result = await response.json();
        const list = document.getElementById('parkingList');


        if (result.success) {
            let allLocations = result.data || [];

            // Render Map
            updateMapMarkers(allLocations);

            if (!list) return; // If on homepage but no list (unlikely based on layout) or if list exists

            const render = (locations) => {
                if (locations.length === 0) {
                    list.innerHTML = `<div class="glass-panel animate-fade-in" style="grid-column: 1/-1; text-align: center; padding: 2rem;">
                        <p>No parking spots match your search.</p>
                    </div>`;
                    return;
                }

                list.innerHTML = locations.map(loc => `
                    <div class="glass-panel animate-fade-in" style="padding: 0; overflow: hidden; display: flex; flex-direction: column;">
                        <div style="height: 200px; background: #222; position: relative;">
                             <img src="${loc.image_url ? loc.image_url : './assets/img/placeholder.jpg'}" alt="${loc.name}" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='./assets/img/placeholder.jpg'">
                             <div style="position: absolute; bottom: 0; left: 0; width: 100%; background: linear-gradient(to top, rgba(0,0,0,0.8), transparent); padding: 1rem;">
                                <span style="font-weight: 700; font-size: 1.1rem; color: white;">${loc.name}</span>
                             </div>
                        </div>
                        <div style="padding: 1.5rem; flex-grow: 1; display: flex; flex-direction: column;">
                            <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem; flex-grow: 1;">${loc.address}</p>
                            
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.9rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.5rem;">
                                <span style="color: var(--text-muted);">Capacity</span>
                                <strong>${loc.total_slots} Slots</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 1.5rem; font-size: 0.9rem;">
                                <span style="color: var(--text-muted);">Rate</span>
                                <strong style="color: var(--primary);">NPR ${loc.price_per_hour}/hr</strong>
                            </div>
                            <button onclick="openBookingModal(${loc.id}, '${loc.name}', ${loc.price_per_hour}, '${loc.qr_code_url || ''}')" class="btn" style="width: 100%;">Book Spot</button>
                        </div>
                    </div>
                `).join('');

                // Observe new elements for animation
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('visible');
                            observer.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.1 });

                list.querySelectorAll('.glass-panel').forEach(el => observer.observe(el));
            };

            render(allLocations);

            // Search Filter
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                let debounceTimer;
                searchInput.addEventListener('input', (e) => {
                    const term = e.target.value;
                    clearTimeout(debounceTimer);

                    // Show subtle loading state if needed
                    // list.style.opacity = '0.5';

                    debounceTimer = setTimeout(async () => {
                        try {
                            const res = await fetch(`backend/router.php?action=get_approved_locations&q=${encodeURIComponent(term)}`);
                            const result = await res.json();
                            // list.style.opacity = '1';
                            if (result.success) {
                                render(result.data);
                                // Don't fit bounds on search, just update markers -> No Jitter
                                updateMapMarkers(result.data, false);
                            }
                        } catch (e) { console.error(e); }
                    }, 400); // Increased debounce to 400ms
                });
            }
        }
    } catch (e) {
        console.error("Fetch error", e);
    }
}

// Global scope for popup onclick
window.openBookingModal = function (id, name, price, qrUrl) {
    const modal = document.getElementById('bookingModal');
    if (modal) {
        document.getElementById('bookingLocName').innerText = name;
        document.getElementById('bookingLocId').value = id;
        document.getElementById('bookingPriceRate').value = price;

        // QR Logic
        const qrContainer = document.getElementById('qrCodeContainer');
        // Check if container exists (it might not in older HTML)
        if (document.getElementById('bookingQrImage')) {
            const qrImg = document.getElementById('bookingQrImage');
            if (qrUrl && qrUrl !== 'null' && qrUrl !== 'undefined') {
                qrImg.src = qrUrl;
                if (qrContainer) qrContainer.style.display = 'block';
            } else {
                if (qrContainer) qrContainer.style.display = 'none';
            }
        }

        modal.style.display = 'flex'; // Changed to flex for centering

        document.getElementById('bookingForm').onsubmit = async (e) => {
            e.preventDefault();
            const btn = e.target.querySelector('button[type="submit"]');
            const oldText = btn.innerText;
            btn.disabled = true;
            btn.innerText = "Processing...";

            const data = {
                location_id: document.getElementById('bookingLocId').value,
                start_time: e.target.start_time.value,
                end_time: e.target.end_time.value,
                total_price: document.getElementById('inputTotalPrice').value
            };

            try {
                const response = await fetch('backend/router.php?action=book_slot', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const res = await response.json();

                if (res.success) {
                    alert("Booking Request Sent!");
                    modal.style.display = 'none';
                    if (confirm("Booking pending. Go to dashboard?")) {
                        window.location.href = 'dashboard_user.html';
                    }
                } else {
                    alert(res.message);
                    if (res.message.includes('Authentication') || res.message.includes('permission')) {
                        alert("Please sign in to book a spot.");
                        window.location.href = 'login.html';
                    }
                }
            } catch (err) {
                alert("Booking failed. Network error.");
            } finally {
                btn.disabled = false;
                btn.innerText = oldText;
            }
        };
    }
}

window.calcPrice = function () {
    const start = new Date(document.querySelector('input[name="start_time"]').value);
    const end = new Date(document.querySelector('input[name="end_time"]').value);
    const rate = parseFloat(document.getElementById('bookingPriceRate').value);

    if (!isNaN(start) && !isNaN(end) && end > start && rate) {
        const hours = (end - start) / 36e5; // hours
        const total = (Math.max(0, hours) * rate).toFixed(2);
        document.getElementById('totalPrice').innerText = total;
        document.getElementById('inputTotalPrice').value = total;
    } else {
        document.getElementById('totalPrice').innerText = "0";
    }
}

// --- Vendor Map Picker Logic ---
let pickerMapInstance = null;
let pickerMarker = null;

function initPickerMap() {
    const mapContainer = document.getElementById('pickerMap');
    if (!mapContainer) return;

    if (pickerMapInstance) {
        pickerMapInstance.invalidateSize();
        return;
    }

    // Default to Hetauda or existing input values
    const latInput = document.getElementById('latInput');
    const lngInput = document.getElementById('lngInput');
    const defaultLat = latInput && latInput.value ? parseFloat(latInput.value) : 27.4293;
    const defaultLng = lngInput && lngInput.value ? parseFloat(lngInput.value) : 85.0305;

    pickerMapInstance = L.map('pickerMap', {
        center: [defaultLat, defaultLng],
        zoom: 15
    });

    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a> &copy; <a href="https://carto.com/attributions">CARTO</a>',
        subdomains: 'abcd',
        maxZoom: 19
    }).addTo(pickerMapInstance);

    // Create custom icon
    const icon = L.divIcon({
        className: 'custom-marker',
        html: `<div class="marker-pin" style="background:var(--accent);box-shadow:0 0 10px var(--accent);"></div>`,
        iconSize: [30, 30],
        iconAnchor: [15, 15]
    });

    // Add initial marker
    pickerMarker = L.marker([defaultLat, defaultLng], { icon: icon, draggable: true }).addTo(pickerMapInstance);

    // Drag event
    pickerMarker.on('dragend', function (event) {
        const position = pickerMarker.getLatLng();
        updateInputs(position.lat, position.lng);
    });

    // Click event
    pickerMapInstance.on('click', function (e) {
        pickerMarker.setLatLng(e.latlng);
        updateInputs(e.latlng.lat, e.latlng.lng);
    });

    function updateInputs(lat, lng) {
        if (latInput) latInput.value = lat.toFixed(6);
        if (lngInput) lngInput.value = lng.toFixed(6);
    }

    // Invalidate size to ensure proper rendering
    setTimeout(() => { pickerMapInstance.invalidateSize(); }, 200);
}

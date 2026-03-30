/* Enamel Store Locator — frontend.js
 * All map logic lives here so it can be cached by the browser.
 * Per-instance config is injected by PHP via window.enamelSLInstances[id].
 */
(function () {
    'use strict';

    function initInstance(containerId, cfg) {
        var mapContainerId       = containerId + '-map';
        var locations            = cfg.locations;
        var defaultCenter        = cfg.defaultCenter;
        var defaultZoom          = cfg.defaultZoom;
        var primaryColor         = cfg.primaryColor;
        var accentColor          = cfg.accentColor;
        var markerColor          = cfg.markerColor;
        var activeMarkerColor    = cfg.activeMarkerColor;
        var markerStyle          = cfg.markerStyle;
        var customMarkerImage    = cfg.customMarkerImage;
        var customActiveMarkerImage = cfg.customActiveMarkerImage;
        var showDirections       = cfg.showDirections;
        var showSchedule         = cfg.showSchedule;
        var showCall             = cfg.showCall;
        var mapId                = cfg.mapId;
        var useAdvancedMarkers   = cfg.useAdvancedMarkers;
        var mapStyle             = cfg.mapStyle;
        var apiKey               = cfg.apiKey;
        var lazyLoad             = cfg.lazyLoad;

        // ── Marker helpers ──────────────────────────────────────────────────

        function createAdvancedMarkerContent(color, isActive) {
            var fillColor = isActive ? activeMarkerColor : color;
            var size = isActive ? 44 : 36;
            var div = document.createElement('div');
            div.style.cursor = 'pointer';
            div.style.transition = 'transform 0.2s ease';
            if (isActive) div.style.transform = 'scale(1.15)';

            if (markerStyle === 'custom' && customMarkerImage) {
                var img = document.createElement('img');
                img.src = (isActive && customActiveMarkerImage) ? customActiveMarkerImage : customMarkerImage;
                img.style.width = size + 'px';
                img.style.height = size + 'px';
                img.style.filter = 'drop-shadow(0 2px 4px rgba(0,0,0,0.3))';
                div.appendChild(img);
                return div;
            }

            var svgNS = 'http://www.w3.org/2000/svg';
            var svg = document.createElementNS(svgNS, 'svg');
            svg.setAttribute('width', size);
            svg.setAttribute('height', size);
            svg.setAttribute('viewBox', '0 0 24 24');
            svg.style.filter = 'drop-shadow(0 2px 4px rgba(0,0,0,0.3))';

            var path = document.createElementNS(svgNS, 'path');
            path.setAttribute('fill', fillColor);
            path.setAttribute('stroke', '#ffffff');
            path.setAttribute('stroke-width', '1.5');

            if (markerStyle === 'circle') {
                path.setAttribute('d', 'M12 4a8 8 0 1 0 0 16 8 8 0 0 0 0-16z');
            } else if (markerStyle === 'tooth') {
                path.setAttribute('d', 'M12 2C8.5 2 6 4.5 6 7.5C6 10 7 11.5 8 13C9 14.5 10 16 10 18C10 20 11 22 12 22C13 22 14 20 14 18C14 16 15 14.5 16 13C17 11.5 18 10 18 7.5C18 4.5 15.5 2 12 2Z');
            } else {
                path.setAttribute('d', 'M12 0C7.31 0 3.5 3.81 3.5 8.5C3.5 14.88 12 24 12 24S20.5 14.88 20.5 8.5C20.5 3.81 16.69 0 12 0ZM12 12C10.07 12 8.5 10.43 8.5 8.5C8.5 6.57 10.07 5 12 5C13.93 5 15.5 6.57 15.5 8.5C15.5 10.43 13.93 12 12 12Z');
            }
            svg.appendChild(path);
            div.appendChild(svg);
            return div;
        }

        function createMarkerIcon(color, isActive) {
            var fillColor = isActive ? activeMarkerColor : color;
            var scale = isActive ? 1.2 : 1;

            if (markerStyle === 'custom' && customMarkerImage) {
                var imageUrl = (isActive && customActiveMarkerImage) ? customActiveMarkerImage : customMarkerImage;
                var size = isActive ? 48 : 40;
                return {
                    url: imageUrl,
                    scaledSize: new google.maps.Size(size, size),
                    anchor: new google.maps.Point(size / 2, size)
                };
            }
            if (markerStyle === 'circle') {
                return { path: google.maps.SymbolPath.CIRCLE, fillColor: fillColor, fillOpacity: 1, strokeColor: '#ffffff', strokeWeight: 2, scale: 10 * scale };
            }
            if (markerStyle === 'tooth') {
                return { path: 'M12 2C8.5 2 6 4.5 6 7.5C6 10 7 11.5 8 13C9 14.5 10 16 10 18C10 20 11 22 12 22C13 22 14 20 14 18C14 16 15 14.5 16 13C17 11.5 18 10 18 7.5C18 4.5 15.5 2 12 2Z', fillColor: fillColor, fillOpacity: 1, strokeColor: '#ffffff', strokeWeight: 1.5, scale: 1.5 * scale, anchor: new google.maps.Point(12, 22) };
            }
            return { path: 'M12 0C7.31 0 3.5 3.81 3.5 8.5C3.5 14.88 12 24 12 24S20.5 14.88 20.5 8.5C20.5 3.81 16.69 0 12 0ZM12 12C10.07 12 8.5 10.43 8.5 8.5C8.5 6.57 10.07 5 12 5C13.93 5 15.5 6.57 15.5 8.5C15.5 10.43 13.93 12 12 12Z', fillColor: fillColor, fillOpacity: 1, strokeColor: '#ffffff', strokeWeight: 1.5, scale: 1.3 * scale, anchor: new google.maps.Point(12, 24) };
        }

        function updateMarkerAppearance(marker, isActive) {
            if (useAdvancedMarkers && marker.content !== undefined) {
                marker.content = createAdvancedMarkerContent(markerColor, isActive);
            } else if (typeof marker.setIcon === 'function') {
                marker.setIcon(createMarkerIcon(markerColor, isActive));
            }
        }

        function escapeHtml(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // ── Map init ────────────────────────────────────────────────────────

        function initMap() {
            var mapContainer = document.getElementById(mapContainerId);
            if (!mapContainer) return;
            mapContainer.innerHTML = '';

            var mapOptions = { center: defaultCenter, zoom: defaultZoom, styles: mapStyle };
            if (useAdvancedMarkers) mapOptions.mapId = mapId;

            var map = new google.maps.Map(mapContainer, mapOptions);
            var bounds = new google.maps.LatLngBounds();
            var markers = [];

            locations.forEach(function (location) {
                var lat = parseFloat(location.lat);
                var lng = parseFloat(location.lng);
                if (!isFinite(lat) || !isFinite(lng)) return;

                var position    = { lat: lat, lng: lng };
                var safeName    = escapeHtml(String(location.name    || ''));
                var safeAddress = escapeHtml(String(location.address || ''));
                var safeCity    = escapeHtml(String(location.city    || ''));
                var safeState   = escapeHtml(String(location.state   || ''));
                var safeZip     = escapeHtml(String(location.zip     || ''));
                var safePhone   = escapeHtml(String(location.phone   || ''));

                var marker;
                if (useAdvancedMarkers && google.maps.marker && google.maps.marker.AdvancedMarkerElement) {
                    marker = new google.maps.marker.AdvancedMarkerElement({ position: position, map: map, title: safeName, content: createAdvancedMarkerContent(markerColor, false) });
                } else {
                    marker = new google.maps.Marker({ position: position, map: map, title: safeName, icon: createMarkerIcon(markerColor, false) });
                }

                var buttonsHtml = '';
                if (showDirections) {
                    buttonsHtml += '<a href="https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent(lat + ',' + lng) + '" target="_blank" rel="noopener" style="background:' + primaryColor + ';color:#fff;padding:6px 12px;border-radius:4px;text-decoration:none;font-size:12px;">Directions</a>';
                }
                if (showSchedule && location.booking_url) {
                    buttonsHtml += '<a href="' + encodeURI(String(location.booking_url)) + '" target="_blank" rel="noopener" style="background:' + accentColor + ';color:#fff;padding:6px 12px;border-radius:4px;text-decoration:none;font-size:12px;">Book</a>';
                }

                var infoContent = '<div style="padding:10px;max-width:250px;">' +
                    '<h3 style="margin:0 0 8px;font-size:16px;">' + safeName + '</h3>' +
                    '<p style="margin:0 0 8px;font-size:13px;color:#666;">' + safeAddress + '<br>' + safeCity + ', ' + safeState + ' ' + safeZip + '</p>' +
                    (safePhone ? '<p style="margin:0 0 10px;font-size:13px;">' + safePhone + '</p>' : '') +
                    (buttonsHtml ? '<div style="display:flex;gap:8px;">' + buttonsHtml + '</div>' : '') +
                    '</div>';

                var infoWindow = new google.maps.InfoWindow({ content: infoContent });
                marker.addListener('click', function () {
                    markers.forEach(function (m) { m.infoWindow.close(); updateMarkerAppearance(m, false); });
                    updateMarkerAppearance(marker, true);
                    infoWindow.open(map, marker);
                });
                marker.infoWindow = infoWindow;
                markers.push(marker);
                bounds.extend(position);
            });

            if (markers.length > 1) {
                map.fitBounds(bounds);
            } else if (markers.length === 1) {
                var pos = markers[0].position || markers[0].getPosition();
                map.setCenter(pos);
                map.setZoom(14);
            }

            var allCards = document.querySelectorAll('#' + containerId + ' .esl-location-card');
            allCards.forEach(function (card, index) {
                card.addEventListener('click', function () {
                    allCards.forEach(function (c) { c.classList.remove('active'); });
                    card.classList.add('active');
                    if (markers[index]) {
                        var p = markers[index].position || markers[index].getPosition();
                        map.setCenter(p);
                        map.setZoom(15);
                        google.maps.event.trigger(markers[index], 'click');
                    }
                });
            });

            window['eslMap_' + containerId] = { map: map, markers: markers };
        }

        // ── Distance / sort ─────────────────────────────────────────────────

        function calculateDistance(lat1, lng1, lat2, lng2) {
            var R = 3959;
            var dLat = (lat2 - lat1) * Math.PI / 180;
            var dLng = (lng2 - lng1) * Math.PI / 180;
            var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLng / 2) * Math.sin(dLng / 2);
            return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        }

        function sortLocationsByDistance(userLat, userLng) {
            var mapData = window['eslMap_' + containerId];
            if (!mapData) return;

            var sorted = locations.map(function (loc, index) {
                return { location: loc, index: index, distance: calculateDistance(userLat, userLng, parseFloat(loc.lat), parseFloat(loc.lng)) };
            }).sort(function (a, b) { return a.distance - b.distance; });

            if (!sorted.length || !mapData.markers.length) return;
            var nearest = sorted[0];
            mapData.map.setCenter({ lat: parseFloat(nearest.location.lat), lng: parseFloat(nearest.location.lng) });
            mapData.map.setZoom(13);

            var allCards = document.querySelectorAll('#' + containerId + ' .esl-location-card');
            allCards.forEach(function (c) { c.classList.remove('active'); });
            var nearestCard = document.querySelector('#' + containerId + ' .esl-location-card[data-lat="' + nearest.location.lat + '"][data-lng="' + nearest.location.lng + '"]');
            if (nearestCard) {
                nearestCard.classList.add('active');
                nearestCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
            if (mapData.markers[nearest.index]) {
                google.maps.event.trigger(mapData.markers[nearest.index], 'click');
            }
        }

        function sortLocationsSilent(userLat, userLng) {
            var list = document.getElementById(containerId + '-locations');
            if (!list) return;
            var cards = Array.from(list.querySelectorAll('.esl-location-card'));
            cards.sort(function (a, b) {
                return calculateDistance(userLat, userLng, parseFloat(a.getAttribute('data-lat')), parseFloat(a.getAttribute('data-lng'))) -
                    calculateDistance(userLat, userLng, parseFloat(b.getAttribute('data-lat')), parseFloat(b.getAttribute('data-lng')));
            });
            cards.forEach(function (card) { list.appendChild(card); });
        }

        // ── UI event handlers ────────────────────────────────────────────────

        document.getElementById(containerId + '-search-btn').addEventListener('click', function () {
            var query = document.getElementById(containerId + '-search').value.trim();
            if (!query) return;
            new google.maps.Geocoder().geocode({ address: query }, function (results, status) {
                if (status === 'OK' && results[0]) {
                    var loc = results[0].geometry.location;
                    sortLocationsByDistance(loc.lat(), loc.lng());
                } else {
                    alert('Location not found. Please try a different address.');
                }
            });
        });

        document.getElementById(containerId + '-search').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') document.getElementById(containerId + '-search-btn').click();
        });

        document.getElementById(containerId + '-location-btn').addEventListener('click', function () {
            var btn = this;
            var textSpan = btn.querySelector('.esl-location-btn-text');
            var originalText = textSpan.textContent;
            if (!navigator.geolocation) { alert('Geolocation is not supported by your browser.'); return; }
            btn.disabled = true;
            textSpan.textContent = 'Getting location...';
            navigator.geolocation.getCurrentPosition(
                function (pos) { sortLocationsByDistance(pos.coords.latitude, pos.coords.longitude); btn.disabled = false; textSpan.textContent = originalText; },
                function (err) {
                    btn.disabled = false; textSpan.textContent = originalText;
                    var msg = 'Unable to get your location.';
                    if (err.code === 1) msg = 'Location access denied. Please enable location permissions.';
                    else if (err.code === 2) msg = 'Location unavailable. Please try again.';
                    else if (err.code === 3) msg = 'Location request timed out. Please try again.';
                    alert(msg);
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
            );
        });

        // ── Google Maps loading ──────────────────────────────────────────────

        function autoPromptLocation() {
            if (!navigator.geolocation) return;
            setTimeout(function () {
                navigator.geolocation.getCurrentPosition(
                    function (pos) { sortLocationsSilent(pos.coords.latitude, pos.coords.longitude); },
                    function (err) { console.log('Auto-location not available:', err.message); },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
                );
            }, 500);
        }

        var callbackName = 'enamelInitMap_' + containerId.replace(/-/g, '_');
        var libraries    = useAdvancedMarkers ? '&libraries=marker' : '';

        function loadGoogleMaps() {
            if (typeof google !== 'undefined' && google.maps) {
                initMap(); autoPromptLocation();
            } else {
                var s = document.createElement('script');
                s.src = 'https://maps.googleapis.com/maps/api/js?key=' + apiKey + libraries + '&loading=async&callback=' + callbackName;
                s.async = true; s.defer = true;
                document.head.appendChild(s);
                window[callbackName] = function () { initMap(); autoPromptLocation(); };
            }
        }

        if (lazyLoad && 'IntersectionObserver' in window) {
            var el = document.getElementById(containerId);
            if (el) {
                new IntersectionObserver(function (entries, obs) {
                    entries.forEach(function (entry) { if (entry.isIntersecting) { obs.disconnect(); loadGoogleMaps(); } });
                }, { rootMargin: '200px' }).observe(el);
            } else {
                loadGoogleMaps();
            }
        } else {
            loadGoogleMaps();
        }
    }

    // ── Bootstrap ────────────────────────────────────────────────────────────

    function initPending() {
        var instances = window.enamelSLInstances || {};
        Object.keys(instances).forEach(function (id) {
            if (!instances[id]._initialized) {
                instances[id]._initialized = true;
                initInstance(id, instances[id]);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPending);
    } else {
        initPending();
    }
})();

import { useState, useCallback, useEffect } from 'react';
import MapView, { type StoreLocation } from './MapView';
import LocationCard from './LocationCard';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { MapIcon, MapPin, Search, Crosshair } from 'lucide-react';

// Resolve the WP REST API URL:
// 1. Injected by WordPress via wp_localize_script (production)
// 2. VITE_WP_API_URL env var (local dev pointing at live WP site)
// 3. Relative URL fallback (works when React is embedded in WP)
declare global {
  interface Window { enamelLocatorConfig?: { apiUrl: string } }
}
const WP_API_URL =
  window.enamelLocatorConfig?.apiUrl ??
  import.meta.env.VITE_WP_API_URL ??
  '/wp-json/enamel-sl/v1/locations';

interface DentalLocatorProps {
  defaultCenter?: { lat: number; lng: number };
  defaultZoom?: number;
  className?: string;
}

export default function DentalLocator({
  defaultCenter = { lat: 30.3072, lng: -97.7560 }, // Austin, TX
  defaultZoom = 10,
  className = ""
}: DentalLocatorProps) {
  const [locations, setLocations] = useState<StoreLocation[]>([]);
  const [isLoadingLocations, setIsLoadingLocations] = useState(true);
  const [selectedLocation, setSelectedLocation] = useState<StoreLocation | null>(null);
  const [mapCenter, setMapCenter] = useState(defaultCenter);
  const [userLocation, setUserLocation] = useState<{ lat: number; lng: number } | null>(null);
  const [searchValue, setSearchValue] = useState('');
  const [isLoadingLocation, setIsLoadingLocation] = useState(false);

  // Fetch locations from WordPress REST API
  useEffect(() => {
    fetch(WP_API_URL)
      .then(res => res.json())
      .then((data: StoreLocation[]) => {
        setLocations(data);
      })
      .catch(() => {
        // Fallback mock data for local preview
        setLocations([
          { id: '1', name: 'Enamel Dentistry Leander', address: '128 South Brook Drive', city: 'Leander', state: 'TX', zip: '78641', phone: '+1 (512) 337-3415', lat: 30.5788, lng: -97.8531, hours: 'Monday: 8:00AM – 6:00PM\nTuesday: 8:00AM – 6:00PM\nWednesday: 8:00AM – 6:00PM', booking_url: '#', rating: 5 },
          { id: '2', name: 'Enamel Dentistry Easton Park', address: '7101 East William Cannon Drive', city: 'Austin', state: 'TX', zip: '78744', phone: '+1 (512) 489-4015', lat: 30.1869, lng: -97.7198, hours: 'Monday: 8:00AM – 6:00PM\nTuesday: 8:00AM – 6:00PM', booking_url: '#', rating: 5 },
          { id: '3', name: 'Enamel Dentistry Manor', address: '14008 Shadow Glen Boulevard', city: 'Manor', state: 'TX', zip: '78653', phone: '+1 (512) 982-1272', lat: 30.3418, lng: -97.5567, hours: 'Monday: 8:00AM – 6:00PM\nFriday: 8:00AM – 5:00PM', booking_url: '#', rating: 5 },
          { id: '4', name: 'Enamel Dentistry At The Grove', address: '4301 Bull Creek Road', city: 'Austin', state: 'TX', zip: '78731', phone: '+1 (512) 884-5658', lat: 30.3356, lng: -97.7526, hours: 'Monday: 8:00AM – 6:00PM\nSaturday: 9:00AM – 3:00PM', booking_url: '#', rating: 5 },
          { id: '5', name: 'Enamel Dentistry The Domain', address: '11005 Burnet Road', city: 'Austin', state: 'TX', zip: '78758', phone: '+1 (512) 646-0815', lat: 30.4015, lng: -97.7198, hours: 'Monday: 8:00AM – 7:00PM\nSaturday: 9:00AM – 4:00PM', booking_url: '#', rating: 5 },
          { id: '6', name: 'Enamel Dentistry Saltillo (East Austin)', address: '901 East 5th Street', city: 'Austin', state: 'TX', zip: '78702', phone: '+1 (512) 649-7510', lat: 30.2637, lng: -97.7298, hours: 'Tuesday: 9:00AM – 6:00PM\nSaturday: 9:00AM – 3:00PM', booking_url: '#', rating: 5 },
          { id: '7', name: 'Enamel Dentistry Lantana', address: '7415 Southwest Parkway Building 6 #200', city: 'Austin', state: 'TX', zip: '78735', phone: '+1 (512) 648-6115', lat: 30.2456, lng: -97.8456, hours: 'Monday: 8:00AM – 6:00PM', booking_url: '#', rating: 5 },
          { id: '8', name: 'Enamel Dentistry Parmer Park', address: '1606 East Parmer Lane', city: 'Austin', state: 'TX', zip: '78753', phone: '+1 (512) 572-0215', lat: 30.4234, lng: -97.6789, hours: 'Monday: 8:00AM – 6:00PM\nFriday: 8:00AM – 5:00PM', booking_url: '#', rating: 4 },
          { id: '9', name: 'Enamel Dentistry & Sleep Wellness', address: '6700 Alma Road', city: 'McKinney', state: 'TX', zip: '75070', phone: '+1 (469) 663-0515', lat: 33.1976, lng: -96.6398, hours: 'Monday: 8:00AM – 6:00PM\nSaturday: 9:00AM – 2:00PM', booking_url: '#', rating: 5 },
        ]);
      })
      .finally(() => {
        setIsLoadingLocations(false);
      });
  }, []);

  // Auto-prompt for user's location on page load (without scrolling)
  useEffect(() => {
    if (!navigator.geolocation) {
      console.log('Geolocation is not supported');
      return;
    }

    // Small delay to let page render first, then prompt for location
    const timer = setTimeout(() => {
      navigator.geolocation.getCurrentPosition(
        (position) => {
          const location = {
            lat: position.coords.latitude,
            lng: position.coords.longitude
          };
          // Only update user location for distance calculations, don't center map or scroll
          setUserLocation(location);
          console.log('Auto-detected user location:', location);
        },
        (error) => {
          console.log('User declined or error getting location:', error);
        },
        { enableHighAccuracy: true, timeout: 10000 }
      );
    }, 500);

    return () => clearTimeout(timer);
  }, []);

  const handleLocationSelect = useCallback((location: StoreLocation | null) => {
    setSelectedLocation(location);
    console.log('Location selected:', location?.name);
  }, []);

  const handleGetDirections = useCallback((location: StoreLocation) => {
    const url = `https://www.google.com/maps/dir/?api=1&destination=${location.lat},${location.lng}`;
    window.open(url, '_blank');
    console.log('Get directions clicked for:', location.name);
  }, []);

  const handleCallOffice = useCallback((location: StoreLocation) => {
    if (location.phone) {
      window.location.href = `tel:${location.phone}`;
      console.log('Call clicked for:', location.name);
    }
  }, []);

  const handleLocationSearch = useCallback(async (address: string) => {
    console.log('Searching for location:', address);
    // For demo purposes, center map on first location if searching for Austin area
    if (address.toLowerCase().includes('austin') || address.startsWith('78')) {
      setMapCenter({ lat: 30.3072, lng: -97.7560 });
    }
    setSelectedLocation(null);
  }, []);

  // Helper function to calculate distance between two points (Haversine formula)
  const calculateDistance = useCallback((lat1: number, lng1: number, lat2: number, lng2: number): number => {
    const R = 3959; // Earth's radius in miles
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLng / 2) * Math.sin(dLng / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
  }, []);

  // Find nearest location to given coordinates
  const findNearestLocation = useCallback((userLat: number, userLng: number): StoreLocation | null => {
    if (locations.length === 0) return null;
    
    let nearest = locations[0];
    let minDistance = calculateDistance(userLat, userLng, nearest.lat, nearest.lng);
    
    for (const location of locations) {
      const distance = calculateDistance(userLat, userLng, location.lat, location.lng);
      if (distance < minDistance) {
        minDistance = distance;
        nearest = location;
      }
    }
    
    return nearest;
  }, [calculateDistance]);

  const handleCurrentLocation = useCallback(() => {
    if (!navigator.geolocation) {
      console.log('Geolocation is not supported');
      return;
    }

    setIsLoadingLocation(true);
    navigator.geolocation.getCurrentPosition(
      (position) => {
        const location = {
          lat: position.coords.latitude,
          lng: position.coords.longitude
        };
        setUserLocation(location);
        
        // Find and select the nearest location
        const nearest = findNearestLocation(location.lat, location.lng);
        if (nearest) {
          setSelectedLocation(nearest);
          setMapCenter({ lat: nearest.lat, lng: nearest.lng });
          console.log('Nearest location found:', nearest.name);
        } else {
          setMapCenter(location);
        }
        
        setIsLoadingLocation(false);
        console.log('Current location obtained:', location);
      },
      (error) => {
        console.log('Error getting location:', error);
        setIsLoadingLocation(false);
      },
      { enableHighAccuracy: true, timeout: 10000 }
    );
  }, [findNearestLocation]);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (searchValue.trim()) {
      handleLocationSearch(searchValue.trim());
    }
  };

  return (
    <div className={`w-full h-screen flex flex-col lg:flex-row ${className}`} data-testid="dental-locator">
      {/* Sidebar with locations */}
      <div className="w-full lg:w-96 bg-background border-r flex flex-col">
        <div className="p-6 border-b bg-primary text-primary-foreground">
          <h1 className="text-2xl font-heading font-bold mb-2">
            Find Your Nearest Location
          </h1>
          <p className="text-primary-foreground/90 text-sm">
            Quality dental care across Texas with {locations.length} convenient locations
          </p>
        </div>

        {/* Location Search Section */}
        <div className="p-4 border-b bg-background">
          <Card>
            <CardContent className="p-4 space-y-4">
              <div>
                <h3 className="font-heading font-semibold text-sm mb-3">Find Nearest Location</h3>
                
                <form onSubmit={handleSubmit} className="space-y-3">
                  <div className="relative">
                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                    <Input
                      type="text"
                      placeholder="Enter address or zip code"
                      value={searchValue}
                      onChange={(e) => setSearchValue(e.target.value)}
                      className="pl-10"
                      data-testid="input-location-search"
                    />
                  </div>
                  
                  <Button 
                    type="submit" 
                    className="w-full"
                    size="sm"
                    disabled={!searchValue.trim()}
                    data-testid="button-search-location"
                  >
                    <Search className="w-4 h-4 mr-2" />
                    Search
                  </Button>
                </form>

                <div className="relative my-3">
                  <div className="absolute inset-0 flex items-center">
                    <span className="w-full border-t" />
                  </div>
                  <div className="relative flex justify-center text-xs uppercase">
                    <span className="bg-card px-2 text-muted-foreground">or</span>
                  </div>
                </div>

                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  className="w-full"
                  onClick={handleCurrentLocation}
                  disabled={isLoadingLocation}
                  data-testid="button-current-location"
                >
                  {isLoadingLocation ? (
                    <>
                      <div className="w-4 h-4 border-2 border-current border-t-transparent rounded-full animate-spin mr-2" />
                      Getting location...
                    </>
                  ) : (
                    <>
                      <Crosshair className="w-4 h-4 mr-2" />
                      Use My Location
                    </>
                  )}
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>

        <div className="flex-1 overflow-hidden">
          <ScrollArea className="h-full">
            <div className="p-4 space-y-4">
              {isLoadingLocations ? (
                <div className="flex items-center justify-center py-12">
                  <div className="w-6 h-6 border-2 border-primary border-t-transparent rounded-full animate-spin" />
                </div>
              ) : locations.map((location) => (
                <Card 
                  key={location.id}
                  className={`cursor-pointer transition-all hover-elevate ${
                    selectedLocation?.id === location.id ? 'ring-2 ring-primary ring-offset-2' : ''
                  }`}
                  onClick={() => handleLocationSelect(location)}
                  data-testid={`card-location-${location.id}`}
                >
                  <CardContent className="p-4 space-y-3">
                    <div>
                      <h3 className="font-heading font-semibold text-base text-primary mb-1">
                        {location.name}
                      </h3>
                      <div className="flex items-start gap-2">
                        <MapPin className="w-4 h-4 text-muted-foreground mt-0.5 flex-shrink-0" />
                        <p className="text-sm text-muted-foreground leading-relaxed">
                          {location.address}
                        </p>
                      </div>
                    </div>

                    {(location.phone || location.hours) && (
                      <div className="space-y-2 pt-2 border-t">
                        {location.phone && (
                          <div className="flex items-center justify-between">
                            <span className="text-sm font-medium">Phone:</span>
                            <button
                              onClick={(e) => {
                                e.stopPropagation();
                                handleCallOffice(location);
                              }}
                              className="text-sm text-primary hover:text-primary/80 transition-colors"
                              data-testid={`button-call-${location.id}`}
                            >
                              {location.phone}
                            </button>
                          </div>
                        )}
                        
                        {location.hours && (
                          <div className="flex items-start justify-between gap-2">
                            <span className="text-sm font-medium flex-shrink-0">Hours:</span>
                            <span className="text-sm text-muted-foreground text-right">
                              {location.hours}
                            </span>
                          </div>
                        )}
                      </div>
                    )}

                    <div className="flex gap-2 pt-2">
                      <button 
                        onClick={(e) => {
                          e.stopPropagation();
                          handleGetDirections(location);
                        }}
                        className="flex-1 bg-primary hover:bg-primary/90 text-primary-foreground text-sm font-medium py-2 px-3 rounded-md transition-colors"
                        data-testid={`button-directions-${location.id}`}
                      >
                        Get Directions
                      </button>
                      
                      {location.phone && (
                        <button 
                          onClick={(e) => {
                            e.stopPropagation();
                            handleCallOffice(location);
                          }}
                          className="bg-accent hover:bg-accent/90 text-accent-foreground text-sm font-medium py-2 px-3 rounded-md transition-colors"
                          data-testid={`button-call-primary-${location.id}`}
                        >
                          Call
                        </button>
                      )}
                    </div>
                  </CardContent>
                </Card>
              ))}
            </div>
          </ScrollArea>
        </div>

        {/* Footer with brand info */}
        <div className="p-4 border-t bg-muted/30 text-center">
          <p className="text-xs text-muted-foreground">
            Established in 2016 • Quality dental care using the latest technology
          </p>
        </div>
      </div>

      {/* Map */}
      <div className="flex-1">
        <MapView
          center={mapCenter}
          zoom={defaultZoom}
          stores={locations}
          selectedStore={selectedLocation}
          onStoreSelect={handleLocationSelect}
          userLocation={userLocation}
          className="h-full"
        />
      </div>
    </div>
  );
}
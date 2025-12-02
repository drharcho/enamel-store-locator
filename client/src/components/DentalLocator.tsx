import { useState, useCallback, useEffect } from 'react';
import MapView, { type StoreLocation } from './MapView';
import LocationCard from './LocationCard';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { MapIcon, MapPin, Search, Crosshair } from 'lucide-react';

// Sample dental clinic locations - these would come from WordPress backend
const DENTAL_LOCATIONS: StoreLocation[] = [
  {
    id: '1',
    name: 'Enamel Dentistry - Downtown Austin',
    address: '123 Congress Avenue, Austin, TX 78701',
    lat: 30.2672,
    lng: -97.7431,
    phone: '(512) 555-0123',
    hours: 'Mon-Fri 8AM-6PM, Sat 9AM-3PM'
  },
  {
    id: '2',
    name: 'Enamel Dentistry - Cedar Park',
    address: '456 Ranch Road 620, Cedar Park, TX 78613',
    lat: 30.5052,
    lng: -97.8203,
    phone: '(512) 555-0456',
    hours: 'Mon-Thu 8AM-7PM, Fri 8AM-5PM'
  },
  {
    id: '3',
    name: 'Enamel Dentistry - Round Rock',
    address: '789 Main Street, Round Rock, TX 78664',
    lat: 30.5085,
    lng: -97.6789,
    phone: '(512) 555-0789',
    hours: 'Mon-Fri 8AM-6PM, Sat 8AM-2PM'
  },
  {
    id: '4',
    name: 'Enamel Dentistry - South Austin',
    address: '101 South Lamar Blvd, Austin, TX 78704',
    lat: 30.2500,
    lng: -97.7667,
    phone: '(512) 555-0101',
    hours: 'Tue-Sat 9AM-6PM'
  },
  {
    id: '5',
    name: 'Enamel Dentistry - The Domain',
    address: '202 Domain Drive, Austin, TX 78758',
    lat: 30.4000,
    lng: -97.7200,
    phone: '(512) 555-0202',
    hours: 'Mon-Fri 8AM-7PM, Sat 9AM-4PM'
  }
];

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
  const [selectedLocation, setSelectedLocation] = useState<StoreLocation | null>(null);
  const [mapCenter, setMapCenter] = useState(defaultCenter);
  const [userLocation, setUserLocation] = useState<{ lat: number; lng: number } | null>(null);
  const [searchValue, setSearchValue] = useState('');
  const [isLoadingLocation, setIsLoadingLocation] = useState(false);

  // Auto-prompt for user's location on page load
  useEffect(() => {
    if (!navigator.geolocation) {
      console.log('Geolocation is not supported');
      return;
    }

    // Small delay to let page render first, then prompt for location
    const timer = setTimeout(() => {
      setIsLoadingLocation(true);
      navigator.geolocation.getCurrentPosition(
        (position) => {
          const location = {
            lat: position.coords.latitude,
            lng: position.coords.longitude
          };
          setUserLocation(location);
          setMapCenter(location);
          setIsLoadingLocation(false);
          console.log('Auto-detected user location:', location);
        },
        (error) => {
          console.log('User declined or error getting location:', error);
          setIsLoadingLocation(false);
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
        setMapCenter(location);
        setSelectedLocation(null);
        setIsLoadingLocation(false);
        console.log('Current location obtained:', location);
      },
      (error) => {
        console.log('Error getting location:', error);
        setIsLoadingLocation(false);
      },
      { enableHighAccuracy: true, timeout: 10000 }
    );
  }, []);

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
            Quality dental care across Texas with {DENTAL_LOCATIONS.length} convenient locations
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
              {DENTAL_LOCATIONS.map((location) => (
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
          stores={DENTAL_LOCATIONS}
          selectedStore={selectedLocation}
          onStoreSelect={handleLocationSelect}
          userLocation={userLocation}
          className="h-full"
        />
      </div>
    </div>
  );
}
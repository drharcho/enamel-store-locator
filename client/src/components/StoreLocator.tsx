import { useState, useEffect, useCallback } from 'react';
import MapView, { type StoreLocation } from './MapView';
import SearchPanel from './SearchPanel';
import FilterControls from './FilterControls';
import LocationCard from './LocationCard';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { MapIcon, List } from 'lucide-react';
import { Button } from '@/components/ui/button';

// todo: remove mock store data
const MOCK_STORES: StoreLocation[] = [
  {
    id: '1',
    name: 'Downtown Medical Center',
    address: '123 Main St, New York, NY 10001',
    lat: 40.7589,
    lng: -73.9851,
    phone: '(212) 555-0123',
    hours: 'Mon-Fri 9AM-6PM',
    distance: 0.8
  },
  {
    id: '2',
    name: 'Midtown Health Clinic',
    address: '456 Broadway, New York, NY 10013',
    lat: 40.7505,
    lng: -73.9934,
    phone: '(212) 555-0456',
    hours: 'Mon-Sat 8AM-8PM',
    distance: 1.2
  },
  {
    id: '3',
    name: 'Upper East Side Practice',
    address: '789 Park Ave, New York, NY 10021',
    lat: 40.7736,
    lng: -73.9566,
    phone: '(212) 555-0789',
    hours: 'Mon-Fri 9AM-5PM',
    distance: 2.1
  },
  {
    id: '4',
    name: 'Brooklyn Heights Office',
    address: '101 Court St, Brooklyn, NY 11201',
    lat: 40.6931,
    lng: -73.9915,
    phone: '(718) 555-0101',
    hours: 'Tue-Sat 10AM-7PM',
    distance: 3.5
  },
  {
    id: '5',
    name: 'Queens Community Center',
    address: '202 Northern Blvd, Queens, NY 11361',
    lat: 40.7614,
    lng: -73.7776,
    phone: '(718) 555-0202',
    hours: 'Mon-Fri 8AM-6PM',
    distance: 12.8
  }
];

interface StoreLocatorProps {
  defaultCenter?: { lat: number; lng: number };
  defaultZoom?: number;
  defaultRadius?: number;
  className?: string;
}

export default function StoreLocator({
  defaultCenter = { lat: 40.7614, lng: -73.9776 }, // NYC
  defaultZoom = 12,
  defaultRadius = 10,
  className = ""
}: StoreLocatorProps) {
  const [mapCenter, setMapCenter] = useState(defaultCenter);
  const [selectedStore, setSelectedStore] = useState<StoreLocation | null>(null);
  const [userLocation, setUserLocation] = useState<{ lat: number; lng: number } | null>(null);
  const [radius, setRadius] = useState(defaultRadius);
  const [isLoadingLocation, setIsLoadingLocation] = useState(false);
  const [showMobileList, setShowMobileList] = useState(false);

  // Filter stores by radius
  const filteredStores = MOCK_STORES.filter(store => 
    !store.distance || store.distance <= radius
  );

  const handleLocationSearch = useCallback(async (address: string) => {
    console.log('Searching for location:', address);
    // todo: implement actual geocoding
    // For demo, simulate geocoding result
    if (address.toLowerCase().includes('brooklyn')) {
      setMapCenter({ lat: 40.6892, lng: -73.9442 });
    } else if (address.toLowerCase().includes('queens')) {
      setMapCenter({ lat: 40.7282, lng: -73.7949 });
    } else {
      setMapCenter({ lat: 40.7614, lng: -73.9776 });
    }
    setSelectedStore(null);
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
        setSelectedStore(null);
        setIsLoadingLocation(false);
        console.log('Current location obtained:', location);
      },
      (error) => {
        console.log('Error getting location:', error);
        setIsLoadingLocation(false);
        // todo: show error toast
      },
      { enableHighAccuracy: true, timeout: 10000 }
    );
  }, []);

  const handleStoreSelect = useCallback((store: StoreLocation | null) => {
    setSelectedStore(store);
    if (store) {
      setMapCenter({ lat: store.lat, lng: store.lng });
    }
  }, []);

  const handleGetDirections = useCallback((store: StoreLocation) => {
    const url = `https://www.google.com/maps/dir/?api=1&destination=${store.lat},${store.lng}`;
    window.open(url, '_blank');
  }, []);

  return (
    <div className={`w-full h-screen flex flex-col lg:flex-row ${className}`} data-testid="store-locator">
      {/* Mobile view toggle */}
      <div className="lg:hidden p-4 border-b bg-background">
        <div className="flex gap-2">
          <Button
            variant={!showMobileList ? "default" : "outline"}
            size="sm"
            onClick={() => setShowMobileList(false)}
            className="flex-1"
            data-testid="button-mobile-map"
          >
            <MapIcon className="w-4 h-4 mr-2" />
            Map
          </Button>
          <Button
            variant={showMobileList ? "default" : "outline"}
            size="sm"
            onClick={() => setShowMobileList(true)}
            className="flex-1"
            data-testid="button-mobile-list"
          >
            <List className="w-4 h-4 mr-2" />
            List ({filteredStores.length})
          </Button>
        </div>
      </div>

      {/* Sidebar */}
      <div className={`w-full lg:w-80 xl:w-96 bg-background border-r flex flex-col ${
        showMobileList ? 'flex' : 'hidden lg:flex'
      }`}>
        <div className="p-4 space-y-4 border-b">
          <SearchPanel
            onLocationSearch={handleLocationSearch}
            onCurrentLocation={handleCurrentLocation}
            isLoadingLocation={isLoadingLocation}
          />
          
          <FilterControls
            radius={radius}
            onRadiusChange={setRadius}
            storeCount={filteredStores.length}
          />
        </div>

        <div className="flex-1 overflow-hidden">
          <div className="p-4">
            <Card>
              <CardHeader className="pb-3">
                <CardTitle className="text-base">
                  Nearby Locations ({filteredStores.length})
                </CardTitle>
              </CardHeader>
              <CardContent className="p-0">
                <ScrollArea className="h-[400px] lg:h-[calc(100vh-400px)]">
                  <div className="p-4 pt-0 space-y-3">
                    {filteredStores.length > 0 ? (
                      filteredStores.map((store) => (
                        <LocationCard
                          key={store.id}
                          store={store}
                          isSelected={selectedStore?.id === store.id}
                          onSelect={handleStoreSelect}
                          onGetDirections={handleGetDirections}
                        />
                      ))
                    ) : (
                      <div className="text-center py-8 text-muted-foreground">
                        <MapIcon className="w-8 h-8 mx-auto mb-2 opacity-50" />
                        <p>No locations found within {radius} miles</p>
                        <p className="text-sm">Try increasing the search radius</p>
                      </div>
                    )}
                  </div>
                </ScrollArea>
              </CardContent>
            </Card>
          </div>
        </div>
      </div>

      {/* Map */}
      <div className={`flex-1 ${showMobileList ? 'hidden lg:block' : 'block'}`}>
        <MapView
          center={mapCenter}
          zoom={defaultZoom}
          stores={filteredStores}
          selectedStore={selectedStore}
          onStoreSelect={handleStoreSelect}
          userLocation={userLocation}
          className="h-full"
        />
      </div>
    </div>
  );
}
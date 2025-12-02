import { GoogleMap, Marker, InfoWindow, useJsApiLoader } from '@react-google-maps/api';
import { useState, useCallback } from 'react';
import { MapPin, Clock, Phone, Navigation } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

export interface StoreLocation {
  id: string;
  name: string;
  address: string;
  lat: number;
  lng: number;
  phone?: string;
  hours?: string;
  distance?: number;
  scheduleLink?: string;
}

interface MapViewProps {
  center: { lat: number; lng: number };
  zoom: number;
  stores: StoreLocation[];
  selectedStore?: StoreLocation | null;
  onStoreSelect: (store: StoreLocation | null) => void;
  userLocation?: { lat: number; lng: number } | null;
  className?: string;
}

const mapContainerStyle = {
  width: '100%',
  height: '100%',
};

const libraries: ("places" | "geometry" | "drawing" | "visualization")[] = ['places'];

export default function MapView({
  center,
  zoom,
  stores,
  selectedStore,
  onStoreSelect,
  userLocation,
  className = ""
}: MapViewProps) {
  const apiKey = import.meta.env.VITE_GOOGLE_MAPS_API_KEY;
  const { isLoaded, loadError } = useJsApiLoader({
    id: 'google-map-script',
    googleMapsApiKey: apiKey || 'demo-key',
    libraries
  });

  const [map, setMap] = useState<google.maps.Map | null>(null);

  const onLoad = useCallback((map: google.maps.Map) => {
    setMap(map);
  }, []);

  const onUnmount = useCallback(() => {
    setMap(null);
  }, []);

  const handleMarkerClick = (store: StoreLocation) => {
    onStoreSelect(store);
    console.log('Store marker clicked:', store.name);
  };

  const handleGetDirections = (store: StoreLocation) => {
    const url = `https://www.google.com/maps/dir/?api=1&destination=${store.lat},${store.lng}`;
    window.open(url, '_blank');
    console.log('Get directions clicked for:', store.name);
  };

  if (loadError || !apiKey) {
    return (
      <div className={`flex items-center justify-center bg-muted rounded-lg ${className}`}>
        <div className="text-center space-y-4 p-8">
          <MapPin className="w-16 h-16 mx-auto text-muted-foreground" />
          <div>
            <h3 className="text-lg font-semibold mb-2">Interactive Map Preview</h3>
            <p className="text-muted-foreground mb-4">
              This is where your Google Maps integration will appear
            </p>
            <div className="text-sm text-muted-foreground space-y-2">
              <p>✓ Custom clinic markers with popups</p>
              <p>✓ Interactive location details</p>
              <p>✓ Get directions functionality</p>
              <p className="text-xs mt-4 text-primary">
                Add your Google Maps API key to see the live map
              </p>
            </div>
          </div>
        </div>
      </div>
    );
  }

  if (!isLoaded) {
    return (
      <div className={`flex items-center justify-center bg-muted rounded-lg ${className}`}>
        <div className="text-center space-y-4">
          <div className="w-8 h-8 border-4 border-primary border-t-transparent rounded-full animate-spin mx-auto"></div>
          <p className="text-muted-foreground">Loading map...</p>
        </div>
      </div>
    );
  }

  return (
    <div className={`relative rounded-lg overflow-hidden shadow-lg ${className}`} data-testid="map-container">
      <GoogleMap
        mapContainerStyle={mapContainerStyle}
        center={center}
        zoom={zoom}
        onLoad={onLoad}
        onUnmount={onUnmount}
        options={{
          zoomControl: true,
          streetViewControl: false,
          mapTypeControl: false,
          fullscreenControl: false,
        }}
      >
        {/* User location marker */}
        {userLocation && (
          <Marker
            position={userLocation}
            icon={{
              path: google.maps.SymbolPath.CIRCLE,
              scale: 8,
              fillColor: '#4285F4',
              fillOpacity: 1,
              strokeColor: '#ffffff',
              strokeWeight: 2,
            }}
            title="Your location"
          />
        )}

        {/* Store markers */}
        {stores.map((store) => (
          <Marker
            key={store.id}
            position={{ lat: store.lat, lng: store.lng }}
            onClick={() => handleMarkerClick(store)}
            icon={{
              path: google.maps.SymbolPath.BACKWARD_CLOSED_ARROW,
              scale: 6,
              fillColor: 'hsl(220 100% 35%)',
              fillOpacity: 1,
              strokeColor: '#ffffff',
              strokeWeight: 2,
            }}
            title={store.name}
          />
        ))}

        {/* Info window for selected store */}
        {selectedStore && (
          <InfoWindow
            position={{ lat: selectedStore.lat, lng: selectedStore.lng }}
            onCloseClick={() => onStoreSelect(null)}
          >
            <Card className="border-0 shadow-none w-64" data-testid={`info-window-${selectedStore.id}`}>
              <CardContent className="p-4 space-y-3">
                <div>
                  <h3 className="font-semibold text-base">{selectedStore.name}</h3>
                  <div className="flex items-start gap-2 mt-1">
                    <MapPin className="w-4 h-4 text-muted-foreground mt-0.5 flex-shrink-0" />
                    <p className="text-sm text-muted-foreground">{selectedStore.address}</p>
                  </div>
                </div>

                {selectedStore.distance && (
                  <p className="text-sm font-medium text-primary">
                    {selectedStore.distance.toFixed(1)} miles away
                  </p>
                )}

                <div className="space-y-2">
                  {selectedStore.phone && (
                    <div className="flex items-center gap-2">
                      <Phone className="w-4 h-4 text-muted-foreground" />
                      <span className="text-sm">{selectedStore.phone}</span>
                    </div>
                  )}
                  
                  {selectedStore.hours && (
                    <div className="flex items-center gap-2">
                      <Clock className="w-4 h-4 text-muted-foreground" />
                      <span className="text-sm">{selectedStore.hours}</span>
                    </div>
                  )}
                </div>

                <Button 
                  size="sm" 
                  className="w-full"
                  onClick={() => handleGetDirections(selectedStore)}
                  data-testid={`button-directions-${selectedStore.id}`}
                >
                  <Navigation className="w-4 h-4 mr-2" />
                  Get Directions
                </Button>
              </CardContent>
            </Card>
          </InfoWindow>
        )}
      </GoogleMap>
    </div>
  );
}
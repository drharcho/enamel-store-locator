import MapView, { type StoreLocation } from '../MapView';
import { useState } from 'react';

const mockStores: StoreLocation[] = [
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
  }
];

export default function MapViewExample() {
  const [selectedStore, setSelectedStore] = useState<StoreLocation | null>(null);
  
  return (
    <div className="h-96">
      <MapView
        center={{ lat: 40.7614, lng: -73.9776 }}
        zoom={13}
        stores={mockStores}
        selectedStore={selectedStore}
        onStoreSelect={setSelectedStore}
        userLocation={{ lat: 40.7614, lng: -73.9776 }}
      />
    </div>
  );
}
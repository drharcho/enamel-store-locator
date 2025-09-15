import LocationCard from '../LocationCard';
import { useState } from 'react';
import type { StoreLocation } from '../MapView';

const mockStore: StoreLocation = {
  id: '1',
  name: 'Downtown Medical Center',
  address: '123 Main St, New York, NY 10001',
  lat: 40.7589,
  lng: -73.9851,
  phone: '(212) 555-0123',
  hours: 'Mon-Fri 9AM-6PM',
  distance: 0.8
};

export default function LocationCardExample() {
  const [isSelected, setIsSelected] = useState(false);

  return (
    <div className="max-w-md space-y-4">
      <LocationCard
        store={mockStore}
        isSelected={isSelected}
        onSelect={() => setIsSelected(!isSelected)}
        onGetDirections={(store) => console.log('Directions for:', store.name)}
      />
    </div>
  );
}
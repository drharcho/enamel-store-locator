import StoreLocator from '../StoreLocator';

export default function StoreLocatorExample() {
  return (
    <div className="h-screen">
      <StoreLocator
        defaultCenter={{ lat: 40.7614, lng: -73.9776 }}
        defaultZoom={12}
        defaultRadius={10}
      />
    </div>
  );
}
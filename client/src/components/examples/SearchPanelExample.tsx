import SearchPanel from '../SearchPanel';
import { useState } from 'react';

export default function SearchPanelExample() {
  const [isLoading, setIsLoading] = useState(false);

  return (
    <div className="max-w-md">
      <SearchPanel
        onLocationSearch={(address) => console.log('Search:', address)}
        onCurrentLocation={() => {
          setIsLoading(true);
          setTimeout(() => setIsLoading(false), 2000);
        }}
        isLoadingLocation={isLoading}
      />
    </div>
  );
}
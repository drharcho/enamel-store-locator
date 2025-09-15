import FilterControls from '../FilterControls';
import { useState } from 'react';

export default function FilterControlsExample() {
  const [radius, setRadius] = useState(10);

  return (
    <div className="max-w-md">
      <FilterControls
        radius={radius}
        onRadiusChange={setRadius}
        storeCount={12}
      />
    </div>
  );
}
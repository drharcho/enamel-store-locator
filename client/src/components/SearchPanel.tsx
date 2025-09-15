import { useState } from 'react';
import { Search, MapPin, Crosshair } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

interface SearchPanelProps {
  onLocationSearch: (address: string) => void;
  onCurrentLocation: () => void;
  isLoadingLocation?: boolean;
  className?: string;
}

export default function SearchPanel({
  onLocationSearch,
  onCurrentLocation,
  isLoadingLocation = false,
  className = ""
}: SearchPanelProps) {
  const [searchValue, setSearchValue] = useState('');

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (searchValue.trim()) {
      onLocationSearch(searchValue.trim());
      console.log('Location search submitted:', searchValue);
    }
  };

  const handleCurrentLocation = () => {
    onCurrentLocation();
    console.log('Current location requested');
  };

  return (
    <Card className={`w-full max-w-md ${className}`} data-testid="search-panel">
      <CardHeader className="pb-4">
        <CardTitle className="text-lg flex items-center gap-2">
          <MapPin className="w-5 h-5" />
          Find Locations
        </CardTitle>
      </CardHeader>
      
      <CardContent className="space-y-4">
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
            disabled={!searchValue.trim()}
            data-testid="button-search-location"
          >
            <Search className="w-4 h-4 mr-2" />
            Search Location
          </Button>
        </form>

        <div className="relative">
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
              Use Current Location
            </>
          )}
        </Button>

        <div className="pt-2">
          <p className="text-xs text-muted-foreground mb-2">Quick searches:</p>
          <div className="flex flex-wrap gap-2">
            {/* //todo: remove mock quick search options */}
            {['New York, NY', 'Los Angeles, CA', '90210'].map((location) => (
              <Badge
                key={location}
                variant="outline"
                className="cursor-pointer hover-elevate"
                onClick={() => {
                  setSearchValue(location);
                  onLocationSearch(location);
                }}
                data-testid={`badge-quick-search-${location.replace(/[^a-zA-Z0-9]/g, '-')}`}
              >
                {location}
              </Badge>
            ))}
          </div>
        </div>
      </CardContent>
    </Card>
  );
}
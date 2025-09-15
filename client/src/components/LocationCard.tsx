import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { MapPin, Phone, Clock, Navigation, ExternalLink } from 'lucide-react';
import type { StoreLocation } from './MapView';

interface LocationCardProps {
  store: StoreLocation;
  isSelected?: boolean;
  onSelect?: (store: StoreLocation) => void;
  onGetDirections?: (store: StoreLocation) => void;
  className?: string;
}

export default function LocationCard({
  store,
  isSelected = false,
  onSelect,
  onGetDirections,
  className = ""
}: LocationCardProps) {
  const handleCardClick = () => {
    if (onSelect) {
      onSelect(store);
      console.log('Location card selected:', store.name);
    }
  };

  const handleDirectionsClick = (e: React.MouseEvent) => {
    e.stopPropagation();
    if (onGetDirections) {
      onGetDirections(store);
      console.log('Get directions clicked from card:', store.name);
    }
  };

  return (
    <Card 
      className={`cursor-pointer transition-all hover-elevate ${
        isSelected ? 'ring-2 ring-primary ring-offset-2' : ''
      } ${className}`}
      onClick={handleCardClick}
      data-testid={`card-location-${store.id}`}
    >
      <CardContent className="p-4 space-y-3">
        <div className="flex items-start justify-between gap-3">
          <div className="flex-1 min-w-0">
            <h3 className="font-semibold text-base truncate">{store.name}</h3>
            <div className="flex items-start gap-2 mt-1">
              <MapPin className="w-4 h-4 text-muted-foreground mt-0.5 flex-shrink-0" />
              <p className="text-sm text-muted-foreground leading-relaxed">
                {store.address}
              </p>
            </div>
          </div>
          
          {store.distance && (
            <Badge variant="secondary" className="flex-shrink-0">
              {store.distance.toFixed(1)} mi
            </Badge>
          )}
        </div>

        {(store.phone || store.hours) && (
          <div className="space-y-2">
            {store.phone && (
              <div className="flex items-center gap-2">
                <Phone className="w-4 h-4 text-muted-foreground" />
                <span className="text-sm">{store.phone}</span>
              </div>
            )}
            
            {store.hours && (
              <div className="flex items-center gap-2">
                <Clock className="w-4 h-4 text-muted-foreground" />
                <span className="text-sm">{store.hours}</span>
              </div>
            )}
          </div>
        )}

        <div className="flex gap-2 pt-2">
          <Button 
            size="sm" 
            className="flex-1"
            onClick={handleDirectionsClick}
            data-testid={`button-directions-card-${store.id}`}
          >
            <Navigation className="w-4 h-4 mr-2" />
            Directions
          </Button>
          
          <Button 
            size="sm" 
            variant="outline"
            onClick={(e) => {
              e.stopPropagation();
              console.log('View details clicked for:', store.name);
            }}
            data-testid={`button-details-${store.id}`}
          >
            <ExternalLink className="w-4 h-4" />
          </Button>
        </div>
      </CardContent>
    </Card>
  );
}
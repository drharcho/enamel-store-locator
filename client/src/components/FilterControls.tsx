import { Slider } from '@/components/ui/slider';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { SlidersHorizontal } from 'lucide-react';

interface FilterControlsProps {
  radius: number;
  onRadiusChange: (radius: number) => void;
  storeCount: number;
  className?: string;
}

export default function FilterControls({
  radius,
  onRadiusChange,
  storeCount,
  className = ""
}: FilterControlsProps) {
  const handleRadiusChange = (value: number[]) => {
    const newRadius = value[0];
    onRadiusChange(newRadius);
    console.log('Radius filter changed:', newRadius);
  };

  const getRadiusLabel = (radius: number) => {
    if (radius === 1) return '1 mile';
    return `${radius} miles`;
  };

  return (
    <Card className={className} data-testid="filter-controls">
      <CardHeader className="pb-4">
        <CardTitle className="text-base flex items-center gap-2">
          <SlidersHorizontal className="w-4 h-4" />
          Search Radius
        </CardTitle>
      </CardHeader>
      
      <CardContent className="space-y-4">
        <div className="space-y-3">
          <div className="flex items-center justify-between">
            <span className="text-sm text-muted-foreground">Distance</span>
            <Badge variant="secondary" data-testid="text-radius-value">
              {getRadiusLabel(radius)}
            </Badge>
          </div>
          
          <Slider
            value={[radius]}
            onValueChange={handleRadiusChange}
            max={50}
            min={1}
            step={1}
            className="w-full"
            data-testid="slider-radius"
          />
          
          <div className="flex justify-between text-xs text-muted-foreground">
            <span>1 mile</span>
            <span>50 miles</span>
          </div>
        </div>

        <div className="pt-2 border-t">
          <div className="flex items-center justify-between text-sm">
            <span className="text-muted-foreground">Results found</span>
            <Badge variant="outline" data-testid="text-store-count">
              {storeCount} {storeCount === 1 ? 'location' : 'locations'}
            </Badge>
          </div>
        </div>

        {/* Quick radius presets */}
        <div className="pt-2">
          <p className="text-xs text-muted-foreground mb-2">Quick select:</p>
          <div className="flex flex-wrap gap-2">
            {[5, 10, 25, 50].map((presetRadius) => (
              <Badge
                key={presetRadius}
                variant={radius === presetRadius ? "default" : "outline"}
                className="cursor-pointer hover-elevate"
                onClick={() => onRadiusChange(presetRadius)}
                data-testid={`badge-radius-${presetRadius}`}
              >
                {presetRadius} mi
              </Badge>
            ))}
          </div>
        </div>
      </CardContent>
    </Card>
  );
}
# Store Locator App Design Guidelines

## Design Approach: Reference-Based (Google Maps + Modern Web App)
Drawing inspiration from Google Maps interface patterns and modern location-based apps like Airbnb's map view and Yelp's business finder. This utility-focused application prioritizes functionality while maintaining a clean, professional aesthetic suitable for WordPress integration.

## Core Design Elements

### A. Color Palette
**Light Mode:**
- Primary: 220 100% 35% (Professional blue for interactive elements)
- Background: 0 0% 98% (Clean white-gray base)
- Surface: 0 0% 100% (Pure white cards/panels)
- Border: 220 15% 85% (Subtle gray borders)

**Dark Mode:**
- Primary: 220 100% 60% (Lighter blue for dark backgrounds)
- Background: 220 15% 8% (Deep dark gray)
- Surface: 220 15% 12% (Elevated dark surfaces)
- Border: 220 15% 20% (Visible dark borders)

### B. Typography
- **Primary Font**: Inter (Google Fonts) - Clean, readable for UI elements
- **Secondary Font**: System fonts for performance
- **Sizes**: Text-sm (14px) for body, text-lg (18px) for headings, text-xs (12px) for labels

### C. Layout System
**Spacing Units**: Tailwind units of 2, 4, 6, and 8 (p-4, m-6, gap-8, etc.)
- Consistent 16px (4 units) base spacing
- 24px (6 units) for component separation
- 32px (8 units) for major layout divisions

### D. Component Library

**Map Container**: Full-width/height with subtle shadow and rounded corners (rounded-lg)
**Search Panel**: Floating card overlay (top-left) with backdrop blur
**Filter Controls**: Horizontal pill-style buttons with radius slider
**Location Cards**: Clean white cards with subtle shadows, appearing on marker interaction
**Custom Map Markers**: Branded pin icons with store logos, hover state with scale transform
**Loading States**: Skeleton loaders for map and location data

## Interface Layout

**Primary Layout**: Split-screen approach
- Left sidebar (30% width): Search controls, filters, location list
- Right main area (70% width): Interactive Google Maps
- Mobile: Stacked layout with collapsible search panel

**Search Panel Components**:
- Location input field with geolocation button
- Radius/distance slider (1-50 miles range)
- Store category filters (if applicable)
- Results list with distance indicators

**Map Interactions**:
- Custom store markers with branded colors
- Info windows showing store details on click
- Zoom controls and map type toggles
- User location marker (distinct from stores)

## WordPress Integration Considerations

**Plugin Styling**: Inherits WordPress theme colors while maintaining functional contrast
**Responsive Breakpoints**: Mobile-first approach (sm: 640px, md: 768px, lg: 1024px)
**Accessibility**: WCAG 2.1 AA compliance with keyboard navigation and screen reader support

## Performance Optimizations

- Lazy loading for map tiles and markers
- Debounced search inputs
- Clustered markers for dense location areas
- Minimal animation (subtle hover states only)

## Key User Flows

1. **Initial Load**: Default location with configurable radius showing nearby stores
2. **Location Search**: Input address/zip → geocode → update map center and markers
3. **Geolocation**: Browser permission → current location → nearby results
4. **Store Selection**: Click marker → info popup → detailed store information

This design balances Google Maps familiarity with clean, modern web app aesthetics while ensuring seamless WordPress integration and optimal performance for location-based functionality.
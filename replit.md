# Enamel Dentistry Store Locator

## Overview

A store locator web application designed for Enamel Dentistry's multi-location dental practice. The application provides an interactive Google Maps-based interface for finding nearby clinic locations across Texas. Built as a standalone React application with plans for WordPress plugin integration, it enables users to search for dental clinics by location, view details about each office, and get directions.

The application emphasizes a clean, professional interface inspired by Google Maps and modern location-based services like Airbnb and Yelp, with customizable branding to match Enamel Dentistry's visual identity.

## User Preferences

Preferred communication style: Simple, everyday language.

## System Architecture

### Frontend Architecture

**Framework**: React 18+ with TypeScript, using Vite as the build tool and development server.

**UI Component System**: Shadcn/ui (Radix UI primitives) with Tailwind CSS for styling. The "New York" style variant is configured with custom typography (Montserrat and Rubik fonts) and a neutral color scheme that supports both light and dark modes.

**State Management**: TanStack Query (React Query) for server state management with minimal client-side state. The application uses custom hooks and local component state for UI interactions.

**Routing**: Wouter for lightweight client-side routing, though the current implementation is primarily a single-page application centered on the store locator interface.

**Key Design Decisions**:
- Split-screen layout (30% sidebar, 70% map) on desktop with stacked mobile layout
- Component-based architecture with reusable UI elements (LocationCard, SearchPanel, FilterControls, MapView)
- Responsive design with mobile-first considerations
- Theme system supporting light/dark modes with customizable color schemes

### Backend Architecture

**Server Framework**: Express.js with TypeScript running on Node.js.

**API Design**: RESTful API structure (routes prefixed with `/api`) though current implementation shows minimal route definitions, suggesting the backend is primarily set up for future CRUD operations.

**Storage Layer**: Abstract storage interface (`IStorage`) designed to support multiple data entities:
- Clinic locations with business hours, services, and metadata
- Plugin settings for WordPress integration
- Map markers for customization
- Color themes for branding
- User management (authentication ready)

**Build Process**: 
- Frontend: Vite bundler outputting to `dist/public`
- Backend: esbuild bundling server code to `dist` directory
- Separate development and production modes with different configurations

### Data Storage

**Database**: PostgreSQL via Neon serverless database with WebSocket connections.

**ORM**: Drizzle ORM for type-safe database operations with schema-first design.

**Schema Design**:
- `clinicLocations`: Core table storing location data with lat/lng coordinates, address information, business hours (JSON), services array (JSON), and metadata (JSON) for flexible extensibility
- `users`: Authentication/authorization support
- `pluginSettings`: Key-value store for WordPress plugin configuration
- `mapMarkers`: Custom marker styling and configuration
- `colorThemes`: Brand customization with active theme selection

**Key Decisions**:
- JSON columns for flexible nested data (business hours, services, metadata) avoiding over-normalization
- UUID primary keys for distributed system compatibility
- Timestamp tracking (createdAt, updatedAt) for audit trails
- Boolean flags for soft features (isActive on locations)

### External Dependencies

**Google Maps Platform**:
- `@react-google-maps/api`: React wrapper for Google Maps JavaScript API
- `@googlemaps/js-api-loader`: Async loader for Maps API
- API key configured via `VITE_GOOGLE_MAPS_API_KEY` environment variable
- Used for interactive map display, geocoding, marker rendering, and directions

**Neon Database**:
- `@neondatabase/serverless`: Serverless PostgreSQL client with WebSocket support
- Connection string via `DATABASE_URL` environment variable
- Supports connection pooling and auto-scaling

**Session Management**:
- `connect-pg-simple`: PostgreSQL-backed session store for Express
- Enables persistent user sessions across server restarts

**WordPress Integration** (Planned):
- Custom plugin structure in `wordpress-plugin/` directory
- Shortcode-based embedding (`[enamel_store_locator]`)
- Admin interface for location management, settings, and customization
- REST API endpoints for WordPress-to-React communication

**Development Tools**:
- `tsx`: TypeScript execution for development server
- `@replit/vite-plugin-runtime-error-modal`: Enhanced error reporting in Replit environment
- `@replit/vite-plugin-cartographer`: Code mapping for Replit development

**Font Loading**:
- Google Fonts API for Montserrat and Rubik typefaces
- Preconnect optimization for faster font delivery

**Key Architectural Patterns**:
- Environment-based configuration (NODE_ENV, REPL_ID checks)
- Middleware-based request logging with response capture
- Error boundary handling with status code normalization
- Alias-based imports (`@/`, `@shared/`, `@assets/`) for cleaner module resolution
import { sql } from "drizzle-orm";
import { pgTable, text, varchar, real, boolean, json, timestamp, integer } from "drizzle-orm/pg-core";
import { createInsertSchema } from "drizzle-zod";
import { z } from "zod";

export const users = pgTable("users", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  username: text("username").notNull().unique(),
  password: text("password").notNull(),
});

// Enhanced clinic locations table for WordPress plugin
export const clinicLocations = pgTable("clinic_locations", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  name: text("name").notNull(),
  address: text("address").notNull(),
  city: varchar("city", { length: 100 }).notNull(),
  state: varchar("state", { length: 10 }).notNull(),
  zipCode: varchar("zip_code", { length: 10 }).notNull(),
  phone: varchar("phone", { length: 20 }),
  lat: real("lat").notNull(),
  lng: real("lng").notNull(),
  isActive: boolean("is_active").default(true),
  
  // Business Hours (stored as JSON)
  businessHours: json("business_hours").$type<{
    monday?: { open: string; close: string; closed?: boolean };
    tuesday?: { open: string; close: string; closed?: boolean };
    wednesday?: { open: string; close: string; closed?: boolean };
    thursday?: { open: string; close: string; closed?: boolean };
    friday?: { open: string; close: string; closed?: boolean };
    saturday?: { open: string; close: string; closed?: boolean };
    sunday?: { open: string; close: string; closed?: boolean };
  }>(),
  
  // Services offered at this location
  services: json("services").$type<string[]>().default([]),
  
  // Additional location metadata
  metadata: json("metadata").$type<{
    website?: string;
    email?: string;
    parking?: string;
    accessibility?: string[];
    specialNotes?: string;
  }>(),
  
  createdAt: timestamp("created_at").defaultNow(),
  updatedAt: timestamp("updated_at").defaultNow()
});

// Keep legacy stores table for backward compatibility
export const stores = pgTable("stores", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  name: text("name").notNull(),
  address: text("address").notNull(),
  lat: real("lat").notNull(),
  lng: real("lng").notNull(),
  phone: text("phone"),
  hours: text("hours"),
});

// Plugin Settings Table - stores all customizable text and appearance settings
export const pluginSettings = pgTable("plugin_settings", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  settingKey: varchar("setting_key", { length: 100 }).notNull().unique(),
  settingValue: json("setting_value"),
  settingType: varchar("setting_type", { length: 50 }).notNull(), // 'text', 'color', 'boolean', 'number', 'json'
  category: varchar("category", { length: 50 }).notNull(), // 'content', 'colors', 'map', 'markers', 'behavior'
  description: text("description"),
  updatedAt: timestamp("updated_at").defaultNow()
});

// Map Markers Table - stores custom marker configurations
export const mapMarkers = pgTable("map_markers", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  name: varchar("name", { length: 100 }).notNull(),
  type: varchar("type", { length: 50 }).notNull(), // 'default', 'custom_icon', 'font_awesome', 'text'
  iconData: json("icon_data").$type<{
    // For custom icons
    iconUrl?: string;
    iconSize?: { width: number; height: number };
    iconAnchor?: { x: number; y: number };
    
    // For font awesome icons
    fontAwesome?: {
      icon: string;
      style: 'solid' | 'regular' | 'light';
      color: string;
      size: string;
    };
    
    // For text markers
    text?: {
      content: string;
      font: string;
      backgroundColor: string;
      textColor: string;
      shape: 'circle' | 'square';
    };
    
    // Common properties
    color?: string;
    borderColor?: string;
    borderWidth?: number;
    shadow?: boolean;
  }>(),
  isDefault: boolean("is_default").default(false),
  isActive: boolean("is_active").default(true),
  createdAt: timestamp("created_at").defaultNow()
});

// Color Themes Table - predefined color schemes
export const colorThemes = pgTable("color_themes", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  name: varchar("name", { length: 100 }).notNull(),
  description: text("description"),
  colors: json("colors").$type<{
    primary: string;
    accent: string;
    background: string;
    cardBackground: string;
    mutedBackground: string;
    primaryText: string;
    secondaryText: string;
    linkText: string;
    borderColor: string;
  }>().notNull(),
  isDefault: boolean("is_default").default(false),
  isActive: boolean("is_active").default(true),
  createdAt: timestamp("created_at").defaultNow()
});

// Export insert schemas
export const insertUserSchema = createInsertSchema(users).pick({
  username: true,
  password: true,
});

export const insertStoreSchema = createInsertSchema(stores).omit({
  id: true,
});

export const insertClinicLocationSchema = createInsertSchema(clinicLocations).omit({
  id: true,
  createdAt: true,
  updatedAt: true
});

export const insertPluginSettingSchema = createInsertSchema(pluginSettings).omit({
  id: true,
  updatedAt: true
});

export const insertMapMarkerSchema = createInsertSchema(mapMarkers).omit({
  id: true,
  createdAt: true
});

export const insertColorThemeSchema = createInsertSchema(colorThemes).omit({
  id: true,
  createdAt: true
});

// Export types
export type User = typeof users.$inferSelect;
export type InsertUser = z.infer<typeof insertUserSchema>;

export type Store = typeof stores.$inferSelect;
export type InsertStore = z.infer<typeof insertStoreSchema>;

export type ClinicLocation = typeof clinicLocations.$inferSelect;
export type InsertClinicLocation = z.infer<typeof insertClinicLocationSchema>;

export type PluginSetting = typeof pluginSettings.$inferSelect;
export type InsertPluginSetting = z.infer<typeof insertPluginSettingSchema>;

export type MapMarker = typeof mapMarkers.$inferSelect;
export type InsertMapMarker = z.infer<typeof insertMapMarkerSchema>;

export type ColorTheme = typeof colorThemes.$inferSelect;
export type InsertColorTheme = z.infer<typeof insertColorThemeSchema>;

// Default settings configuration
export const defaultPluginSettings = {
  // Content settings
  'header_main_title': 'Find Your Nearest Location',
  'header_subtitle': 'Quality dental care across Texas with convenient locations',
  'search_section_title': 'Find Nearest Location',
  'search_input_placeholder': 'Enter address or zip code',
  'search_button_text': 'Search',
  'location_button_text': 'Use My Location',
  'footer_text': 'Established in 2016 • Quality dental care using the latest technology',
  'directions_button_text': 'Get Directions',
  'call_button_text': 'Call',
  'phone_label': 'Phone:',
  'hours_label': 'Hours:',
  'getting_location_text': 'Getting location...',
  'loading_map_text': 'Loading map...',
  'closed_text': 'Closed',
  
  // Error messages
  'location_not_found': 'Sorry, we couldn\'t find that location',
  'gps_failed': 'Unable to get your current location. Please try again',
  'location_found': 'Found nearby locations!',
  'settings_saved': 'Settings updated successfully',
  
  // Color scheme (Enamel Dentistry brand)
  'primary_color': '#7D55C7',
  'accent_color': '#E56B10',
  'background_color': '#FFFFFF',
  'card_background': '#F8F9FA',
  'muted_background': '#EDE9FF',
  'primary_text': '#231942',
  'secondary_text': '#6B7280',
  'link_text': '#7D55C7',
  'border_color': '#E5E7EB',
  
  // Map settings
  'default_map_type': 'roadmap',
  'default_zoom_level': 10,
  'default_lat': 30.3072,
  'default_lng': -97.7560,
  'enable_clustering': true,
  'cluster_distance': 50,
  'min_cluster_size': 2,
  'show_zoom_controls': true,
  'show_fullscreen': true,
  'enable_street_view': false,
  'enable_map_type_selector': false,
  'map_style_theme': 'default',
  
  // Marker settings
  'default_marker_type': 'default',
  'marker_color': '#7D55C7',
  'marker_size': 32,
  'marker_animation': true,
  'show_info_window': true,
  'info_window_max_width': 300,
  
  // User experience
  'load_on_page_load': true,
  'results_limit': 25,
  'distance_unit': 'miles',
  'default_radius': 25,
  'enable_phone_click': true,
  'enable_directions': true
} as const;
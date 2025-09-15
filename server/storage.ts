import { 
  type User, type InsertUser,
  type ClinicLocation, type InsertClinicLocation,
  type PluginSetting, type InsertPluginSetting,
  type MapMarker, type InsertMapMarker,
  type ColorTheme, type InsertColorTheme
} from "@shared/schema";
import { randomUUID } from "crypto";

// Enhanced storage interface for WordPress plugin
export interface IStorage {
  // User methods (existing)
  getUser(id: string): Promise<User | undefined>;
  getUserByUsername(username: string): Promise<User | undefined>;
  createUser(user: InsertUser): Promise<User>;

  // Clinic Location methods
  getClinicLocations(): Promise<ClinicLocation[]>;
  getClinicLocation(id: string): Promise<ClinicLocation | undefined>;
  createClinicLocation(location: InsertClinicLocation): Promise<ClinicLocation>;
  updateClinicLocation(id: string, location: Partial<InsertClinicLocation>): Promise<ClinicLocation | undefined>;
  deleteClinicLocation(id: string): Promise<boolean>;
  
  // Plugin Settings methods
  getPluginSettings(): Promise<PluginSetting[]>;
  getPluginSetting(key: string): Promise<PluginSetting | undefined>;
  upsertPluginSetting(setting: InsertPluginSetting): Promise<PluginSetting>;
  deletePluginSetting(key: string): Promise<boolean>;

  // Map Marker methods
  getMapMarkers(): Promise<MapMarker[]>;
  getMapMarker(id: string): Promise<MapMarker | undefined>;
  createMapMarker(marker: InsertMapMarker): Promise<MapMarker>;
  updateMapMarker(id: string, marker: Partial<InsertMapMarker>): Promise<MapMarker | undefined>;
  deleteMapMarker(id: string): Promise<boolean>;

  // Color Theme methods
  getColorThemes(): Promise<ColorTheme[]>;
  getActiveColorTheme(): Promise<ColorTheme | undefined>;
  createColorTheme(theme: InsertColorTheme): Promise<ColorTheme>;
  setActiveColorTheme(id: string): Promise<boolean>;
}

// Database storage implementation
import { users, clinicLocations, pluginSettings, mapMarkers, colorThemes } from "@shared/schema";
import { db } from "./db";
import { eq } from "drizzle-orm";

export class DatabaseStorage implements IStorage {
  // User methods (existing)
  async getUser(id: string): Promise<User | undefined> {
    const [user] = await db.select().from(users).where(eq(users.id, id));
    return user || undefined;
  }

  async getUserByUsername(username: string): Promise<User | undefined> {
    const [user] = await db.select().from(users).where(eq(users.username, username));
    return user || undefined;
  }

  async createUser(insertUser: InsertUser): Promise<User> {
    const [user] = await db
      .insert(users)
      .values(insertUser)
      .returning();
    return user;
  }

  // Clinic Location methods
  async getClinicLocations(): Promise<ClinicLocation[]> {
    const locations = await db
      .select()
      .from(clinicLocations)
      .where(eq(clinicLocations.isActive, true));
    return locations;
  }

  async getClinicLocation(id: string): Promise<ClinicLocation | undefined> {
    const [location] = await db
      .select()
      .from(clinicLocations)
      .where(eq(clinicLocations.id, id));
    return location || undefined;
  }

  async createClinicLocation(location: InsertClinicLocation): Promise<ClinicLocation> {
    const [newLocation] = await db
      .insert(clinicLocations)
      .values([location])
      .returning();
    return newLocation;
  }

  async updateClinicLocation(id: string, location: Partial<InsertClinicLocation>): Promise<ClinicLocation | undefined> {
    const [updatedLocation] = await db
      .update(clinicLocations)
      .set({ ...location, updatedAt: new Date() })
      .where(eq(clinicLocations.id, id))
      .returning();
    return updatedLocation || undefined;
  }

  async deleteClinicLocation(id: string): Promise<boolean> {
    const result = await db
      .update(clinicLocations)
      .set({ isActive: false })
      .where(eq(clinicLocations.id, id));
    return (result.rowCount || 0) > 0;
  }

  // Plugin Settings methods
  async getPluginSettings(): Promise<PluginSetting[]> {
    const settings = await db.select().from(pluginSettings);
    return settings;
  }

  async getPluginSetting(key: string): Promise<PluginSetting | undefined> {
    const [setting] = await db
      .select()
      .from(pluginSettings)
      .where(eq(pluginSettings.settingKey, key));
    return setting || undefined;
  }

  async upsertPluginSetting(setting: InsertPluginSetting): Promise<PluginSetting> {
    const [upsertedSetting] = await db
      .insert(pluginSettings)
      .values(setting)
      .onConflictDoUpdate({
        target: pluginSettings.settingKey,
        set: {
          settingValue: setting.settingValue,
          updatedAt: new Date()
        }
      })
      .returning();
    return upsertedSetting;
  }

  async deletePluginSetting(key: string): Promise<boolean> {
    const result = await db
      .delete(pluginSettings)
      .where(eq(pluginSettings.settingKey, key));
    return (result.rowCount || 0) > 0;
  }

  // Map Marker methods
  async getMapMarkers(): Promise<MapMarker[]> {
    const markers = await db
      .select()
      .from(mapMarkers)
      .where(eq(mapMarkers.isActive, true));
    return markers;
  }

  async getMapMarker(id: string): Promise<MapMarker | undefined> {
    const [marker] = await db
      .select()
      .from(mapMarkers)
      .where(eq(mapMarkers.id, id));
    return marker || undefined;
  }

  async createMapMarker(marker: InsertMapMarker): Promise<MapMarker> {
    const [newMarker] = await db
      .insert(mapMarkers)
      .values([marker])
      .returning();
    return newMarker;
  }

  async updateMapMarker(id: string, marker: Partial<InsertMapMarker>): Promise<MapMarker | undefined> {
    const [updatedMarker] = await db
      .update(mapMarkers)
      .set(marker)
      .where(eq(mapMarkers.id, id))
      .returning();
    return updatedMarker || undefined;
  }

  async deleteMapMarker(id: string): Promise<boolean> {
    const result = await db
      .update(mapMarkers)
      .set({ isActive: false })
      .where(eq(mapMarkers.id, id));
    return (result.rowCount || 0) > 0;
  }

  // Color Theme methods
  async getColorThemes(): Promise<ColorTheme[]> {
    const themes = await db
      .select()
      .from(colorThemes)
      .where(eq(colorThemes.isActive, true));
    return themes;
  }

  async getActiveColorTheme(): Promise<ColorTheme | undefined> {
    const [theme] = await db
      .select()
      .from(colorThemes)
      .where(eq(colorThemes.isDefault, true));
    return theme || undefined;
  }

  async createColorTheme(theme: InsertColorTheme): Promise<ColorTheme> {
    // If this is being set as default, unset all other defaults first
    if (theme.isDefault) {
      await db
        .update(colorThemes)
        .set({ isDefault: false })
        .where(eq(colorThemes.isDefault, true));
    }

    const [newTheme] = await db
      .insert(colorThemes)
      .values(theme)
      .returning();
    return newTheme;
  }

  async setActiveColorTheme(id: string): Promise<boolean> {
    // Unset all current defaults
    await db
      .update(colorThemes)
      .set({ isDefault: false })
      .where(eq(colorThemes.isDefault, true));

    // Set the new default
    const result = await db
      .update(colorThemes)
      .set({ isDefault: true })
      .where(eq(colorThemes.id, id));

    return (result.rowCount || 0) > 0;
  }
}

export const storage = new DatabaseStorage();

import { useState, useEffect, useCallback } from 'react';
import { supabase } from '@/integrations/supabase/client';

export interface Property {
  id: string;
  title: string;
  price: number;
  image: string;
  area: number;
  rooms: number;
  floor: number;
  address: string;
  type: string;
}

const FAVORITES_STORAGE_KEY = 'livegrid_favorites';
const FAVORITES_STORAGE_KEY_USER = 'livegrid_favorites_user';

export const useFavorites = () => {
  const [favorites, setFavorites] = useState<Property[]>([]);
  const [userId, setUserId] = useState<string | null>(null);

  // Load favorites from localStorage on mount
  useEffect(() => {
    const stored = localStorage.getItem(FAVORITES_STORAGE_KEY);
    if (stored) {
      try {
        setFavorites(JSON.parse(stored));
      } catch (error) {
        console.error('Error parsing favorites from localStorage:', error);
      }
    }
  }, []);

  // Listen for auth state changes to sync favorites
  useEffect(() => {
    if (!supabase) return;

    const { data: { subscription } } = supabase.auth.onAuthStateChange(async (event, session) => {
      const currentUserId = session?.user?.id || null;
      setUserId(currentUserId);

      if (event === 'SIGNED_IN' && currentUserId) {
        // User logged in - sync favorites
        await syncFavoritesToServer(currentUserId);
      } else if (event === 'SIGNED_OUT') {
        // User logged out - clear user-specific favorites
        const localFavorites = getLocalFavorites();
        setFavorites(localFavorites);
      }
    });

    // Check initial session
    supabase.auth.getSession().then(({ data: { session } }) => {
      const currentUserId = session?.user?.id || null;
      setUserId(currentUserId);
      if (currentUserId) {
        syncFavoritesToServer(currentUserId);
      }
    });

    return () => subscription.unsubscribe();
  }, []);

  const getLocalFavorites = (): Property[] => {
    const stored = localStorage.getItem(FAVORITES_STORAGE_KEY);
    if (stored) {
      try {
        return JSON.parse(stored);
      } catch (error) {
        console.error('Error parsing favorites:', error);
      }
    }
    return [];
  };

  const saveToLocalStorage = (favoritesList: Property[]) => {
    localStorage.setItem(FAVORITES_STORAGE_KEY, JSON.stringify(favoritesList));
  };

  const syncFavoritesToServer = async (userId: string) => {
    if (!supabase) return;

    try {
      // Get local favorites
      const localFavorites = getLocalFavorites();

      // Try to get server favorites
      const { data: serverData, error: fetchError } = await supabase
        .from('user_favorites')
        .select('favorites')
        .eq('user_id', userId)
        .maybeSingle();

      if (fetchError && fetchError.code !== 'PGRST116') {
        console.error('Error fetching favorites from server:', fetchError);
      }

      let serverFavorites: Property[] = [];
      if (serverData?.favorites) {
        try {
          serverFavorites = typeof serverData.favorites === 'string' 
            ? JSON.parse(serverData.favorites) 
            : serverData.favorites;
        } catch (error) {
          console.error('Error parsing server favorites:', error);
        }
      }

      // Merge: server favorites take priority, then add local ones that aren't on server
      const mergedFavorites = [...serverFavorites];
      localFavorites.forEach(local => {
        if (!mergedFavorites.some(f => f.id === local.id)) {
          mergedFavorites.push(local);
        }
      });

      // Update state and localStorage
      setFavorites(mergedFavorites);
      saveToLocalStorage(mergedFavorites);

      // Save to server
      const { error: saveError } = await supabase
        .from('user_favorites')
        .upsert({
          user_id: userId,
          favorites: JSON.stringify(mergedFavorites),
          updated_at: new Date().toISOString(),
        }, {
          onConflict: 'user_id'
        });

      if (saveError) {
        console.error('Error saving favorites to server:', saveError);
      }
    } catch (error) {
      console.error('Error syncing favorites:', error);
    }
  };

  const addToFavorites = useCallback(async (property: Property) => {
    const updated = [...favorites, property];
    setFavorites(updated);
    saveToLocalStorage(updated);

    // Sync to server if user is logged in
    if (userId && supabase) {
      try {
        await supabase
          .from('user_favorites')
          .upsert({
            user_id: userId,
            favorites: JSON.stringify(updated),
            updated_at: new Date().toISOString(),
          }, {
            onConflict: 'user_id'
          });
      } catch (error) {
        console.error('Error saving favorite to server:', error);
      }
    }
  }, [favorites, userId]);

  const removeFromFavorites = useCallback(async (id: string) => {
    const updated = favorites.filter(p => p.id !== id);
    setFavorites(updated);
    saveToLocalStorage(updated);

    // Sync to server if user is logged in
    if (userId && supabase) {
      try {
        await supabase
          .from('user_favorites')
          .upsert({
            user_id: userId,
            favorites: JSON.stringify(updated),
            updated_at: new Date().toISOString(),
          }, {
            onConflict: 'user_id'
          });
      } catch (error) {
        console.error('Error removing favorite from server:', error);
      }
    }
  }, [favorites, userId]);

  const toggleFavorite = useCallback(async (id: string, property?: Property) => {
    const isCurrentlyFavorite = favorites.some(p => p.id === id);
    if (isCurrentlyFavorite) {
      await removeFromFavorites(id);
    } else {
      if (property) {
        await addToFavorites(property);
      }
    }
  }, [favorites, addToFavorites, removeFromFavorites]);

  const isFavorite = useCallback((id: string) => {
    return favorites.some(p => p.id === id);
  }, [favorites]);

  const clearFavorites = useCallback(async () => {
    setFavorites([]);
    localStorage.removeItem(FAVORITES_STORAGE_KEY);

    // Clear from server if user is logged in
    if (userId && supabase) {
      try {
        await supabase
          .from('user_favorites')
          .upsert({
            user_id: userId,
            favorites: JSON.stringify([]),
            updated_at: new Date().toISOString(),
          }, {
            onConflict: 'user_id'
          });
      } catch (error) {
        console.error('Error clearing favorites from server:', error);
      }
    }
  }, [userId]);

  return { 
    favorites, 
    addToFavorites, 
    removeFromFavorites, 
    toggleFavorite,
    isFavorite, 
    clearFavorites 
  };
};

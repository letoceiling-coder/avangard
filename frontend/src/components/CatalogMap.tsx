import { useEffect, useRef, useState } from "react";
import { MapPin, ZoomIn, ZoomOut, Maximize2, X } from "lucide-react";
import { Button } from "@/components/ui/button";

interface Property {
  id: string;
  title: string;
  price: number;
  address: string;
  coordinates?: [number, number];
}

interface CatalogMapProps {
  properties: Property[];
  onPropertyClick?: (id: string) => void;
  hoveredPropertyId?: string | null;
  className?: string;
}

declare global {
  interface Window {
    ymaps: any;
  }
}

const CatalogMap = ({ 
  properties, 
  onPropertyClick, 
  hoveredPropertyId,
  className = "" 
}: CatalogMapProps) => {
  const mapContainerRef = useRef<HTMLDivElement>(null);
  const mapRef = useRef<any>(null);
  const placemarkRefs = useRef<Map<string, any>>(new Map());
  const [isLoading, setIsLoading] = useState(true);
  const [isFullscreen, setIsFullscreen] = useState(false);

  // Generate random coordinates near Belgorod for demo
  const getPropertyCoordinates = (id: string): [number, number] => {
    const baseCoords: [number, number] = [50.5997, 36.5873];
    const hash = id.split("").reduce((a, b) => a + b.charCodeAt(0), 0);
    return [
      baseCoords[0] + (hash % 10 - 5) * 0.01,
      baseCoords[1] + ((hash * 2) % 10 - 5) * 0.015,
    ];
  };

  useEffect(() => {
    const loadYandexMaps = () => {
      return new Promise<void>((resolve, reject) => {
        if (window.ymaps) {
          resolve();
          return;
        }
        const script = document.createElement("script");
        script.src = "https://api-maps.yandex.ru/2.1/?apikey=&lang=ru_RU";
        script.async = true;
        script.onload = () => window.ymaps.ready(() => resolve());
        script.onerror = () => reject(new Error("Failed to load Yandex Maps"));
        document.head.appendChild(script);
      });
    };

    const initMap = async () => {
      try {
        await loadYandexMaps();
        if (!mapContainerRef.current || mapRef.current) return;

        const map = new window.ymaps.Map(mapContainerRef.current, {
          center: [50.5997, 36.5873],
          zoom: 12,
          controls: [],
        });

        // Add custom controls
        map.controls.add("zoomControl", { position: { right: 10, top: 10 } });

        // Add placemarks for each property
        properties.forEach((property) => {
          const coords = property.coordinates || getPropertyCoordinates(property.id);
          
          const placemark = new window.ymaps.Placemark(
            coords,
            {
              balloonContentHeader: property.title,
              balloonContentBody: `<div style="font-size: 14px; font-weight: 600; color: #4AB3FF;">${property.price.toLocaleString("ru-RU")} ₽</div><div style="font-size: 12px; color: #666; margin-top: 4px;">${property.address}</div>`,
              hintContent: `${(property.price / 1000000).toFixed(1)} млн ₽`,
            },
            {
              preset: "islands#blueCircleDotIcon",
              iconColor: "#4AB3FF",
            }
          );

          placemark.events.add("click", () => {
            onPropertyClick?.(property.id);
          });

          map.geoObjects.add(placemark);
          placemarkRefs.current.set(property.id, placemark);
        });

        map.behaviors.disable("scrollZoom");
        mapRef.current = map;
        setIsLoading(false);
      } catch (err) {
        console.error("Map error:", err);
        setIsLoading(false);
      }
    };

    initMap();

    return () => {
      if (mapRef.current) {
        mapRef.current.destroy();
        mapRef.current = null;
        placemarkRefs.current.clear();
      }
    };
  }, [properties, onPropertyClick]);

  // Highlight hovered property
  useEffect(() => {
    if (!mapRef.current) return;
    
    placemarkRefs.current.forEach((placemark, id) => {
      const isHovered = id === hoveredPropertyId;
      placemark.options.set("iconColor", isHovered ? "#10B981" : "#4AB3FF");
      if (isHovered) {
        placemark.options.set("preset", "islands#greenCircleDotIconWithCaption");
      } else {
        placemark.options.set("preset", "islands#blueCircleDotIcon");
      }
    });
  }, [hoveredPropertyId]);

  const handleZoomIn = () => {
    if (mapRef.current) {
      mapRef.current.setZoom(mapRef.current.getZoom() + 1);
    }
  };

  const handleZoomOut = () => {
    if (mapRef.current) {
      mapRef.current.setZoom(mapRef.current.getZoom() - 1);
    }
  };

  const toggleFullscreen = () => {
    setIsFullscreen(!isFullscreen);
    setTimeout(() => {
      if (mapRef.current) {
        mapRef.current.container.fitToViewport();
      }
    }, 100);
  };

  return (
    <div className={`relative ${isFullscreen ? "fixed inset-0 z-50" : className}`}>
      {isLoading && (
        <div className="absolute inset-0 flex items-center justify-center bg-muted z-10 rounded-xl">
          <div className="flex flex-col items-center gap-3">
            <div className="w-8 h-8 border-2 border-primary border-t-transparent rounded-full animate-spin" />
            <p className="text-sm text-muted-foreground">Загрузка карты...</p>
          </div>
        </div>
      )}
      
      <div ref={mapContainerRef} className="w-full h-full rounded-xl" />
      
      {/* Custom Controls */}
      <div className="absolute top-4 right-4 flex flex-col gap-2 z-10">
        <Button
          variant="outline"
          size="icon"
          className="bg-card shadow-elevated rounded-lg"
          onClick={handleZoomIn}
        >
          <ZoomIn className="w-4 h-4" />
        </Button>
        <Button
          variant="outline"
          size="icon"
          className="bg-card shadow-elevated rounded-lg"
          onClick={handleZoomOut}
        >
          <ZoomOut className="w-4 h-4" />
        </Button>
        <Button
          variant="outline"
          size="icon"
          className="bg-card shadow-elevated rounded-lg"
          onClick={toggleFullscreen}
        >
          {isFullscreen ? <X className="w-4 h-4" /> : <Maximize2 className="w-4 h-4" />}
        </Button>
      </div>

      {/* Property Count Badge */}
      <div className="absolute bottom-4 left-4 bg-card px-3 py-2 rounded-lg shadow-elevated text-sm font-medium z-10">
        <MapPin className="w-4 h-4 inline mr-1.5 text-primary" />
        {properties.length} объектов
      </div>
    </div>
  );
};

export default CatalogMap;

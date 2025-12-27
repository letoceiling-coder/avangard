import { useEffect, useRef, useState } from "react";

interface YandexMapProps {
  address: string;
  coordinates?: [number, number];
  zoom?: number;
  className?: string;
}

declare global {
  interface Window {
    ymaps: any;
  }
}

const YandexMap = ({ 
  address, 
  coordinates = [50.5997, 36.5873], // Белгород по умолчанию
  zoom = 16,
  className = ""
}: YandexMapProps) => {
  const mapContainerRef = useRef<HTMLDivElement>(null);
  const mapRef = useRef<any>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

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
        script.onload = () => {
          window.ymaps.ready(() => resolve());
        };
        script.onerror = () => reject(new Error("Failed to load Yandex Maps"));
        document.head.appendChild(script);
      });
    };

    const initMap = async () => {
      try {
        await loadYandexMaps();
        
        if (!mapContainerRef.current || mapRef.current) return;

        const map = new window.ymaps.Map(mapContainerRef.current, {
          center: coordinates,
          zoom: zoom,
          controls: ["zoomControl", "fullscreenControl"],
        });

        // Add placemark
        const placemark = new window.ymaps.Placemark(
          coordinates,
          {
            balloonContentHeader: "Расположение объекта",
            balloonContentBody: address,
            hintContent: address,
          },
          {
            preset: "islands#blueCircleDotIcon",
            iconColor: "#4AB3FF",
          }
        );

        map.geoObjects.add(placemark);
        map.behaviors.disable("scrollZoom");
        
        mapRef.current = map;
        setIsLoading(false);
      } catch (err) {
        setError("Не удалось загрузить карту");
        setIsLoading(false);
      }
    };

    initMap();

    return () => {
      if (mapRef.current) {
        mapRef.current.destroy();
        mapRef.current = null;
      }
    };
  }, [coordinates, zoom, address]);

  if (error) {
    return (
      <div className={`flex items-center justify-center bg-muted rounded-xl ${className}`}>
        <p className="text-muted-foreground">{error}</p>
      </div>
    );
  }

  return (
    <div className={`relative rounded-xl overflow-hidden ${className}`}>
      {isLoading && (
        <div className="absolute inset-0 flex items-center justify-center bg-muted z-10">
          <div className="flex flex-col items-center gap-3">
            <div className="w-8 h-8 border-2 border-primary border-t-transparent rounded-full animate-spin" />
            <p className="text-sm text-muted-foreground">Загрузка карты...</p>
          </div>
        </div>
      )}
      <div ref={mapContainerRef} className="w-full h-full" />
    </div>
  );
};

export default YandexMap;

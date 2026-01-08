import { useEffect, useRef, useState } from "react";
import { ExternalLink } from "lucide-react";
import { cn } from "@/lib/utils";

interface PropertyMapProps {
  latitude: number;
  longitude: number;
  address: string;
  city: string;
  className?: string;
}

declare global {
  interface Window {
    ymaps: any;
  }
}

const PropertyMap = ({
  latitude,
  longitude,
  address,
  city,
  className,
}: PropertyMapProps) => {
  const mapContainerRef = useRef<HTMLDivElement>(null);
  const observerRef = useRef<IntersectionObserver | null>(null);
  const mapRef = useRef<any>(null);
  const [isVisible, setIsVisible] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [isLoaded, setIsLoaded] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Intersection Observer для lazy loading
  useEffect(() => {
    if (!mapContainerRef.current) return;

    observerRef.current = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting && !isVisible) {
            setIsVisible(true);
            observerRef.current?.disconnect();
          }
        });
      },
      {
        root: null,
        rootMargin: "50px", // Загружать за 50px до появления в viewport
        threshold: 0.1,
      }
    );

    observerRef.current.observe(mapContainerRef.current);

    return () => {
      observerRef.current?.disconnect();
    };
  }, [isVisible]);

  // Загрузка и инициализация карты при видимости
  useEffect(() => {
    if (!isVisible || isLoaded || mapRef.current) return;

    const loadYandexMaps = () => {
      return new Promise<void>((resolve, reject) => {
        if (window.ymaps) {
          resolve();
          return;
        }

        setIsLoading(true);

        const script = document.createElement("script");
        script.src = "https://api-maps.yandex.ru/2.1/?apikey=&lang=ru_RU";
        script.async = true;
        script.onload = () => {
          window.ymaps.ready(() => {
            setIsLoading(false);
            resolve();
          });
        };
        script.onerror = () => {
          setIsLoading(false);
          reject(new Error("Failed to load Yandex Maps"));
        };
        document.head.appendChild(script);
      });
    };

    const initMap = async () => {
      try {
        await loadYandexMaps();

        if (!mapContainerRef.current || mapRef.current) return;

        // Определяем, мобильный ли это устройство
        const isMobile = window.innerWidth < 768;

        const map = new window.ymaps.Map(mapContainerRef.current, {
          center: [longitude, latitude], // Yandex Maps использует [longitude, latitude]
          zoom: 14,
          controls: isMobile ? [] : ["zoomControl"],
        });

        // Кастомный маркер (pin icon) - используем синий цвет #2563EB
        const pinSvg = `
          <svg width="24" height="32" viewBox="0 0 24 32" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 0C5.37258 0 0 5.37258 0 12C0 18.6274 5.37258 24 12 24C18.6274 24 24 18.6274 24 12C24 5.37258 18.6274 0 12 0ZM12 16C9.79086 16 8 14.2091 8 12C8 9.79086 9.79086 8 12 8C14.2091 8 16 9.79086 16 12C16 14.2091 14.2091 16 12 16Z" fill="#2563EB"/>
            <path d="M12 32C12 32 4 22 4 12C4 6.47715 7.47715 4 12 4C16.5229 4 20 6.47715 20 12C20 22 12 32 12 32Z" fill="#2563EB" opacity="0.3"/>
          </svg>
        `;

        // Создаем data URL для SVG
        const pinDataUrl = `data:image/svg+xml;charset=utf-8,${encodeURIComponent(pinSvg)}`;

        const customIcon = new window.ymaps.Placemark(
          [longitude, latitude],
          {
            balloonContentHeader: address,
            balloonContentBody: `${city}`,
            hintContent: address,
          },
          {
            iconLayout: "default#imageWithContent",
            iconImageHref: pinDataUrl,
            iconImageSize: [24, 32],
            iconImageOffset: [-12, -32],
          }
        );

        map.geoObjects.add(customIcon);

        // Отключаем zoom на mobile, разрешаем на desktop
        if (isMobile) {
          map.behaviors.disable(["scrollZoom", "dblClickZoom", "drag"]);
        } else {
          map.behaviors.disable("scrollZoom");
        }

        mapRef.current = map;
        setIsLoaded(true);
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
  }, [isVisible, latitude, longitude, address, city, isLoaded]);

  const handleOpenInMaps = () => {
    // Формируем URL для Yandex Maps
    const url = `https://yandex.ru/maps/?ll=${longitude},${latitude}&z=14&pt=${longitude},${latitude}`;
    window.open(url, "_blank", "noopener,noreferrer");
  };

  return (
    <div
      className={cn(
        "px-4 py-4",
        "md:px-6 md:py-4",
        className
      )}
      style={{
        backgroundColor: "#FFFFFF",
      }}
    >
      {/* Title */}
      <h3
        style={{
          fontFamily: "Inter, sans-serif",
          fontWeight: 600,
          fontSize: "14px",
          color: "#0F0F0F",
          marginBottom: "12px",
        }}
      >
        КАРТА
      </h3>

      {/* Map Container */}
      <div
        ref={mapContainerRef}
        className={cn(
          "relative rounded-xl overflow-hidden",
          "w-full",
          "h-[250px]",
          "md:h-[300px]"
        )}
        style={{
          backgroundColor: "#E5E7EB",
          borderRadius: "12px",
        }}
      >
        {/* Loading Placeholder */}
        {!isLoaded && !error && (
          <div
            className="absolute inset-0 flex items-center justify-center"
            style={{
              backgroundColor: "#E5E7EB",
              borderRadius: "12px",
            }}
          >
            {isLoading ? (
              <div className="flex flex-col items-center gap-2">
                <div
                  className="animate-spin rounded-full border-2 border-[#2563EB] border-t-transparent"
                  style={{ width: "24px", height: "24px" }}
                />
                <p
                  style={{
                    fontFamily: "Inter, sans-serif",
                    fontSize: "12px",
                    color: "#616161",
                  }}
                >
                  Загрузка карты...
                </p>
              </div>
            ) : (
              <div
                style={{
                  fontFamily: "Inter, sans-serif",
                  fontSize: "12px",
                  color: "#616161",
                }}
              >
                Карта загрузится при прокрутке
              </div>
            )}
          </div>
        )}

        {/* Error State */}
        {error && (
          <div
            className="absolute inset-0 flex items-center justify-center"
            style={{
              backgroundColor: "#E5E7EB",
              borderRadius: "12px",
            }}
          >
            <p
              style={{
                fontFamily: "Inter, sans-serif",
                fontSize: "12px",
                color: "#EF4444",
              }}
            >
              {error}
            </p>
          </div>
        )}
      </div>

      {/* Open in Maps Button */}
      <button
        onClick={handleOpenInMaps}
        className={cn(
          "flex items-center gap-1 mt-3",
          "text-[#2563EB] hover:underline",
          "cursor-pointer transition-all"
        )}
        style={{
          fontFamily: "Inter, sans-serif",
          fontWeight: 500,
          fontSize: "14px",
          color: "#2563EB",
          background: "transparent",
          border: "none",
          padding: 0,
          marginTop: "12px",
        }}
        aria-label="Открыть в Яндекс.Картах"
      >
        Открыть в Картах
        <ExternalLink className="w-4 h-4" />
      </button>
    </div>
  );
};

export default PropertyMap;


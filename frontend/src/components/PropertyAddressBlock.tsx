import { MapPin, Navigation } from "lucide-react";
import { cn } from "@/lib/utils";

interface MetroStation {
  name: string;
  distance: number; // в минутах пешком
}

interface PropertyAddressBlockProps {
  address: string;
  city?: string;
  metro?: MetroStation | null;
  className?: string;
}

const PropertyAddressBlock = ({
  address,
  city,
  metro,
  className,
}: PropertyAddressBlockProps) => {
  const isMoscow = city === "Москва";
  const showMetro = isMoscow && metro;

  const handleMapClick = () => {
    const mapUrl = `https://yandex.ru/maps/?text=${encodeURIComponent(address)}`;
    window.open(mapUrl, "_blank", "noopener,noreferrer");
  };

  return (
    <div
      className={cn(
        "px-4 py-3",
        className
      )}
      style={{
        padding: "12px 16px",
      }}
    >
      {/* Address with Map Link */}
      <div className="flex items-start gap-2">
        <MapPin className="w-4 h-4 text-primary flex-shrink-0 mt-0.5" />
        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-2 flex-wrap">
            <span className="text-sm text-foreground leading-snug">{address}</span>
            <button
              onClick={handleMapClick}
              className="text-sm text-primary hover:underline inline-flex items-center gap-1 transition-colors flex-shrink-0"
              aria-label="Открыть на карте"
            >
              На карте
            </button>
          </div>
        </div>
      </div>

      {/* Metro Station (only for Moscow) */}
      {showMetro && (
        <div className="flex items-center gap-2 ml-6 mt-1">
          <div className="w-2 h-2 rounded-full bg-red-500 flex-shrink-0" />
          <span className="text-sm text-muted-foreground">
            {metro.name}, {metro.distance} мин
          </span>
        </div>
      )}
    </div>
  );
};

export default PropertyAddressBlock;


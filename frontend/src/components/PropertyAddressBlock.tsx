import { MapPin, ExternalLink } from "lucide-react";
import { cn } from "@/lib/utils";

interface NearestMetro {
  name: string;
  walkTime: number; // в минутах пешком
}

interface PropertyAddressBlockProps {
  address: string;
  district?: string;
  nearestMetro?: NearestMetro | null;
  city: string;
  className?: string;
}

const PropertyAddressBlock = ({
  address,
  district,
  nearestMetro,
  city,
  className,
}: PropertyAddressBlockProps) => {
  const handleMapClick = () => {
    const mapUrl = `https://yandex.ru/maps/?text=${encodeURIComponent(address)}`;
    window.open(mapUrl, "_blank", "noopener,noreferrer");
  };

  return (
    <div
      className={cn(
        "flex flex-col md:flex-row border-b border-[#EEEEEE]",
        className
      )}
      style={{
        padding: "12px 16px",
        gap: "12px",
      }}
    >
      {/* First Row: Address with Map Link */}
      <div className="flex items-center gap-3 flex-wrap flex-1 min-w-0">
        {/* MapPin Icon */}
        <MapPin
          className="flex-shrink-0"
          style={{
            width: "20px",
            height: "20px",
            color: "#2563EB",
          }}
        />
        
        {/* Address Text */}
        <span
          style={{
            fontFamily: "Inter, sans-serif",
            fontWeight: 500,
            fontSize: "14px",
            color: "#0F0F0F",
          }}
          className="flex-1 min-w-0"
        >
          {address}
        </span>

        {/* Map Link Button */}
        <button
          onClick={handleMapClick}
          className="inline-flex items-center gap-1 flex-shrink-0 cursor-pointer hover:underline transition-all"
          aria-label="Открыть на карте"
          style={{
            fontFamily: "Inter, sans-serif",
            fontWeight: 500,
            fontSize: "13px",
            color: "#2563EB",
          }}
        >
          На карте
          <ExternalLink className="w-3.5 h-3.5" />
        </button>
      </div>

      {/* Second Row: Metro Station (if exists) */}
      {nearestMetro && (
        <div
          className={cn(
            "flex items-center gap-2",
            "md:ml-8"
          )}
          style={{
            fontFamily: "Inter, sans-serif",
            fontWeight: 400,
            fontSize: "13px",
            color: "#616161",
          }}
        >
          <span>
            {nearestMetro.name}, {nearestMetro.walkTime} мин пешком
          </span>
        </div>
      )}
    </div>
  );
};

export default PropertyAddressBlock;


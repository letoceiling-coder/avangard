import { Link } from "react-router-dom";
import { MapPin, Heart, Ruler, Home } from "lucide-react";
import { useFavorites } from "@/hooks/useFavorites";
import { cn } from "@/lib/utils";
import { Button } from "./ui/button";

export interface Complex {
  id: string;
  name: string;
  image: string;
  minPrice: number;
  apartments: number;
  readiness: string;
  address: string;
  rating?: number;
  isNew?: boolean;
  area?: string;
  rooms?: string;
}

interface ComplexCardProps {
  complex: Complex;
  className?: string;
}

// Helper to determine status badge styling
const getStatusConfig = (readiness: string) => {
  const lower = readiness.toLowerCase();
  
  // Completed/delivered
  if (lower.includes("сдан") || lower === "готов") {
    return {
      label: readiness,
      className: "bg-emerald-500/90 text-white shadow-emerald-500/25"
    };
  }
  
  // Under construction
  if (lower.includes("строится") || lower.includes("строительство")) {
    return {
      label: readiness,
      className: "bg-amber-500/90 text-white shadow-amber-500/25"
    };
  }
  
  // Quarter/year dates (e.g., "1 кв. 2025", "2025")
  if (/\d{4}|кв\.?\s*\d{4}/.test(lower)) {
    return {
      label: readiness,
      className: "bg-sky-500/90 text-white shadow-sky-500/25"
    };
  }
  
  // Default
  return {
    label: readiness,
    className: "bg-primary/90 text-primary-foreground shadow-primary/25"
  };
};

const ComplexCard = ({ complex, className }: ComplexCardProps) => {
  const { isFavorite, addToFavorites, removeFromFavorites } = useFavorites();
  const isLiked = isFavorite(complex.id);
  
  const formatPrice = (price: number) => {
    if (price >= 1000000) {
      return `от ${(price / 1000000).toFixed(1)} млн ₽`;
    }
    return `от ${price.toLocaleString("ru-RU")} ₽`;
  };

  const handleFavoriteClick = (e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();
    
    if (isLiked) {
      removeFromFavorites(complex.id);
    } else {
      addToFavorites({
        id: complex.id,
        title: complex.name,
        image: complex.image,
        price: complex.minPrice,
        address: complex.address,
        area: 0,
        rooms: 0,
        floor: 0,
        type: "complex",
      });
    }
  };

  const statusConfig = getStatusConfig(complex.readiness);

  return (
    <Link to={`/complex/${complex.id}`} className={cn("block h-full", className)}>
      <div 
        className="relative bg-card rounded-[20px] overflow-hidden border border-border/60 hover:border-primary/20 hover:shadow-[0_20px_48px_rgba(10,35,66,0.10),0_8px_20px_rgba(10,35,66,0.06)] hover:-translate-y-1 transition-all duration-300 cursor-pointer group h-full flex flex-col"
        style={{ boxShadow: '0 8px 24px rgba(10, 35, 66, 0.06), 0 2px 8px rgba(10, 35, 66, 0.04)' }}
      >
        {/* Image Container */}
        <div className="relative aspect-[4/3] overflow-hidden rounded-t-[20px] bg-muted">
          <img
            src={complex.image}
            alt={complex.name}
            className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
          />
          
          {/* Subtle gradient overlay */}
          <div className="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent" />

          {/* Status Badge - More prominent */}
          <div className="absolute top-3 left-3">
            <span className={cn(
              "inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold shadow-lg backdrop-blur-sm",
              statusConfig.className
            )}>
              {statusConfig.label}
            </span>
          </div>

          {/* Like Button - subtle, smaller */}
          <Button
            size="icon"
            variant="ghost"
            className="absolute top-2.5 right-2.5 w-7 h-7 rounded-full bg-white/70 backdrop-blur-sm hover:bg-white/90 transition-colors z-10"
            onClick={handleFavoriteClick}
          >
            <Heart
              className={cn(
                "w-3.5 h-3.5 transition-colors",
                isLiked ? "fill-primary text-primary" : "text-muted-foreground/50"
              )}
            />
          </Button>

          {/* Compact Key Info - Only 2-3 params with better contrast */}
          <div className="absolute bottom-3 left-3 right-3 flex items-center gap-2">
            <div className="flex items-center gap-1.5 bg-white/95 backdrop-blur-sm px-2.5 py-1.5 rounded-full shadow-sm">
              <Ruler className="w-3 h-3 text-primary/70" />
              <span className="text-xs font-medium text-foreground/90">{complex.area || "35-120 м²"}</span>
            </div>
            <div className="flex items-center gap-1.5 bg-white/95 backdrop-blur-sm px-2.5 py-1.5 rounded-full shadow-sm">
              <Home className="w-3 h-3 text-primary/70" />
              <span className="text-xs font-medium text-foreground/90">{complex.rooms || "1-4 комн."}</span>
            </div>
          </div>
        </div>

        {/* Info Block - Compact */}
        <div className="p-3 sm:p-4 flex-1 flex flex-col">
          {/* Title */}
          <h3 className="text-sm font-medium text-foreground group-hover:text-primary transition-colors line-clamp-1 mb-1.5">
            {complex.name}
          </h3>

          {/* Location */}
          <div className="text-[11px] text-muted-foreground/70 mb-2 flex items-start gap-1">
            <MapPin className="w-3 h-3 flex-shrink-0 mt-0.5 text-primary/40" />
            <span className="line-clamp-1">{complex.address}</span>
          </div>

          {/* Price & Apartments */}
          <div className="flex items-center justify-between mt-auto">
            <p className="text-base font-semibold text-primary">
              {formatPrice(complex.minPrice)}
            </p>
            <span className="text-[11px] text-muted-foreground/60">
              {complex.apartments} квартир
            </span>
          </div>
        </div>
      </div>
    </Link>
  );
};

export default ComplexCard;

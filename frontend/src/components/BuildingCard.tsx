import { Link } from "react-router-dom";
import { MapPin, Ruler, Home, Heart } from "lucide-react";
import { cn } from "@/lib/utils";
import { useFavorites } from "@/hooks/useFavorites";

export interface Building {
  id: string;
  name: string;
  image: string;
  location: string;
  priceFrom: number;
  priceTo: number;
  status: "completed" | "construction" | string;
  area: string;
  rooms: string;
  floors: number;
  highlight?: "popular" | "new" | "discount" | null;
}

interface BuildingCardProps {
  building: Building;
  className?: string;
  index?: number;
}

const getStatusConfig = (status: string) => {
  const lower = status.toLowerCase();
  
  if (lower === "completed" || lower.includes("сдан")) {
    return { 
      label: "Сдан"
    };
  }
  if (lower === "construction" || lower.includes("строится")) {
    return { 
      label: "Строится"
    };
  }
  // Quarter dates like "I кв. 2025"
  return { 
    label: status
  };
};

// Get single badge - prioritize promo for differentiation
const getBadge = (status: string, index: number) => {
  // Every 3rd card gets a promo badge instead of status
  if (index % 3 === 0) {
    const promos = [
      { label: "Выгодно" },
      { label: "Новинка" },
    ];
    return promos[Math.floor(index / 3) % promos.length];
  }
  
  // Otherwise show status badge
  return getStatusConfig(status);
};

const formatPrice = (price: number) => {
  if (price >= 1000000) {
    return `${(price / 1000000).toFixed(1)} млн`;
  }
  return `${(price / 1000).toFixed(0)} тыс`;
};

const BuildingCard = ({ building, className, index = 0 }: BuildingCardProps) => {
  const badge = getBadge(building.status, index);
  const { isFavorite, addToFavorites, removeFromFavorites } = useFavorites();
  const isLiked = isFavorite(building.id);

  const handleFavoriteClick = (e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();
    
    if (isLiked) {
      removeFromFavorites(building.id);
    } else {
      addToFavorites({
        id: building.id,
        title: building.name,
        image: building.image,
        price: building.priceFrom,
        address: building.location,
        area: 0,
        rooms: 0,
        floor: 0,
        type: "complex",
      });
    }
  };

  return (
    <Link
      to={`/buildings/${building.id}`}
      className={cn("block group h-full", className)}
    >
      <article 
        className="relative bg-white rounded-xl shadow-sm overflow-hidden h-full flex flex-col hover:shadow-md transition-shadow duration-200"
      >
        {/* Image Area - Larger */}
        <div className="relative w-full h-[200px] overflow-hidden">
          <img
            src={building.image}
            alt={building.name}
            className="object-cover w-full h-full rounded-t-xl transition-transform duration-300 group-hover:scale-105"
          />

          {/* Status Badge - Overlay style */}
          <div className="absolute top-2 left-2">
            <span className="px-2 py-1 text-xs rounded-md bg-primary/10 text-primary font-medium">
              {badge.label}
            </span>
          </div>

          {/* Favorite Button - Compact */}
          <button
            onClick={handleFavoriteClick}
            className="absolute top-2 right-2 w-6 h-6 rounded-full flex items-center justify-center bg-transparent hover:bg-muted/10 transition-colors duration-200 z-10"
            aria-label={isLiked ? "Удалить из избранного" : "Добавить в избранное"}
          >
            <Heart
              className={cn(
                "w-4 h-4 transition-colors",
                isLiked 
                  ? "fill-primary text-primary" 
                  : "text-white/80"
              )}
            />
          </button>

          {/* CTA Button - Show on hover only */}
          <div className="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none">
            <span className="bg-white/95 backdrop-blur-sm text-foreground px-4 py-2 rounded-full text-sm font-medium shadow-lg">
              Смотреть квартиры
            </span>
          </div>
        </div>

        {/* Content - Compact spacing */}
        <div className="flex flex-col gap-2 py-3 px-3 flex-1">
          {/* Name - Smaller font */}
          <h3 className="text-sm font-medium text-foreground line-clamp-2 leading-tight">
            {building.name}
          </h3>

          {/* Location - text-muted */}
          <p className="text-sm text-muted-foreground truncate">
            {building.location}
          </p>

          {/* Price - Compact */}
          <div className="flex items-baseline gap-1.5 flex-wrap">
            <span className="text-xs text-muted-foreground">от</span>
            <span className="text-blue-600 font-semibold text-sm">
              {formatPrice(building.priceFrom)} ₽
            </span>
            <span className="text-xs text-muted-foreground">до</span>
            <span className="text-blue-600 font-semibold text-sm">
              {formatPrice(building.priceTo)} ₽
            </span>
          </div>

          {/* Specs - Inline text, minimal icons */}
          <div className="flex items-center gap-2 text-xs text-muted-foreground">
            <span className="flex items-center gap-1">
              <Ruler className="w-3 h-3 flex-shrink-0" />
              {building.area}
            </span>
            <span className="flex items-center gap-1">
              <Home className="w-3 h-3 flex-shrink-0" />
              {building.rooms}
            </span>
          </div>
        </div>
      </article>
    </Link>
  );
};

export default BuildingCard;

import { Heart } from "lucide-react";
import { Link } from "react-router-dom";
import { useFavorites, Property } from "@/hooks/useFavorites";
import { toast } from "sonner";
import { cn } from "@/lib/utils";

export interface ExtendedProperty extends Property {
  buildingName?: string;
  status?: "new" | "secondary" | "verified";
  district?: string;
}

interface PropertyCardProps {
  property: ExtendedProperty;
  variant?: "default" | "compact";
  featured?: boolean;
}

const statusConfig = {
  new: { label: "Новостройка", className: "bg-primary/90 text-white" },
  secondary: { label: "Вторичка", className: "bg-title/80 text-white" },
  verified: { label: "Проверено", className: "bg-emerald-500/90 text-white" },
};

const formatRooms = (rooms: number) => {
  if (rooms === 1) return "Студия";
  return `${rooms}-комн.`;
};

const PropertyCard = ({ property, variant = "default", featured = false }: PropertyCardProps) => {
  const { isFavorite, toggleFavorite: toggleFavoriteHook } = useFavorites();
  const favorite = isFavorite(property.id);

  const handleToggleFavorite = async (e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();
    await toggleFavoriteHook(property.id, property);
    toast.success(favorite ? "Удалено из избранного" : "Добавлено в избранное");
  };

  const status = property.status || (property.type === "Новостройка" ? "new" : "secondary");
  const statusInfo = statusConfig[status];

  // Format details as inline text: "1-комн. · 31.9 м² · 6/12 этаж"
  const detailsText = `${formatRooms(property.rooms)} · ${property.area} м² · ${property.floor} эт.`;

  return (
    <Link to={`/property/${property.id}`} className="block group h-full">
      <article 
        className="relative bg-white rounded-xl shadow-sm overflow-hidden h-full flex flex-col hover:shadow-md transition-shadow duration-200"
      >
        {/* Image - dominant, fixed height */}
        <div className="relative w-full h-[180px] overflow-hidden">
          <img
            src={property.image}
            alt={property.title}
            className="w-full h-full object-cover rounded-t-xl transition-transform duration-300 group-hover:scale-105"
          />

          {/* Small Status Badge - only if needed */}
          {statusInfo && !featured && (
            <div className="absolute top-2 left-2">
              <span className={cn(
                "inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium",
                statusInfo.className
              )}>
                {statusInfo.label}
              </span>
            </div>
          )}

          {/* Favorite Button - transparent background */}
          <button
            onClick={handleToggleFavorite}
            className="absolute top-2 right-2 w-8 h-8 rounded-full flex items-center justify-center bg-transparent hover:bg-muted/10 transition-colors duration-200 z-10"
            aria-label={favorite ? "Удалить из избранного" : "Добавить в избранное"}
          >
            <Heart
              className={cn(
                "w-4 h-4 transition-colors",
                favorite 
                  ? "fill-primary text-primary" 
                  : "text-white/80"
              )}
            />
          </button>
        </div>

        {/* Content - compact spacing */}
        <div className="flex flex-col gap-2 py-3 px-3 flex-1">
          {/* Price - left-aligned */}
          <p className="text-base font-semibold text-foreground leading-tight">
            {property.price.toLocaleString("ru-RU")} ₽
          </p>

          {/* Title - left-aligned */}
          <h3 className="text-base font-semibold text-foreground line-clamp-2 leading-tight">
            {property.title}
          </h3>

          {/* Details - inline text, no icons */}
          <p className="text-sm text-muted-foreground">
            {detailsText}
          </p>

          {/* Address - single line, truncated */}
          <p className="text-sm text-muted-foreground truncate">
            {property.address}
            {property.buildingName && <span> · {property.buildingName}</span>}
          </p>
        </div>
      </article>
    </Link>
  );
};

export default PropertyCard;

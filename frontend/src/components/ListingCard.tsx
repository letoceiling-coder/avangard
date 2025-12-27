import { Link } from "react-router-dom";
import { Heart, MapPin, Maximize2, Home, Building2, Scale, Eye } from "lucide-react";
import { Button } from "./ui/button";
import { Card } from "./ui/card";
import { Badge } from "./ui/badge";
import { useFavorites, Property } from "@/hooks/useFavorites";
import { useComparison } from "@/hooks/useComparison";
import { toast } from "sonner";
import { cn } from "@/lib/utils";

interface ListingCardProps {
  property: Property;
  variant?: "default" | "horizontal";
  isActive?: boolean;
  onHover?: () => void;
  onLeave?: () => void;
}

const ListingCard = ({
  property,
  variant = "default",
  isActive = false,
  onHover,
  onLeave,
}: ListingCardProps) => {
  const { isFavorite, addToFavorites, removeFromFavorites } = useFavorites();
  const { isInCompare, addToCompare, removeFromCompare, canAddMore } = useComparison();
  const favorite = isFavorite(property.id);
  const inCompare = isInCompare(property.id);

  const pricePerMeter = Math.round(property.price / property.area);

  const toggleFavorite = (e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();
    if (favorite) {
      removeFromFavorites(property.id);
      toast.success("Удалено из избранного");
    } else {
      addToFavorites(property);
      toast.success("Добавлено в избранное");
    }
  };

  const toggleCompare = (e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();
    if (inCompare) {
      removeFromCompare(property.id);
      toast.success("Убрано из сравнения");
    } else {
      if (!canAddMore) {
        toast.error("Можно сравнить максимум 3 объекта");
        return;
      }
      addToCompare({
        ...property,
        pricePerMeter,
      });
      toast.success("Добавлено к сравнению");
    }
  };

  if (variant === "horizontal") {
    return (
      <Link to={`/property/${property.id}`}>
        <Card
          className={cn(
            "overflow-hidden group transition-all duration-normal rounded-[20px]",
            isActive && "ring-2 ring-primary"
          )}
          radius="lg"
          hoverable
          onMouseEnter={onHover}
          onMouseLeave={onLeave}
        >
          <div className="flex flex-col sm:flex-row">
            {/* Image */}
            <div className="relative w-full sm:w-48 md:w-56 flex-shrink-0 aspect-[4/3] sm:aspect-auto sm:h-auto overflow-hidden rounded-t-[20px] sm:rounded-l-[20px] sm:rounded-tr-none">
              <img
                src={property.image}
                alt={property.title}
                className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
              />
              <div className="absolute top-3 left-3">
                <Badge
                  variant={property.type === "Новостройка" ? "info" : "default"}
                  size="sm"
                  label={property.type}
                />
              </div>
            </div>

            {/* Content */}
            <div className="flex-1 p-4 flex flex-col">
              {/* Top Row */}
              <div className="flex items-start justify-between gap-3">
                <div className="flex-1 min-w-0">
                  <h3 className="font-semibold text-foreground group-hover:text-primary transition-colors line-clamp-2">
                    {property.title}
                  </h3>
                  <div className="flex items-center gap-1.5 text-muted-foreground text-sm mt-1">
                    <MapPin className="w-3.5 h-3.5 text-primary flex-shrink-0" />
                    <span className="truncate">{property.address}</span>
                  </div>
                </div>
              </div>

              {/* Price & Stats */}
              <div className="flex items-end justify-between mt-auto pt-3">
                <div>
                  <p className="text-xl font-bold text-primary">
                    {property.price.toLocaleString("ru-RU")} ₽
                  </p>
                  <p className="text-xs text-muted-foreground">
                    {pricePerMeter.toLocaleString("ru-RU")} ₽/м²
                  </p>
                </div>

                <div className="flex items-center gap-3 text-sm text-muted-foreground">
                  <span className="flex items-center gap-1">
                    <Maximize2 className="w-3.5 h-3.5" />
                    {property.area} м²
                  </span>
                  <span>{property.rooms}-к</span>
                  <span>{property.floor} эт.</span>
                </div>
              </div>

              {/* Actions */}
              <div className="flex items-center justify-between pt-3 mt-3 border-t border-border">
                <div className="flex items-center gap-2">
                  <Button
                    variant="ghost"
                    size="sm"
                    className="h-7 px-2"
                    onClick={toggleFavorite}
                  >
                    <Heart
                      className={cn(
                        "w-3.5 h-3.5",
                        favorite ? "fill-primary text-primary" : "text-muted-foreground/60"
                      )}
                    />
                  </Button>
                  <Button
                    variant="ghost"
                    size="sm"
                    className={cn(
                      "h-8 px-2",
                      inCompare && "text-primary"
                    )}
                    onClick={toggleCompare}
                  >
                    <Scale className="w-4 h-4" />
                  </Button>
                </div>
                <Button variant="secondary" size="sm" rightIcon={<Eye className="w-4 h-4" />}>
                  Подробнее
                </Button>
              </div>
            </div>
          </div>
        </Card>
      </Link>
    );
  }

  // Default vertical card
  return (
    <Link to={`/property/${property.id}`}>
      <Card
        className={cn(
          "overflow-hidden group transition-all duration-normal rounded-[20px]",
          isActive && "ring-2 ring-primary"
        )}
        radius="lg"
        glow
        hoverable
        onMouseEnter={onHover}
        onMouseLeave={onLeave}
      >
        {/* Image */}
        <div className="relative aspect-[4/3] overflow-hidden rounded-t-[20px]">
          <img
            src={property.image}
            alt={property.title}
            className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
          />

          {/* Action Buttons */}
          <div className="absolute top-3 right-3 flex flex-col gap-1.5">
            <Button
              size="icon"
              variant="ghost"
              className="w-8 h-8 rounded-full bg-white/80 backdrop-blur-sm hover:bg-white"
              onClick={toggleFavorite}
            >
              <Heart
                className={cn(
                  "w-3.5 h-3.5",
                  favorite ? "fill-primary text-primary" : "text-muted-foreground/60"
                )}
              />
            </Button>
            <Button
              size="icon"
              variant="ghost"
              className={cn(
                "w-8 h-8 rounded-full backdrop-blur-sm",
                inCompare
                  ? "bg-primary text-primary-foreground hover:bg-primary/90"
                  : "bg-white/80 hover:bg-white"
              )}
              onClick={toggleCompare}
            >
              <Scale className={cn("w-3.5 h-3.5", !inCompare && "text-muted-foreground/60")} />
            </Button>
          </div>

          {/* Type Badge */}
          <div className="absolute top-3 left-3">
            <Badge
              variant={property.type === "Новостройка" ? "info" : "default"}
              size="sm"
              label={property.type}
            />
          </div>

          {/* Price Overlay */}
          <div className="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-foreground/60 to-transparent p-3 pt-8">
            <p className="text-base font-semibold text-primary-foreground">
              {property.price.toLocaleString("ru-RU")} ₽
            </p>
            <p className="text-[11px] text-primary-foreground/70">
              {pricePerMeter.toLocaleString("ru-RU")} ₽/м²
            </p>
          </div>
        </div>

        {/* Content */}
        <div className="p-3.5">
          <h3 className="font-medium text-sm text-foreground line-clamp-2 group-hover:text-primary transition-colors">
            {property.title}
          </h3>

          <div className="flex items-center gap-1 text-muted-foreground/80 text-[12px] mt-1.5">
            <MapPin className="w-3 h-3 text-primary/50 flex-shrink-0" />
            <span className="truncate">{property.address}</span>
          </div>

          {/* Parameters */}
          <div className="flex items-center gap-3 pt-2.5 mt-2.5 border-t border-border/50">
            <div className="flex items-center gap-1 text-[12px]">
              <Home className="w-3 h-3 text-primary/60" />
              <span className="text-foreground/80">{property.rooms}</span>
              <span className="text-muted-foreground/70">комн.</span>
            </div>
            <div className="flex items-center gap-1 text-[12px]">
              <Maximize2 className="w-3 h-3 text-primary/60" />
              <span className="text-foreground/80">{property.area}</span>
              <span className="text-muted-foreground/70">м²</span>
            </div>
            <div className="flex items-center gap-1 text-[12px]">
              <Building2 className="w-3 h-3 text-primary/60" />
              <span className="text-foreground/80">{property.floor}</span>
              <span className="text-muted-foreground/70">эт.</span>
            </div>
          </div>
        </div>
      </Card>
    </Link>
  );
};

export default ListingCard;

import React from "react";
import { Link } from "react-router-dom";
import { useFavorites } from "@/hooks/useFavorites";
import { useComparison } from "@/hooks/useComparison";
import { toast } from "sonner";
import { cn } from "@/lib/utils";
import { Share2, Heart, Scale, ChevronRight } from "lucide-react";
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from "@/components/ui/breadcrumb";

interface PropertyDetailHeaderProps {
  propertyId: string;
  propertyTitle: string;
  breadcrumbs?: Array<{ label: string; href: string }>;
  property?: {
    id: string;
    title: string;
    price: number;
    image: string;
    area: number;
    rooms: number;
    floor: number;
    address: string;
    type: string;
  };
}

const PropertyDetailHeader = ({
  propertyId,
  propertyTitle,
  breadcrumbs,
  property,
}: PropertyDetailHeaderProps) => {
  const { isFavorite, addToFavorites, removeFromFavorites } = useFavorites();
  const { isInCompare, addToCompare, removeFromCompare, canAddMore } = useComparison();

  const favorite = isFavorite(propertyId);
  const inCompare = isInCompare(propertyId);

  const handleShare = async () => {
    const shareUrl = `${window.location.origin}/property/${propertyId}`;
    const shareText = `${propertyTitle}`;

    if (navigator.share) {
      try {
        await navigator.share({
          title: propertyTitle,
          text: shareText,
          url: shareUrl,
        });
      } catch (error) {
        if ((error as Error).name !== "AbortError") {
          await navigator.clipboard.writeText(shareUrl);
          toast.success("Ссылка скопирована");
        }
      }
    } else {
      await navigator.clipboard.writeText(shareUrl);
      toast.success("Ссылка скопирована");
    }
  };

  const handleFavorite = () => {
    if (!property) {
      toast.error("Недостаточно данных");
      return;
    }

    if (favorite) {
      removeFromFavorites(propertyId);
      toast.success("Удалено из избранного");
    } else {
      addToFavorites(property);
      toast.success("Добавлено в избранное");
    }
  };

  const handleCompare = () => {
    if (!property) {
      toast.error("Недостаточно данных");
      return;
    }

    if (inCompare) {
      removeFromCompare(propertyId);
      toast.success("Удалено из сравнения");
    } else {
      if (!canAddMore) {
        toast.error("Можно добавить максимум 3 объекта");
        return;
      }
      const success = addToCompare({
        id: property.id,
        title: property.title,
        price: property.price,
        image: property.image,
        area: property.area,
        rooms: property.rooms,
        floor: property.floor,
        address: property.address,
        type: property.type,
      });
      if (success) {
        toast.success("Добавлено в сравнение");
      } else {
        toast.error("Не удалось добавить");
      }
    }
  };

  return (
    <div className="w-full bg-white">
      {/* Compact Header: Breadcrumbs + Actions */}
      <div className="flex items-center justify-between gap-3 px-4 py-2 md:px-6 md:py-3">
        {/* Left: Compact Breadcrumbs */}
        <div className="flex-1 min-w-0">
          <Breadcrumb>
            <BreadcrumbList
              className={cn(
                "flex items-center gap-1 md:gap-1.5",
                "text-xs md:text-sm",
                "text-muted-foreground",
                "overflow-hidden"
              )}
              style={{
                maxWidth: "100%",
              }}
            >
              {breadcrumbs?.map((crumb, index) => (
                <React.Fragment key={index}>
                  <BreadcrumbItem className="inline-flex items-center shrink-0">
                    <BreadcrumbLink asChild>
                      <Link
                        to={crumb.href}
                        className={cn(
                          "truncate max-w-[80px] md:max-w-none",
                          "hover:text-foreground",
                          "transition-colors",
                          "font-normal"
                        )}
                        title={crumb.label}
                      >
                        {crumb.label}
                      </Link>
                    </BreadcrumbLink>
                  </BreadcrumbItem>
                  {index < (breadcrumbs?.length || 0) - 1 && (
                    <BreadcrumbSeparator className="shrink-0">
                      <ChevronRight className="w-3 h-3 md:w-3.5 md:h-3.5" />
                    </BreadcrumbSeparator>
                  )}
                </React.Fragment>
              ))}
              {/* Current page - truncated on mobile */}
              {breadcrumbs && breadcrumbs.length > 0 && (
                <BreadcrumbSeparator className="shrink-0">
                  <ChevronRight className="w-3 h-3 md:w-3.5 md:h-3.5" />
                </BreadcrumbSeparator>
              )}
              <BreadcrumbItem className="inline-flex items-center min-w-0 flex-1 overflow-hidden">
                <BreadcrumbPage
                  className={cn(
                    "font-normal text-foreground",
                    "truncate",
                    "block"
                  )}
                  title={propertyTitle}
                  style={{
                    maxWidth: "100%",
                  }}
                >
                  {propertyTitle}
                </BreadcrumbPage>
              </BreadcrumbItem>
            </BreadcrumbList>
          </Breadcrumb>
        </div>

        {/* Right: Action Icons */}
        <div className="flex items-center gap-1 md:gap-2 shrink-0">
          {/* Share */}
          <button
            onClick={handleShare}
            className={cn(
              "flex items-center justify-center",
              "w-10 h-10 md:w-11 md:h-11",
              "rounded-lg",
              "text-[#616161]",
              "hover:bg-[#F3F4F6] hover:text-[#2563EB]",
              "active:bg-[#DBEAFE] active:scale-95",
              "transition-all duration-200",
              "touch-manipulation"
            )}
            aria-label="Поделиться"
          >
            <Share2 className="w-4 h-4 md:w-5 md:h-5" strokeWidth={2} />
          </button>

          {/* Favorite */}
          <button
            onClick={handleFavorite}
            className={cn(
              "flex items-center justify-center",
              "w-10 h-10 md:w-11 md:h-11",
              "rounded-lg",
              "transition-all duration-200",
              "touch-manipulation",
              favorite
                ? "text-[#EF4444] bg-[#FEF2F2] hover:bg-[#FEE2E2]"
                : "text-[#616161] hover:bg-[#F3F4F6] hover:text-[#EF4444]",
              "active:scale-95"
            )}
            aria-label={favorite ? "Удалить из избранного" : "Добавить в избранное"}
          >
            <Heart
              className={cn(
                "w-4 h-4 md:w-5 md:h-5",
                favorite && "fill-current"
              )}
              strokeWidth={2}
            />
          </button>

          {/* Compare */}
          <button
            onClick={handleCompare}
            className={cn(
              "flex items-center justify-center",
              "w-10 h-10 md:w-11 md:h-11",
              "rounded-lg",
              "transition-all duration-200",
              "touch-manipulation",
              inCompare
                ? "text-[#2563EB] bg-[#DBEAFE] hover:bg-[#BFE5FF]"
                : "text-[#616161] hover:bg-[#F3F4F6] hover:text-[#2563EB]",
              "active:scale-95"
            )}
            aria-label={inCompare ? "Удалить из сравнения" : "Добавить в сравнение"}
          >
            <Scale
              className={cn(
                "w-4 h-4 md:w-5 md:h-5",
                inCompare && "stroke-[2.5]"
              )}
              strokeWidth={2}
            />
          </button>
        </div>
      </div>
    </div>
  );
};

export default PropertyDetailHeader;


import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { useFavorites } from "@/hooks/useFavorites";
import { useComparison } from "@/hooks/useComparison";
import { toast } from "sonner";
import { cn } from "@/lib/utils";

interface PropertyTopBarProps {
  propertyId: string;
  propertyTitle: string;
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

const PropertyTopBar = ({ propertyId, propertyTitle, property }: PropertyTopBarProps) => {
  const navigate = useNavigate();
  const { isFavorite, addToFavorites, removeFromFavorites } = useFavorites();
  const { isInCompare, addToCompare, removeFromCompare, canAddMore } = useComparison();
  const [isShareMenuOpen, setIsShareMenuOpen] = useState(false);

  const favorite = isFavorite(propertyId);
  const inCompare = isInCompare(propertyId);

  const handleBack = () => {
    navigate(-1);
  };

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
        setIsShareMenuOpen(false);
      } catch (error) {
        // User cancelled or share failed
        if ((error as Error).name !== "AbortError") {
          // Fallback to clipboard
          await navigator.clipboard.writeText(shareUrl);
          toast.success("Ссылка скопирована в буфер обмена");
        }
        setIsShareMenuOpen(false);
      }
    } else {
      await navigator.clipboard.writeText(shareUrl);
      toast.success("Ссылка скопирована в буфер обмена");
      setIsShareMenuOpen(false);
    }
  };

  const handleFavorite = () => {
    if (!property) {
      toast.error("Недостаточно данных для добавления в избранное");
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
      toast.error("Недостаточно данных для сравнения");
      return;
    }

    if (inCompare) {
      removeFromCompare(propertyId);
      toast.success("Удалено из сравнения");
    } else {
      if (!canAddMore) {
        toast.error(`Можно добавить максимум 3 объекта для сравнения`);
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
        toast.error("Не удалось добавить в сравнение");
      }
    }
  };

  return (
    <div className="sticky top-0 z-[100] bg-white border-b border-[#EEEEEE] h-14 flex items-center px-4 md:px-6 lg:px-8">
      <div className="flex items-center justify-between w-full max-w-7xl mx-auto gap-2">
        {/* Left: Back button (mobile) / Logo (desktop) */}
        <div className="flex items-center min-w-0 flex-1">
          {/* Mobile: Back button */}
          <button
            onClick={handleBack}
            className="lg:hidden flex items-center justify-center w-12 h-12 -ml-2 rounded-lg transition-colors hover:bg-[#DBEAFE] active:bg-[#BFE5FF] touch-manipulation"
            aria-label="Назад"
          >
            <svg
              width="24"
              height="24"
              viewBox="0 0 24 24"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
              className="text-[#0F0F0F]"
            >
              <path
                d="M15 18L9 12L15 6"
                stroke="currentColor"
                strokeWidth="2"
                strokeLinecap="round"
                strokeLinejoin="round"
              />
            </svg>
          </button>

          {/* Desktop: Title (centered) */}
          <h1 className="hidden lg:block text-base font-semibold text-[#0F0F0F] truncate text-center flex-1 px-4 max-w-2xl mx-auto">
            {propertyTitle}
          </h1>
        </div>

        {/* Right: Action buttons */}
        <div className="flex items-center gap-2">
          {/* Share button */}
          <button
            onClick={handleShare}
            className="flex items-center justify-center w-12 h-12 rounded-lg transition-all touch-manipulation hover:bg-[#DBEAFE] active:bg-[#BFE5FF]"
            aria-label="Поделиться"
          >
            <svg
              width="24"
              height="24"
              viewBox="0 0 24 24"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
              className={cn(
                "transition-colors",
                isShareMenuOpen ? "text-[#1D4ED8]" : "text-[#2563EB]"
              )}
            >
              <path
                d="M18 8C19.6569 8 21 6.65685 21 5C21 3.34315 19.6569 2 18 2C16.3431 2 15 3.34315 15 5C15 5.35064 15.0602 5.68722 15.1707 6M6 15C7.65685 15 9 13.6569 9 12C9 10.3431 7.65685 9 6 9C4.34315 9 3 10.3431 3 12C3 12.3506 3.06015 12.6872 3.17071 13M15.1707 6C14.7473 6.58835 14.5 7.27119 14.5 8C14.5 8.72881 14.7473 9.41165 15.1707 10M8.82929 11C9.25272 10.4117 9.5 9.72881 9.5 9C9.5 8.27119 9.25272 7.58835 8.82929 7M15.1707 10L8.82929 11M18 16C19.6569 16 21 17.3431 21 19C21 20.6569 19.6569 22 18 22C16.3431 22 15 20.6569 15 19C15 18.6494 15.0602 18.3128 15.1707 18M8.82929 13C9.25272 13.5884 9.5 14.2712 9.5 15C9.5 15.7288 9.25272 16.4116 8.82929 17M15.1707 18L8.82929 17"
                stroke="currentColor"
                strokeWidth="2"
                strokeLinecap="round"
                strokeLinejoin="round"
              />
            </svg>
          </button>

          {/* Favorite button */}
          <button
            onClick={handleFavorite}
            className={cn(
              "flex items-center justify-center w-12 h-12 rounded-lg transition-all touch-manipulation",
              "hover:bg-[#DBEAFE] active:bg-[#BFE5FF]",
              favorite && "hover:bg-red-50 active:bg-red-100"
            )}
            aria-label={favorite ? "Удалить из избранного" : "Добавить в избранное"}
          >
            <svg
              width="24"
              height="24"
              viewBox="0 0 24 24"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
              className={cn(
                "transition-colors",
                favorite ? "text-[#EF4444] fill-[#EF4444]" : "text-[#2563EB]"
              )}
            >
              <path
                d="M20.84 4.61C20.3292 4.099 19.7228 3.69364 19.0554 3.41708C18.3879 3.14052 17.6725 2.99817 16.95 2.99817C16.2275 2.99817 15.5121 3.14052 14.8446 3.41708C14.1772 3.69364 13.5708 4.099 13.06 4.61L12 5.67L10.94 4.61C9.9083 3.57831 8.50903 2.99871 7.05 2.99871C5.59096 2.99871 4.19169 3.57831 3.16 4.61C2.1283 5.64169 1.54871 7.04097 1.54871 8.5C1.54871 9.95903 2.1283 11.3583 3.16 12.39L4.22 13.45L12 21.23L19.78 13.45L20.84 12.39C21.351 11.8792 21.7564 11.2728 22.0329 10.6054C22.3095 9.93789 22.4518 9.22248 22.4518 8.5C22.4518 7.77752 22.3095 7.0621 22.0329 6.39464C21.7564 5.72718 21.351 5.12075 20.84 4.61Z"
                stroke="currentColor"
                strokeWidth="2"
                strokeLinecap="round"
                strokeLinejoin="round"
              />
            </svg>
          </button>

          {/* Compare button */}
          <button
            onClick={handleCompare}
            className={cn(
              "flex items-center justify-center w-12 h-12 rounded-lg transition-all touch-manipulation",
              "hover:bg-[#DBEAFE] active:bg-[#BFE5FF]",
              inCompare && "bg-[#DBEAFE]"
            )}
            aria-label={inCompare ? "Удалить из сравнения" : "Добавить в сравнение"}
          >
            <svg
              width="24"
              height="24"
              viewBox="0 0 24 24"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
              className={cn(
                "transition-colors",
                inCompare ? "text-[#1D4ED8]" : "text-[#2563EB]"
              )}
            >
              <path
                d="M8 3H6C4.89543 3 4 3.89543 4 5V19C4 20.1046 4.89543 21 6 21H8M16 3H18C19.1046 3 20 3.89543 20 5V19C20 20.1046 19.1046 21 18 21H16M8 21V13H16V21M8 21H16M8 13L12 9L16 13"
                stroke="currentColor"
                strokeWidth="2"
                strokeLinecap="round"
                strokeLinejoin="round"
              />
            </svg>
          </button>
        </div>
      </div>
    </div>
  );
};

export default PropertyTopBar;


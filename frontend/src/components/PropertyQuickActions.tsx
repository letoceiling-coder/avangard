import { useState } from "react";
import { useFavorites } from "@/hooks/useFavorites";
import { useComparison } from "@/hooks/useComparison";
import { toast } from "sonner";
import { cn } from "@/lib/utils";
import { Heart } from "lucide-react";

interface PropertyQuickActionsProps {
  propertyId: string;
  property: {
    id: string;
    title: string;
    price: number;
    image: string;
    area: number;
    rooms: number;
    floor: number;
    address: string;
    type: string;
    pricePerMeter?: number;
  };
  className?: string;
}

const PropertyQuickActions = ({
  propertyId,
  property,
  className,
}: PropertyQuickActionsProps) => {
  const { isFavorite, addToFavorites, removeFromFavorites } = useFavorites();
  const { isInCompare, addToCompare, removeFromCompare, canAddMore } = useComparison();
  const [isPressed, setIsPressed] = useState<string | null>(null);

  const favorite = isFavorite(propertyId);
  const inCompare = isInCompare(propertyId);

  const handleCompare = () => {
    if (inCompare) {
      removeFromCompare(propertyId);
      toast.success("Убрано из сравнения");
    } else {
      if (!canAddMore) {
        toast.error("Можно сравнить максимум 3 объекта");
        return;
      }
      const pricePerMeter = property.pricePerMeter || Math.round(property.price / property.area);
      addToCompare({
        ...property,
        pricePerMeter,
      });
      toast.success("Добавлено к сравнению");
    }
  };

  const handleCopyLink = async () => {
    try {
      const url = window.location.href;
      await navigator.clipboard.writeText(url);
      toast.success("Скопировано");
    } catch (error) {
      toast.error("Не удалось скопировать ссылку");
    }
  };

  const handleFavorite = () => {
    if (favorite) {
      removeFromFavorites(propertyId);
      toast.success("Удалено из избранного");
    } else {
      addToFavorites(property);
      toast.success("Добавлено в избранное");
    }
  };

  const handleButtonPress = (buttonId: string) => {
    setIsPressed(buttonId);
    setTimeout(() => setIsPressed(null), 150);
  };

  const buttonBaseStyles = {
    width: "48px",
    height: "48px",
    borderRadius: "8px",
    cursor: "pointer",
    transition: "all 0.3s ease",
    display: "flex",
    alignItems: "center",
    justifyContent: "center",
    border: "none",
    background: "transparent",
    padding: 0,
  };

  return (
    <div
      className={cn(
        "flex items-center",
        "gap-2 md:gap-3",
        className
      )}
      style={{
        padding: "12px 16px",
        gap: "12px",
      }}
    >
      {/* Compare Button */}
      <button
        onClick={() => {
          handleButtonPress("compare");
          handleCompare();
        }}
        className={cn(
          "hover:bg-[#F3F4F6]",
          inCompare && "bg-[#DBEAFE]",
          isPressed === "compare" && "scale-95"
        )}
        style={{
          ...buttonBaseStyles,
          color: inCompare ? "#2563EB" : "#616161",
        }}
        aria-label={inCompare ? "Убрать из сравнения" : "Добавить к сравнению"}
      >
        <svg
          width="20"
          height="20"
          viewBox="0 0 24 24"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
          style={{ width: "20px", height: "20px" }}
        >
          <path
            d="M8 3H5C3.89543 3 3 3.89543 3 5V8M21 8V5C21 3.89543 20.1046 3 19 3H16M16 21H19C20.1046 21 21 20.1046 21 19V16M3 16V19C3 20.1046 3.89543 21 5 21H8"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
          />
        </svg>
      </button>

      {/* Copy Link Button */}
      <button
        onClick={() => {
          handleButtonPress("copy");
          handleCopyLink();
        }}
        className={cn(
          "hover:bg-[#F3F4F6]",
          isPressed === "copy" && "scale-95"
        )}
        style={{
          ...buttonBaseStyles,
          color: "#616161",
        }}
        aria-label="Копировать ссылку"
      >
        <svg
          width="20"
          height="20"
          viewBox="0 0 24 24"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
          style={{ width: "20px", height: "20px" }}
        >
          <path
            d="M16 4H18C19.1046 4 20 4.89543 20 6V18C20 19.1046 19.1046 20 18 20H6C4.89543 20 4 19.1046 4 18V16M8 4H6C4.89543 4 4 4.89543 4 6V8M8 8H16C17.1046 8 18 8.89543 18 10V18C18 19.1046 17.1046 20 16 20H8C6.89543 20 6 19.1046 6 18V10C6 8.89543 6.89543 8 8 8Z"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
          />
        </svg>
      </button>

      {/* Favorite Button */}
      <button
        onClick={() => {
          handleButtonPress("favorite");
          handleFavorite();
        }}
        className={cn(
          "hover:bg-[#FEF2F2]",
          favorite && "bg-[#FEF2F2]",
          isPressed === "favorite" && "scale-95"
        )}
        style={{
          ...buttonBaseStyles,
          color: favorite ? "#EF4444" : "#616161",
        }}
        aria-label={favorite ? "Удалить из избранного" : "Добавить в избранное"}
      >
        <Heart
          className={cn(
            "w-5 h-5",
            favorite && "fill-current"
          )}
        />
      </button>
    </div>
  );
};

export default PropertyQuickActions;


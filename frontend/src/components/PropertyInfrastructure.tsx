import { cn } from "@/lib/utils";
import {
  GraduationCap,
  Trees,
  Hospital,
  ShoppingBag,
  MapPin,
  Church,
  Building2,
  Utensils,
} from "lucide-react";

interface InfrastructureItem {
  type: string;
  icon: string; // emoji или название иконки
  name: string;
}

interface PropertyInfrastructureProps {
  infrastructure: InfrastructureItem[];
  className?: string;
}

const getIconComponent = (iconName: string) => {
  const iconMap: Record<string, React.ComponentType<{ className?: string }>> = {
    school: GraduationCap,
    park: Trees,
    hospital: Hospital,
    clinic: Hospital,
    shop: ShoppingBag,
    store: ShoppingBag,
    square: MapPin,
    church: Church,
    building: Building2,
    restaurant: Utensils,
  };

  return iconMap[iconName.toLowerCase()] || Building2;
};

const getEmojiIcon = (iconName: string): string => {
  const emojiMap: Record<string, string> = {
    school: "🏫",
    park: "🌳",
    hospital: "🏥",
    clinic: "🏥",
    shop: "🛒",
    store: "🛒",
    square: "🏘️",
    church: "⛪",
    building: "🏢",
    restaurant: "🍽️",
  };

  return emojiMap[iconName.toLowerCase()] || "📍";
};

const PropertyInfrastructure = ({
  infrastructure,
  className,
}: PropertyInfrastructureProps) => {
  const handleItemClick = (item: InfrastructureItem) => {
    // TODO: Открыть детали или показать на карте
    console.log("Clicked infrastructure item:", item);
  };

  if (!infrastructure || infrastructure.length === 0) {
    return null;
  }

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
        РЯДОМ
      </h3>

      {/* Horizontal Scrollable Carousel */}
      <div
        className={cn(
          "flex overflow-x-auto overflow-y-hidden pb-2 scrollbar-hide",
          "w-full"
        )}
        style={{
          gap: "12px",
          paddingBottom: "8px",
          scrollbarWidth: "thin",
          scrollbarColor: "#E5E7EB transparent",
          WebkitOverflowScrolling: "touch",
        }}
      >
        {infrastructure.map((item, index) => (
          <button
            key={index}
            onClick={() => handleItemClick(item)}
            className={cn(
              "flex flex-col items-center justify-center gap-2",
              "rounded-xl bg-[#FAFAFA] hover:bg-[#EEEEEE]",
              "transition-all duration-200",
              "cursor-pointer flex-shrink-0",
              "w-[70px] h-[70px]",
              "md:w-20 md:h-20"
            )}
            style={{
              backgroundColor: "#FAFAFA",
              borderRadius: "12px",
            }}
            aria-label={item.name}
          >
            {/* Icon */}
            <div
              className="text-4xl"
              style={{
                fontSize: "40px",
                lineHeight: "1",
              }}
            >
              {getEmojiIcon(item.icon)}
            </div>

            {/* Name */}
            <span
              style={{
                fontFamily: "Inter, sans-serif",
                fontWeight: 400,
                fontSize: "12px",
                color: "#0F0F0F",
                textAlign: "center",
                lineHeight: "1.2",
              }}
            >
              {item.name}
            </span>
          </button>
        ))}
      </div>

    </div>
  );
};

export default PropertyInfrastructure;


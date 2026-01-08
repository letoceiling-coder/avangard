import { Link } from "react-router-dom";
import { cn } from "@/lib/utils";

interface SimilarObject {
  id: string;
  image: string;
  price: number;
  area?: number;
  floor?: number;
  totalFloors?: number;
  rooms?: number;
  district?: string;
  address?: string;
  description?: string;
}

interface PropertySimilarObjectsProps {
  similar: SimilarObject[];
  className?: string;
}

const formatPrice = (price: number): string => {
  if (price >= 1000000) {
    const millions = price / 1000000;
    if (millions >= 10) {
      return `${millions.toFixed(1).replace(".", ".")}M ₽`;
    }
    return `${millions.toFixed(1).replace(".", ".")}M ₽`;
  }
  return `${price.toLocaleString("ru-RU")} ₽`;
};

const PropertySimilarObjects = ({
  similar,
  className,
}: PropertySimilarObjectsProps) => {
  if (!similar || similar.length === 0) {
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
        ПОХОЖИЕ
      </h3>

      {/* Horizontal Scrollable Carousel */}
      <div
        className={cn(
          "flex overflow-x-auto overflow-y-hidden pb-2 scrollbar-hide",
          "w-full gap-3"
        )}
        style={{
          gap: "12px",
          paddingBottom: "8px",
          scrollbarWidth: "thin",
          scrollbarColor: "#E5E7EB transparent",
          WebkitOverflowScrolling: "touch",
        }}
      >
        {similar.map((item) => (
          <Link
            key={item.id}
            to={`/property/${item.id}`}
            className={cn(
              "flex flex-col flex-shrink-0",
              "bg-white border border-[#EEEEEE] rounded-xl",
              "cursor-pointer transition-all duration-200",
              "hover:shadow-lg",
              "w-[160px]",
              "md:w-[180px]"
            )}
            style={{
              borderRadius: "12px",
              boxShadow: "none",
            }}
            onMouseEnter={(e) => {
              e.currentTarget.style.boxShadow = "0 4px 12px rgba(0,0,0,0.1)";
            }}
            onMouseLeave={(e) => {
              e.currentTarget.style.boxShadow = "none";
            }}
          >
            {/* Image */}
            <div
              className="relative w-full overflow-hidden"
              style={{
                height: "120px",
                borderRadius: "12px 12px 0 0",
              }}
            >
              <img
                src={item.image}
                alt={item.description || `Объект ${item.id}`}
                loading="lazy"
                className="w-full h-full object-cover"
                style={{
                  borderRadius: "12px 12px 0 0",
                }}
                onError={(e) => {
                  const target = e.target as HTMLImageElement;
                  target.src = "https://via.placeholder.com/180x120?text=No+Image";
                }}
              />
            </div>

            {/* Content */}
            <div
              className="flex flex-col p-3"
              style={{
                padding: "12px",
                gap: "8px",
              }}
            >
              {/* Price */}
              <p
                style={{
                  fontFamily: "Inter, sans-serif",
                  fontWeight: 600,
                  fontSize: "14px",
                  color: "#0F0F0F",
                  lineHeight: "1.2",
                }}
              >
                {formatPrice(item.price)}
              </p>

              {/* Details */}
              <div
                className="flex flex-col"
                style={{
                  gap: "4px",
                }}
              >
                {/* Line 1: метраж + этаж */}
                {item.area && (
                  <p
                    style={{
                      fontFamily: "Inter, sans-serif",
                      fontWeight: 400,
                      fontSize: "12px",
                      color: "#616161",
                      lineHeight: "1.4",
                    }}
                  >
                    {item.area} м²
                    {item.floor && item.totalFloors
                      ? `, ${item.floor}/${item.totalFloors} этаж`
                      : item.floor
                      ? `, ${item.floor} этаж`
                      : ""}
                  </p>
                )}

                {/* Line 2: район */}
                {item.district && (
                  <p
                    style={{
                      fontFamily: "Inter, sans-serif",
                      fontWeight: 400,
                      fontSize: "12px",
                      color: "#616161",
                      lineHeight: "1.4",
                    }}
                  >
                    {item.district}
                  </p>
                )}

                {/* Line 3: адрес (если есть) */}
                {item.address && !item.district && (
                  <p
                    style={{
                      fontFamily: "Inter, sans-serif",
                      fontWeight: 400,
                      fontSize: "12px",
                      color: "#616161",
                      lineHeight: "1.4",
                      overflow: "hidden",
                      textOverflow: "ellipsis",
                      display: "-webkit-box",
                      WebkitLineClamp: 1,
                      WebkitBoxOrient: "vertical",
                    }}
                  >
                    {item.address}
                  </p>
                )}
              </div>
            </div>
          </Link>
        ))}
      </div>
    </div>
  );
};

export default PropertySimilarObjects;


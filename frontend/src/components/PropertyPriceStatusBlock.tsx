import { cn } from "@/lib/utils";

type StatusType = "good_price" | "new" | "price_drop" | "verified" | null;

interface PropertyPriceStatusBlockProps {
  price: number;
  pricePerSquareMeter: number;
  status: StatusType;
  className?: string;
}

const formatPrice = (price: number): string => {
  return price.toLocaleString("ru-RU", {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  });
};

const getStatusConfig = (status: StatusType) => {
  switch (status) {
    case "good_price":
      return {
        label: "Хорошая цена",
        bgColor: "#D1FAE5",
        textColor: "#065F46",
      };
    case "new":
      return {
        label: "Новинка",
        bgColor: "#DBEAFE",
        textColor: "#0C4A6E",
      };
    case "price_drop":
      return {
        label: "Снижение цены",
        bgColor: "#FEF3C7",
        textColor: "#78350F",
      };
    case "verified":
      return {
        label: "Проверено",
        bgColor: "#F3F4F6",
        textColor: "#374151",
      };
    default:
      return null;
  };
};

const PropertyPriceStatusBlock = ({
  price,
  pricePerSquareMeter,
  status,
  className,
}: PropertyPriceStatusBlockProps) => {
  const statusConfig = getStatusConfig(status);

  return (
    <div
      className={cn(
        "bg-white border-b border-[#EEEEEE]",
        "px-4 py-3 md:px-6 md:py-4",
        className
      )}
    >
      {/* Main Price */}
      <p
        className="text-[42px] font-bold leading-tight"
        style={{
          fontFamily: "Manrope, sans-serif",
          fontWeight: 700,
          color: "#0F0F0F",
          marginBottom: "8px",
        }}
      >
        {formatPrice(price)} ₽
      </p>

      {/* Price per m² and Status Badge */}
      <div className="flex items-center gap-3 flex-wrap">
        {/* Price per m² */}
        <p
          className="text-base leading-normal"
          style={{
            fontFamily: "Inter, sans-serif",
            fontWeight: 400,
            color: "#616161",
            marginBottom: 0,
          }}
        >
          {formatPrice(pricePerSquareMeter)} ₽/м²
        </p>

        {/* Status Badge */}
        {statusConfig && (
          <span
            className="inline-flex items-center px-3 py-1.5 rounded-md whitespace-nowrap"
            style={{
              backgroundColor: statusConfig.bgColor,
              color: statusConfig.textColor,
              fontFamily: "Inter, sans-serif",
              fontWeight: 500,
              fontSize: "13px",
            }}
          >
            {statusConfig.label}
          </span>
        )}
      </div>
    </div>
  );
};

export default PropertyPriceStatusBlock;


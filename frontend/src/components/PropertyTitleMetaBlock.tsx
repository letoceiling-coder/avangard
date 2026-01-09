import { cn } from "@/lib/utils";

type PropertyType = "квартира" | "апартаменты" | "дом" | "комната";

interface PropertyTitleMetaBlockProps {
  propertyType: PropertyType;
  area: number; // total_area / area_total / square
  floor: number;
  totalFloors: number;
  // Meta fields (optional)
  updatedAt?: string | Date;
  createdAt?: string | Date;
  viewsTotal?: number;
  viewsToday?: number;
  className?: string;
}

const formatPropertyType = (type: PropertyType): string => {
  const typeMap: Record<PropertyType, string> = {
    квартира: "Квартира",
    апартаменты: "Апартаменты",
    дом: "Дом",
    комната: "Комната",
  };
  return typeMap[type] || type;
};

const formatArea = (area: number): string => {
  // Если есть дробная часть, показываем с одним знаком после запятой
  if (area % 1 !== 0) {
    return area.toFixed(1).replace(".", ",");
  }
  return area.toString();
};

const formatFloor = (floor: number, totalFloors: number): string => {
  if (totalFloors === 1) {
    return "1/1 эт.";
  }
  return `${floor}/${totalFloors} эт.`;
};

const formatDate = (date: string | Date): string => {
  try {
    const dateObj = typeof date === "string" ? new Date(date) : date;
    if (isNaN(dateObj.getTime())) return "";

    const now = new Date();
    const diffMs = now.getTime() - dateObj.getTime();
    const diffMins = Math.floor(diffMs / (1000 * 60));
    const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

    // Если меньше часа - показываем минуты
    if (diffMins < 60) {
      if (diffMins < 1) return "только что";
      return `${diffMins} мин назад`;
    }

    // Если меньше суток - показываем часы
    if (diffHours < 24) {
      return `${diffHours} ч назад`;
    }

    // Если меньше недели - показываем дни
    if (diffDays < 7) {
      return `${diffDays} дн назад`;
    }

    // Иначе - дата в формате ДД.ММ.ГГГГ
    return dateObj.toLocaleDateString("ru-RU", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
    });
  } catch {
    return "";
  }
};

const formatViews = (total: number, today: number): string => {
  const parts: string[] = [];
  
  if (total !== undefined && total !== null) {
    parts.push(total.toLocaleString("ru-RU"));
  }
  
  if (today !== undefined && today !== null) {
    parts.push(`сегодня ${today.toLocaleString("ru-RU")}`);
  }
  
  return parts.join(" / ");
};

const PropertyTitleMetaBlock = ({
  propertyType,
  area,
  floor,
  totalFloors,
  updatedAt,
  createdAt,
  viewsTotal,
  viewsToday,
  className,
}: PropertyTitleMetaBlockProps) => {
  const typeText = formatPropertyType(propertyType);
  const areaText = formatArea(area);
  const floorText = formatFloor(floor, totalFloors);

  // Формируем основной заголовок: "{PropertyType}, {Area} м², {Floor}/{Floors} эт."
  const titleText = `${typeText}, ${areaText} м², ${floorText}`;

  // Определяем дату обновления (приоритет: updatedAt > createdAt)
  const updateDate = updatedAt || createdAt;
  const updateDateText = updateDate ? formatDate(updateDate) : null;

  // Формируем мета-строку
  const viewsText = formatViews(viewsTotal || 0, viewsToday || 0);
  const hasMeta = updateDateText || (viewsTotal !== undefined && viewsTotal !== null);

  return (
    <div
      className={cn(
        "px-4 py-2 md:px-6 md:py-3",
        className
      )}
      style={{
        marginTop: 0,
        marginBottom: 0,
      }}
    >
      {/* Main Title */}
      <h1
        className={cn(
          "font-manrope font-semibold text-foreground",
          "line-clamp-2",
          "leading-tight"
        )}
        style={{
          fontSize: "20px",
          fontWeight: 600,
          color: "#0F0F0F",
          lineHeight: "1.3",
          marginBottom: hasMeta ? "6px" : "0",
        }}
      >
        {titleText}
      </h1>

      {/* Meta Line */}
      {hasMeta && (
        <div
          className={cn(
            "flex flex-wrap items-center gap-1.5 md:gap-2",
            "text-xs md:text-sm",
            "text-muted-foreground"
          )}
          style={{
            fontFamily: "Inter, sans-serif",
            fontWeight: 400,
            color: "#616161",
            lineHeight: "1.4",
          }}
        >
          {/* Updated Date */}
          {updateDateText && (
            <>
              <span className="shrink-0">Обновлено: {updateDateText}</span>
              {viewsText && <span className="shrink-0">•</span>}
            </>
          )}

          {/* Views */}
          {viewsText && (
            <span className="shrink-0">
              Просмотров: {viewsText}
            </span>
          )}
        </div>
      )}
    </div>
  );
};

export default PropertyTitleMetaBlock;


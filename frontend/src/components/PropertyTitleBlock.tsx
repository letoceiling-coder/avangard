import { cn } from "@/lib/utils";

type PropertyType = "квартира" | "апартаменты" | "дом" | "комната";

interface PropertyTitleBlockProps {
  rooms: number; // 0 = студия, 1-4, и т.д.
  type: PropertyType;
  squareMeters: number;
  floor: number;
  totalFloors: number;
  buildingName?: string; // 'ЖК Авангард-31'
  city?: string; // 'Москва'
  className?: string;
}

const formatSquareMeters = (squareMeters: number): string => {
  // Если есть дробная часть, показываем с одним знаком после запятой
  if (squareMeters % 1 !== 0) {
    return squareMeters.toFixed(1).replace(".", ",");
  }
  return squareMeters.toString();
};

const formatRooms = (rooms: number): string => {
  if (rooms === 0) {
    return "Студия";
  }
  return `${rooms}-к`;
};

const formatFloor = (floor: number, totalFloors: number): string => {
  if (totalFloors === 1) {
    return "1/1 этаж";
  }
  return `${floor}/${totalFloors} этаж`;
};

const PropertyTitleBlock = ({
  rooms,
  type,
  squareMeters,
  floor,
  totalFloors,
  buildingName,
  city,
  className,
}: PropertyTitleBlockProps) => {
  const roomsText = formatRooms(rooms);
  const squareText = formatSquareMeters(squareMeters);
  const floorText = formatFloor(floor, totalFloors);

  // Формируем основную строку заголовка
  const titleText = `${roomsText} ${type}, ${squareText} м², ${floorText}`;

  // Формируем вторую строку (если есть buildingName или city)
  const secondLineText = buildingName && city 
    ? `ЖК '${buildingName}', ${city}`
    : buildingName 
    ? `ЖК '${buildingName}'`
    : city 
    ? city
    : null;

  return (
    <div
      className={cn(className)}
      style={{
        padding: "16px",
        marginBottom: "4px",
      }}
    >
      {/* Main Title */}
      <h1
        className="break-words"
        style={{
          fontFamily: "Manrope, sans-serif",
          fontWeight: 600,
          fontSize: "20px",
          color: "#0F0F0F",
          lineHeight: "1.3",
          wordBreak: "break-word",
          margin: 0,
        }}
      >
        {titleText}
      </h1>

      {/* Second Line (if buildingName or city exists) */}
      {secondLineText && (
        <p
          className="mt-1 break-words"
          style={{
            fontFamily: "Inter, sans-serif",
            fontWeight: 400,
            fontSize: "14px",
            color: "#616161",
            marginTop: "4px",
            marginBottom: 0,
            lineHeight: "1.4",
            wordBreak: "break-word",
          }}
        >
          {secondLineText}
        </p>
      )}
    </div>
  );
};

export default PropertyTitleBlock;


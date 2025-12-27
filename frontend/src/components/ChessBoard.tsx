import { useState } from "react";
import { Link } from "react-router-dom";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { Calendar, Maximize2 } from "lucide-react";

interface Apartment {
  id: string;
  section: number;
  floor: number;
  number: string;
  rooms: number;
  area: number;
  price: number;
  status: "available" | "booked" | "sold";
  layout?: string;
}

interface ChessBoardProps {
  apartments: Apartment[];
  complexId: string;
}

const statusColors = {
  available: "bg-green-500 hover:bg-green-600 cursor-pointer text-white",
  booked: "bg-amber-500 hover:bg-amber-600 cursor-pointer text-white",
  sold: "bg-muted text-muted-foreground cursor-not-allowed",
};

const statusLabels = {
  available: "Свободна",
  booked: "Забронирована",
  sold: "Продана",
};

const ChessBoard = ({ apartments, complexId }: ChessBoardProps) => {
  const [selectedApartment, setSelectedApartment] = useState<Apartment | null>(null);
  const [filterRooms, setFilterRooms] = useState<number | null>(null);

  // Get unique sections and floors
  const sections = [...new Set(apartments.map((a) => a.section))].sort((a, b) => a - b);
  const floors = [...new Set(apartments.map((a) => a.floor))].sort((a, b) => b - a);

  // Filter apartments
  const filteredApartments = filterRooms
    ? apartments.filter((a) => a.rooms === filterRooms)
    : apartments;

  const getApartment = (section: number, floor: number) => {
    return filteredApartments.find((a) => a.section === section && a.floor === floor);
  };

  const roomOptions = [...new Set(apartments.map((a) => a.rooms))].sort((a, b) => a - b);

  const formatPrice = (price: number) => {
    if (price >= 1000000) {
      return `${(price / 1000000).toFixed(1)} млн`;
    }
    return `${(price / 1000).toFixed(0)} тыс`;
  };

  const getRoomLabel = (rooms: number) => {
    if (rooms === 1) return "Студия";
    return `${rooms}-комн.`;
  };

  return (
    <div className="space-y-6">
      {/* Legend & Filter */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        {/* Legend */}
        <div className="flex flex-wrap items-center gap-4">
          <div className="flex items-center gap-2">
            <div className="w-4 h-4 rounded bg-green-500" />
            <span className="text-sm text-muted-foreground">Свободна</span>
          </div>
          <div className="flex items-center gap-2">
            <div className="w-4 h-4 rounded bg-amber-500" />
            <span className="text-sm text-muted-foreground">Забронирована</span>
          </div>
          <div className="flex items-center gap-2">
            <div className="w-4 h-4 rounded bg-muted" />
            <span className="text-sm text-muted-foreground">Продана</span>
          </div>
        </div>

        {/* Room filter */}
        <div className="flex items-center gap-2">
          <span className="text-sm text-muted-foreground">Комнат:</span>
          <div className="flex gap-1">
            <button
              onClick={() => setFilterRooms(null)}
              className={cn(
                "px-3 py-1.5 rounded-lg text-sm font-medium transition-colors",
                filterRooms === null
                  ? "bg-primary text-primary-foreground"
                  : "bg-muted text-foreground hover:bg-muted/80"
              )}
            >
              Все
            </button>
            {roomOptions.map((rooms) => (
              <button
                key={rooms}
                onClick={() => setFilterRooms(rooms)}
                className={cn(
                  "px-3 py-1.5 rounded-lg text-sm font-medium transition-colors",
                  filterRooms === rooms
                    ? "bg-primary text-primary-foreground"
                    : "bg-muted text-foreground hover:bg-muted/80"
                )}
              >
                {rooms === 1 ? "Ст" : rooms}
              </button>
            ))}
          </div>
        </div>
      </div>

      {/* Chess grid */}
      <div className="overflow-x-auto -mx-4 px-4 pb-2">
        <div className="min-w-[600px]">
          {/* Section headers */}
          <div className="flex mb-2">
            <div className="w-14 flex-shrink-0" />
            {sections.map((section) => (
              <div
                key={section}
                className="flex-1 text-center text-sm font-semibold text-foreground py-2 bg-muted rounded-t-lg mx-0.5"
              >
                Секция {section}
              </div>
            ))}
          </div>

          {/* Floors and apartments */}
          <div className="space-y-1">
            {floors.map((floor) => (
              <div key={floor} className="flex items-stretch">
                {/* Floor label */}
                <div className="w-14 flex-shrink-0 flex items-center justify-center text-sm font-medium text-muted-foreground bg-muted/50 rounded-l-lg">
                  {floor} эт.
                </div>

                {/* Apartments grid */}
                {sections.map((section) => {
                  const apartment = getApartment(section, floor);

                  if (!apartment) {
                    return (
                      <div
                        key={`${section}-${floor}`}
                        className="flex-1 mx-0.5 h-16 bg-border/30 rounded-lg"
                      />
                    );
                  }

                  return (
                    <div
                      key={apartment.id}
                      onClick={() => apartment.status !== "sold" && setSelectedApartment(apartment)}
                      className={cn(
                        "flex-1 mx-0.5 h-16 rounded-lg flex flex-col items-center justify-center transition-all",
                        statusColors[apartment.status],
                        selectedApartment?.id === apartment.id && "ring-2 ring-foreground ring-offset-2 ring-offset-background"
                      )}
                    >
                      <span className="text-xs font-medium opacity-90">
                        {apartment.rooms === 1 ? "Ст" : `${apartment.rooms}к`} • {apartment.area}м²
                      </span>
                      <span className="text-sm font-bold">{formatPrice(apartment.price)} ₽</span>
                    </div>
                  );
                })}
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Selected apartment details */}
      {selectedApartment && (
        <div className="bg-muted/30 rounded-2xl p-6 animate-fade-in border border-border">
          <div className="flex flex-col md:flex-row md:items-start md:justify-between gap-6">
            <div className="space-y-3">
              <div className="flex items-center gap-3">
                <h4 className="text-xl font-semibold text-foreground">
                  {getRoomLabel(selectedApartment.rooms)}, этаж {selectedApartment.floor}
                </h4>
                <span
                  className={cn(
                    "px-2 py-1 rounded-md text-xs font-medium",
                    selectedApartment.status === "available" && "bg-green-100 text-green-700",
                    selectedApartment.status === "booked" && "bg-amber-100 text-amber-700"
                  )}
                >
                  {statusLabels[selectedApartment.status]}
                </span>
              </div>
              
              <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div className="p-3 rounded-xl bg-background">
                  <p className="text-sm text-muted-foreground">Площадь</p>
                  <p className="font-semibold text-foreground">{selectedApartment.area} м²</p>
                </div>
                <div className="p-3 rounded-xl bg-background">
                  <p className="text-sm text-muted-foreground">Цена за м²</p>
                  <p className="font-semibold text-foreground">
                    {Math.round(selectedApartment.price / selectedApartment.area).toLocaleString()} ₽
                  </p>
                </div>
                <div className="p-3 rounded-xl bg-background">
                  <p className="text-sm text-muted-foreground">Секция</p>
                  <p className="font-semibold text-foreground">{selectedApartment.section}</p>
                </div>
                <div className="p-3 rounded-xl bg-background">
                  <p className="text-sm text-muted-foreground">№ квартиры</p>
                  <p className="font-semibold text-foreground">{selectedApartment.number}</p>
                </div>
              </div>
            </div>

            <div className="flex flex-col items-start md:items-end gap-3">
              <div className="text-right">
                <p className="text-sm text-muted-foreground">Стоимость</p>
                <p className="text-2xl font-bold text-primary">
                  {(selectedApartment.price / 1000000).toFixed(2)} млн ₽
                </p>
              </div>
              <div className="flex gap-2">
                <Button
                  variant="secondary"
                  size="sm"
                  leftIcon={<Calendar className="w-4 h-4" />}
                >
                  На просмотр
                </Button>
                <Button
                  variant="primary"
                  size="sm"
                  asChild
                >
                  <Link to={`/property/${selectedApartment.id}`}>
                    Подробнее
                  </Link>
                </Button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default ChessBoard;

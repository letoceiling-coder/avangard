import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { 
  Search, 
  Home, 
  Building2, 
  Car, 
  Trees, 
  Store, 
  Wrench,
  MapPin,
  SlidersHorizontal
} from "lucide-react";
import { Button } from "./ui/button";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "./ui/dialog";

interface SearchCardProps {
  className?: string;
}

const propertyTypes = [
  { id: "apartment", label: "Квартиры", icon: Home },
  { id: "parking", label: "Паркинги", icon: Car },
  { id: "house-land", label: "Дома с участками", icon: Building2 },
  { id: "land", label: "Участки", icon: Trees },
  { id: "contractors", label: "Подрядчики", icon: Wrench },
  { id: "commercial", label: "Коммерция", icon: Store },
];

const quickFilters = [
  { id: "room-type", label: "Тип" },
  { id: "price", label: "Цена" },
  { id: "deadline", label: "Срок сдачи" },
  { id: "all-filters", label: "Все фильтры", icon: SlidersHorizontal },
];

const SearchCard = ({ className }: SearchCardProps) => {
  const navigate = useNavigate();
  const [searchQuery, setSearchQuery] = useState("");
  const [activePropertyType, setActivePropertyType] = useState<string>("apartment");
  const [filtersOpen, setFiltersOpen] = useState(false);
  const [propertiesCount] = useState(1247); // Mock count

  const handleSearch = () => {
    const params = new URLSearchParams();
    if (searchQuery) params.set("search", searchQuery);
    if (activePropertyType) params.set("type", activePropertyType);
    
    navigate(`/catalog?${params.toString()}`);
  };

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === "Enter") handleSearch();
  };

  return (
    <div className={`flex items-center flex-wrap gap-2 px-4 py-3 bg-white rounded-xl shadow-sm w-full max-w-6xl mx-auto ${className || ""}`}>
      {/* Property Types */}
      <div className="flex gap-1.5 items-center flex-shrink-0">
        {propertyTypes.map((type) => {
          const Icon = type.icon;
          const isActive = activePropertyType === type.id;
          return (
            <button
              key={type.id}
              onClick={() => setActivePropertyType(type.id)}
              className={`flex items-center gap-1.5 px-3 py-1.5 text-xs rounded-full transition ${
                isActive
                  ? "bg-primary text-white font-medium"
                  : "bg-muted/10 text-muted-foreground hover:bg-muted/20"
              }`}
            >
              <Icon className="w-3.5 h-3.5" />
              <span className="hidden sm:inline">{type.label}</span>
            </button>
          );
        })}
      </div>

      {/* Search Input */}
      <div className="flex-1 relative min-w-[180px]">
        <Search className="absolute left-2.5 top-1/2 -translate-y-1/2 text-muted-foreground pointer-events-none w-3.5 h-3.5" />
        <input
          type="text"
          value={searchQuery}
          onChange={(e) => setSearchQuery(e.target.value)}
          onKeyDown={handleKeyDown}
          placeholder="Метро, район, локация, ЖК, улица, застройщик, банк"
          className="w-full h-9 px-9 py-1.5 rounded-lg border border-muted text-sm shadow-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
        />
      </div>

      {/* Quick Filters */}
      <div className="flex gap-1.5 items-center flex-shrink-0">
        {quickFilters.map((filter) => {
          const Icon = filter.icon;
          if (filter.id === "all-filters") {
            return (
              <Dialog key={filter.id} open={filtersOpen} onOpenChange={setFiltersOpen}>
                <DialogTrigger asChild>
                  <button className="flex items-center gap-1 rounded-lg border border-muted px-2.5 py-1.5 text-xs bg-muted/5 hover:bg-muted/10 transition">
                    {Icon && <Icon className="w-3.5 h-3.5 text-muted-foreground" />}
                    <span className="text-muted-foreground">{filter.label}</span>
                  </button>
                </DialogTrigger>
                <DialogContent className="sm:max-w-md">
                  <DialogHeader>
                    <DialogTitle>Все фильтры</DialogTitle>
                  </DialogHeader>
                  <div className="py-4">
                    <p className="text-sm text-muted-foreground">Расширенные фильтры будут здесь</p>
                  </div>
                </DialogContent>
              </Dialog>
            );
          }
          return (
            <button
              key={filter.id}
              className="rounded-lg border border-muted px-2.5 py-1.5 text-xs bg-muted/5 hover:bg-muted/10 transition text-muted-foreground"
            >
              {filter.label}
            </button>
          );
        })}
      </div>

      {/* Action Buttons */}
      <div className="flex gap-1.5 items-center flex-shrink-0">
        <Button
          variant="outline"
          size="sm"
          onClick={() => navigate('/catalog?view=map')}
          className="flex items-center gap-1.5 h-9 px-3 py-1.5 rounded-lg bg-muted/10 hover:bg-muted/20 transition border-0 text-xs"
        >
          <MapPin className="w-3.5 h-3.5" />
          <span className="hidden sm:inline">На карте</span>
        </Button>

        <Button
          onClick={handleSearch}
          className="flex items-center gap-1.5 h-9 px-3 py-1.5 rounded-lg bg-primary text-white hover:bg-primary/90 transition shadow-sm text-xs"
        >
          <span>Найдено {propertiesCount}</span>
        </Button>
      </div>
    </div>
  );
};

export default SearchCard;

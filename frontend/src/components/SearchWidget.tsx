import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { 
  Search, 
  SlidersHorizontal, 
  Home, 
  Building2, 
  Store, 
  X,
  Paintbrush,
  Layers,
  Ruler,
  Calendar,
  Trees,
  DoorOpen,
  DollarSign
} from "lucide-react";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Slider } from "@/components/ui/slider";

type SearchTab = "buy" | "rent";

interface SearchWidgetProps {
  className?: string;
  variant?: "default" | "hero";
}

const searchTabs: { id: SearchTab; label: string }[] = [
  { id: "buy", label: "Покупка" },
  { id: "rent", label: "Аренда" },
];

const propertyTypes = [
  { id: "apartment", label: "Квартира", icon: Building2 },
  { id: "room", label: "Комнаты", icon: DoorOpen },
  { id: "house", label: "Дом", icon: Home },
  { id: "land", label: "Участок", icon: Trees },
  { id: "commercial", label: "Коммерция", icon: Store },
];

const finishingOptions = [
  { id: "none", label: "Без отделки" },
  { id: "rough", label: "Черновая" },
  { id: "prefinish", label: "Предчистовая" },
  { id: "finish", label: "Чистовая" },
  { id: "design", label: "Дизайнерский ремонт" },
];

const SearchWidget = ({ className, variant = "default" }: SearchWidgetProps) => {
  const navigate = useNavigate();
  const [searchQuery, setSearchQuery] = useState("");
  const [activeTab, setActiveTab] = useState<SearchTab>("buy");
  const [selectedPropertyType, setSelectedPropertyType] = useState<string>("apartment");
  const [priceMin, setPriceMin] = useState<string>("");
  const [priceMax, setPriceMax] = useState<string>("");
  
  // Extended filters
  const [selectedFinishing, setSelectedFinishing] = useState<string[]>([]);
  const [floorRange, setFloorRange] = useState<[number, number]>([1, 30]);
  const [areaRange, setAreaRange] = useState<[number, number]>([0, 200]);
  const [yearRange, setYearRange] = useState<[number, number]>([2015, 2025]);
  const [extendedFiltersOpen, setExtendedFiltersOpen] = useState(false);

  const hasExtendedFilters = 
    selectedFinishing.length > 0 || 
    floorRange[0] > 1 || floorRange[1] < 30 ||
    areaRange[0] > 0 || areaRange[1] < 200 ||
    yearRange[0] > 2015 || yearRange[1] < 2025;

  const activeFiltersCount = 
    (selectedFinishing.length > 0 ? 1 : 0) + 
    (floorRange[0] > 1 || floorRange[1] < 30 ? 1 : 0) +
    (areaRange[0] > 0 || areaRange[1] < 200 ? 1 : 0) +
    (yearRange[0] > 2015 || yearRange[1] < 2025 ? 1 : 0);

  const handleSearch = () => {
    const params = new URLSearchParams();
    if (searchQuery) params.set("search", searchQuery);
    if (selectedPropertyType) params.set("type", selectedPropertyType);
    if (priceMin) params.set("priceMin", priceMin);
    if (priceMax) params.set("priceMax", priceMax);
    if (selectedFinishing.length) params.set("finishing", selectedFinishing.join(","));
    if (floorRange[0] > 1) params.set("minFloor", floorRange[0].toString());
    if (floorRange[1] < 30) params.set("maxFloor", floorRange[1].toString());
    if (areaRange[0] > 0) params.set("minArea", areaRange[0].toString());
    if (areaRange[1] < 200) params.set("maxArea", areaRange[1].toString());
    if (yearRange[0] > 2015) params.set("minYear", yearRange[0].toString());
    if (yearRange[1] < 2025) params.set("maxYear", yearRange[1].toString());
    params.set("deal", activeTab === "buy" ? "buy" : "rent");
    navigate(`/catalog?${params.toString()}`);
  };

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === "Enter") handleSearch();
  };

  const toggleFinishing = (finishing: string) => {
    setSelectedFinishing((prev) =>
      prev.includes(finishing) ? prev.filter((f) => f !== finishing) : [...prev, finishing]
    );
  };

  const resetExtendedFilters = () => {
    setSelectedFinishing([]);
    setFloorRange([1, 30]);
    setAreaRange([0, 200]);
    setYearRange([2015, 2025]);
  };

  const isHero = variant === "hero";

  return (
    <div 
      className={`w-full ${isHero ? '' : 'backdrop-blur-sm'} ${className}`}
      style={!isHero ? { 
        background: 'linear-gradient(180deg, #FFFFFF 0%, #F9FBFF 100%)',
        boxShadow: '0 2px 16px rgba(0, 0, 0, 0.05)',
        borderRadius: '24px'
      } : {}}
    >
      <div className={isHero ? 'p-0' : 'p-4 md:p-5'}>
        {/* Row 1: Tabs - Покупка / Аренда */}
        <div className="flex justify-start gap-2 mb-4">
          {searchTabs.map((tab) => (
            <button
              key={tab.id}
              onClick={() => setActiveTab(tab.id)}
              className={`rounded-full px-4 py-2 text-[13px] font-medium transition-colors duration-200 ${
                activeTab === tab.id
                  ? "bg-primary text-white"
                  : "bg-muted/20 text-muted-foreground hover:bg-muted/30"
              }`}
            >
              {tab.label}
            </button>
          ))}
        </div>

        {/* Row 2: Property Type Selector - Scrollable row */}
        <div className="flex gap-2 mb-4 overflow-x-auto scrollbar-hide scroll-smooth" style={{ scrollSnapType: 'x mandatory' }}>
          {propertyTypes.map((type) => {
            const Icon = type.icon;
            const isActive = selectedPropertyType === type.id;
            return (
              <button
                key={type.id}
                onClick={() => setSelectedPropertyType(type.id)}
                className={`rounded-full border px-4 py-2 flex items-center gap-2 whitespace-nowrap flex-shrink-0 transition-all duration-200 ${
                  isActive
                    ? "bg-primary text-white border-primary"
                    : "bg-transparent text-muted-foreground border-border hover:border-primary/50"
                }`}
                style={{ scrollSnapAlign: 'start' }}
              >
                <Icon 
                  className={`w-4 h-4 transition-colors duration-200 ${isActive ? 'text-white' : 'text-muted-foreground'}`} 
                  strokeWidth={isActive ? 2 : 1.5} 
                />
                <span className={`text-sm font-medium ${isActive ? 'text-white' : 'text-muted-foreground'}`}>{type.label}</span>
              </button>
            );
          })}
        </div>

        {/* Row 3: Main Filters - Desktop: одна строка */}
        <div className="hidden md:flex items-center gap-3">
          {/* Address Input Card */}
          <div className="flex-1 min-w-[280px] relative">
            <div className="bg-white rounded-xl shadow-sm border border-border/50 p-1">
              <div className="relative">
                <div className="absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none z-10">
                  <Search className="w-[18px] h-[18px] text-muted-foreground/60" strokeWidth={1.5} />
                </div>
                <input
                  type="text"
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  onKeyDown={handleKeyDown}
                  placeholder="Адрес, ЖК, район..."
                  className="w-full h-[50px] pl-10 pr-4 rounded-lg bg-transparent text-[15px] placeholder:text-muted-foreground/55 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary/20 border border-transparent focus:border-primary/25"
                />
              </div>
            </div>
          </div>

          {/* Price From Card */}
          <div className="min-w-[140px] relative">
            <div className="bg-white rounded-xl shadow-sm border border-border/50 p-1">
              <div className="relative">
                <div className="absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none z-10">
                  <DollarSign className="w-[18px] h-[18px] text-muted-foreground/60" strokeWidth={1.5} />
                </div>
                <input
                  type="text"
                  value={priceMin}
                  onChange={(e) => setPriceMin(e.target.value.replace(/\D/g, ""))}
                  placeholder="Цена от"
                  className="w-full h-[48px] pl-10 pr-3.5 rounded-lg bg-transparent text-[14px] placeholder:text-muted-foreground/55 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary/20 border border-transparent focus:border-primary/25"
                />
              </div>
            </div>
          </div>

          {/* Price To Card */}
          <div className="min-w-[140px] relative">
            <div className="bg-white rounded-xl shadow-sm border border-border/50 p-1">
              <div className="relative">
                <div className="absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none z-10">
                  <DollarSign className="w-[18px] h-[18px] text-muted-foreground/60" strokeWidth={1.5} />
                </div>
                <input
                  type="text"
                  value={priceMax}
                  onChange={(e) => setPriceMax(e.target.value.replace(/\D/g, ""))}
                  placeholder="Цена до"
                  className="w-full h-[48px] pl-10 pr-3.5 rounded-lg bg-transparent text-[14px] placeholder:text-muted-foreground/55 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary/20 border border-transparent focus:border-primary/25"
                />
              </div>
            </div>
          </div>

          {/* Extended Filters Button - "Ещё" */}
          <Dialog open={extendedFiltersOpen} onOpenChange={setExtendedFiltersOpen}>
            <DialogTrigger asChild>
              <button
                className="rounded-md border px-4 py-2 text-sm text-muted-foreground hover:bg-muted/50 transition-colors"
              >
                Ещё
              </button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-md max-h-[85vh] overflow-y-auto">
              <DialogHeader>
                <DialogTitle className="flex items-center gap-2 text-[17px] font-semibold">
                  <SlidersHorizontal className="w-5 h-5 text-primary" strokeWidth={1.5} />
                  Расширенные фильтры
                </DialogTitle>
              </DialogHeader>
              
              <div className="space-y-5 py-4">
                {/* Finishing Filter */}
                <div className="space-y-3">
                  <div className="flex items-center gap-2 text-[14px] font-medium text-foreground">
                    <Paintbrush className="w-4 h-4 text-primary" strokeWidth={1.5} />
                    Отделка
                  </div>
                  <div className="flex flex-wrap gap-2">
                    {finishingOptions.map((option) => {
                      const isSelected = selectedFinishing.includes(option.id);
                      return (
                        <button
                          key={option.id}
                          onClick={() => toggleFinishing(option.id)}
                          className={`px-3 py-2 rounded-lg text-[13px] font-medium transition-all duration-200 ${
                            isSelected 
                              ? "bg-primary text-white" 
                              : "bg-muted/40 text-muted-foreground hover:bg-muted/60 hover:text-foreground"
                          }`}
                        >
                          {option.label}
                        </button>
                      );
                    })}
                  </div>
                </div>

                {/* Floor Filter */}
                <div className="space-y-3">
                  <div className="flex items-center gap-2 text-[14px] font-medium text-foreground">
                    <Layers className="w-4 h-4 text-primary" strokeWidth={1.5} />
                    Этаж
                  </div>
                  <div className="flex items-center gap-3 mb-2">
                    <div className="flex-1">
                      <input
                        type="number"
                        value={floorRange[0]}
                        onChange={(e) => setFloorRange([parseInt(e.target.value) || 1, floorRange[1]])}
                        min={1}
                        max={floorRange[1]}
                        placeholder="от"
                        className="w-full h-11 px-3 rounded-lg bg-muted/40 text-[14px] focus:outline-none focus:bg-muted/60 focus:ring-2 focus:ring-primary/15 border-0"
                      />
                    </div>
                    <span className="text-muted-foreground">—</span>
                    <div className="flex-1">
                      <input
                        type="number"
                        value={floorRange[1]}
                        onChange={(e) => setFloorRange([floorRange[0], parseInt(e.target.value) || 30])}
                        min={floorRange[0]}
                        max={50}
                        placeholder="до"
                        className="w-full h-11 px-3 rounded-lg bg-muted/40 text-[14px] focus:outline-none focus:bg-muted/60 focus:ring-2 focus:ring-primary/15 border-0"
                      />
                    </div>
                  </div>
                  <Slider
                    value={floorRange}
                    onValueChange={(value) => setFloorRange(value as [number, number])}
                    min={1}
                    max={30}
                    step={1}
                  />
                </div>

                {/* Area Filter */}
                <div className="space-y-3">
                  <div className="flex items-center gap-2 text-[14px] font-medium text-foreground">
                    <Ruler className="w-4 h-4 text-primary" strokeWidth={1.5} />
                    Площадь, м²
                  </div>
                  <div className="flex items-center gap-3 mb-2">
                    <div className="flex-1">
                      <input
                        type="number"
                        value={areaRange[0]}
                        onChange={(e) => setAreaRange([parseInt(e.target.value) || 0, areaRange[1]])}
                        min={0}
                        max={areaRange[1]}
                        placeholder="от"
                        className="w-full h-11 px-3 rounded-lg bg-muted/40 text-[14px] focus:outline-none focus:bg-muted/60 focus:ring-2 focus:ring-primary/15 border-0"
                      />
                    </div>
                    <span className="text-muted-foreground">—</span>
                    <div className="flex-1">
                      <input
                        type="number"
                        value={areaRange[1]}
                        onChange={(e) => setAreaRange([areaRange[0], parseInt(e.target.value) || 200])}
                        min={areaRange[0]}
                        max={500}
                        placeholder="до"
                        className="w-full h-11 px-3 rounded-lg bg-muted/40 text-[14px] focus:outline-none focus:bg-muted/60 focus:ring-2 focus:ring-primary/15 border-0"
                      />
                    </div>
                  </div>
                  <Slider
                    value={areaRange}
                    onValueChange={(value) => setAreaRange(value as [number, number])}
                    min={0}
                    max={200}
                    step={5}
                  />
                </div>

                {/* Year Built Filter */}
                <div className="space-y-3">
                  <div className="flex items-center gap-2 text-[14px] font-medium text-foreground">
                    <Calendar className="w-4 h-4 text-primary" strokeWidth={1.5} />
                    Год сдачи
                  </div>
                  <div className="flex items-center gap-3 mb-2">
                    <div className="flex-1">
                      <input
                        type="number"
                        value={yearRange[0]}
                        onChange={(e) => setYearRange([parseInt(e.target.value) || 2015, yearRange[1]])}
                        min={1950}
                        max={yearRange[1]}
                        placeholder="от"
                        className="w-full h-11 px-3 rounded-lg bg-muted/40 text-[14px] focus:outline-none focus:bg-muted/60 focus:ring-2 focus:ring-primary/15 border-0"
                      />
                    </div>
                    <span className="text-muted-foreground">—</span>
                    <div className="flex-1">
                      <input
                        type="number"
                        value={yearRange[1]}
                        onChange={(e) => setYearRange([yearRange[0], parseInt(e.target.value) || 2025])}
                        min={yearRange[0]}
                        max={2030}
                        placeholder="до"
                        className="w-full h-11 px-3 rounded-lg bg-muted/40 text-[14px] focus:outline-none focus:bg-muted/60 focus:ring-2 focus:ring-primary/15 border-0"
                      />
                    </div>
                  </div>
                  <Slider
                    value={yearRange}
                    onValueChange={(value) => setYearRange(value as [number, number])}
                    min={2015}
                    max={2025}
                    step={1}
                  />
                </div>
              </div>

              {/* Footer Actions */}
              <div className="flex items-center gap-3 pt-4 border-t border-border/30">
                <Button
                  variant="ghost"
                  onClick={resetExtendedFilters}
                  className="flex-1 h-11 rounded-xl text-[14px] font-medium"
                  disabled={!hasExtendedFilters}
                >
                  Сбросить
                </Button>
                <Button
                  variant="primary"
                  onClick={() => setExtendedFiltersOpen(false)}
                  className="flex-1 h-11 rounded-xl text-[14px] font-medium"
                >
                  Применить
                </Button>
              </div>
            </DialogContent>
          </Dialog>

          {/* Primary CTA Button - Справа от фильтров */}
          <button
            onClick={handleSearch}
            className="bg-primary text-white rounded-xl px-6 py-3 text-base font-semibold shadow-sm hover:bg-primary/90 transition-colors min-h-[44px] whitespace-nowrap"
            aria-label="Найти недвижимость по заданным параметрам"
          >
            Найти
          </button>
        </div>

        {/* Mobile Layout - Vertical: инпуты в столбик */}
        <div className="md:hidden space-y-3">
          {/* Inputs Container - Vertical column */}
          <div className="flex flex-col gap-3">
            {/* Address Input Card */}
            <div className="w-full relative">
              <div className="bg-white rounded-xl shadow-sm border border-border/50 p-1">
                <div className="relative">
                  <div className="absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none z-10">
                    <Search className="w-[18px] h-[18px] text-muted-foreground/60" strokeWidth={1.5} />
                  </div>
                  <input
                    type="text"
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    onKeyDown={handleKeyDown}
                    placeholder="Адрес, ЖК, район..."
                    className="w-full h-[48px] pl-10 pr-4 rounded-lg bg-transparent text-[15px] placeholder:text-muted-foreground/55 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary/20 border border-transparent focus:border-primary/25"
                  />
                </div>
              </div>
            </div>

            {/* Price Inputs Row */}
            <div className="flex gap-3">
              {/* Price From Card */}
              <div className="flex-1 relative">
                <div className="bg-white rounded-xl shadow-sm border border-border/50 p-1">
                  <div className="relative">
                    <div className="absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none z-10">
                      <DollarSign className="w-[18px] h-[18px] text-muted-foreground/60" strokeWidth={1.5} />
                    </div>
                    <input
                      type="text"
                      value={priceMin}
                      onChange={(e) => setPriceMin(e.target.value.replace(/\D/g, ""))}
                      placeholder="Цена от"
                      className="w-full h-[48px] pl-10 pr-3.5 rounded-lg bg-transparent text-[14px] placeholder:text-muted-foreground/55 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary/20 border border-transparent focus:border-primary/25"
                    />
                  </div>
                </div>
              </div>

              {/* Price To Card */}
              <div className="flex-1 relative">
                <div className="bg-white rounded-xl shadow-sm border border-border/50 p-1">
                  <div className="relative">
                    <div className="absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none z-10">
                      <DollarSign className="w-[18px] h-[18px] text-muted-foreground/60" strokeWidth={1.5} />
                    </div>
                    <input
                      type="text"
                      value={priceMax}
                      onChange={(e) => setPriceMax(e.target.value.replace(/\D/g, ""))}
                      placeholder="Цена до"
                      className="w-full h-[48px] pl-10 pr-3.5 rounded-lg bg-transparent text-[14px] placeholder:text-muted-foreground/55 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary/20 border border-transparent focus:border-primary/25"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Filters + CTA Row */}
          <div className="flex gap-2">
            {/* Extended Filters Button - "Ещё" */}
            <Dialog open={extendedFiltersOpen} onOpenChange={setExtendedFiltersOpen}>
              <DialogTrigger asChild>
                <button
                  className="rounded-md border px-4 py-2 text-sm text-muted-foreground hover:bg-muted/50 transition-colors"
                >
                  Ещё
                </button>
              </DialogTrigger>
            </Dialog>

            {/* Primary CTA Button - Full width на мобильном */}
            <button
              onClick={handleSearch}
              className="w-full bg-primary text-white rounded-xl px-6 py-3 text-base font-semibold shadow-sm hover:bg-primary/90 transition-colors min-h-[44px]"
              aria-label="Найти недвижимость по заданным параметрам"
            >
              Найти
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default SearchWidget;
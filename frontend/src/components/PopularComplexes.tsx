import { useRef, useMemo } from "react";
import { ChevronLeft, ChevronRight } from "lucide-react";
import { Button } from "@/components/ui/button";
import BuildingCard, { Building } from "./BuildingCard";
import { useRegion } from "@/hooks/useRegion";

// Mock data per region
const mockBuildingsByRegion: Record<string, Building[]> = {
  belgorod: [
    {
      id: "blg-1",
      name: "ЖК «Белый город»",
      image: "https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=800&h=600&fit=crop",
      location: "Ленинский район",
      priceFrom: 2800000,
      priceTo: 8500000,
      status: "completed",
      area: "35-120 м²",
      rooms: "1-4",
      floors: 25,
    },
    {
      id: "blg-2",
      name: "ЖК «Империал»",
      image: "https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?w=800&h=600&fit=crop",
      location: "Центральный район",
      priceFrom: 5200000,
      priceTo: 15000000,
      status: "construction",
      area: "45-180 м²",
      rooms: "1-5",
      floors: 32,
    },
    {
      id: "blg-3",
      name: "ЖК «Современник»",
      image: "https://images.unsplash.com/photo-1515263487990-61b07816b324?w=800&h=600&fit=crop",
      location: "Харьковская гора",
      priceFrom: 3400000,
      priceTo: 9200000,
      status: "completed",
      area: "28-95 м²",
      rooms: "Студия-3",
      floors: 18,
    },
    {
      id: "blg-4",
      name: "ЖК «Новая высота»",
      image: "https://images.unsplash.com/photo-1460317442991-0ec209397118?w=800&h=600&fit=crop",
      location: "Северный район",
      priceFrom: 4100000,
      priceTo: 11000000,
      status: "I кв. 2025",
      area: "40-140 м²",
      rooms: "1-4",
      floors: 22,
    },
    {
      id: "blg-5",
      name: "ЖК «Парковый»",
      image: "https://images.unsplash.com/photo-1567496898669-ee935f5f647a?w=800&h=600&fit=crop",
      location: "Западный район",
      priceFrom: 3900000,
      priceTo: 8800000,
      status: "completed",
      area: "32-110 м²",
      rooms: "1-4",
      floors: 16,
    },
    {
      id: "blg-6",
      name: "ЖК «Центральный»",
      image: "https://images.unsplash.com/photo-1448630360428-65456885c650?w=800&h=600&fit=crop",
      location: "Центральный район",
      priceFrom: 6800000,
      priceTo: 18000000,
      status: "II кв. 2025",
      area: "55-200 м²",
      rooms: "2-5",
      floors: 28,
    },
  ],
  kursk: [
    {
      id: "krs-1",
      name: "ЖК «Курский»",
      image: "https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=800&h=600&fit=crop",
      location: "Центральный округ",
      priceFrom: 2400000,
      priceTo: 7200000,
      status: "completed",
      area: "30-100 м²",
      rooms: "1-4",
      floors: 20,
    },
    {
      id: "krs-2",
      name: "ЖК «Соловьи»",
      image: "https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=800&h=600&fit=crop",
      location: "Железнодорожный округ",
      priceFrom: 3100000,
      priceTo: 9500000,
      status: "construction",
      area: "38-130 м²",
      rooms: "1-4",
      floors: 24,
    },
    {
      id: "krs-3",
      name: "ЖК «Северный парк»",
      image: "https://images.unsplash.com/photo-1574362848149-11496d93a7c7?w=800&h=600&fit=crop",
      location: "Северо-западный округ",
      priceFrom: 2800000,
      priceTo: 8000000,
      status: "III кв. 2025",
      area: "35-115 м²",
      rooms: "Студия-4",
      floors: 18,
    },
    {
      id: "krs-4",
      name: "ЖК «Тускарь»",
      image: "https://images.unsplash.com/photo-1580587771525-78b9dba3b914?w=800&h=600&fit=crop",
      location: "Сеймский округ",
      priceFrom: 3500000,
      priceTo: 10500000,
      status: "completed",
      area: "42-150 м²",
      rooms: "1-5",
      floors: 22,
    },
    {
      id: "krs-5",
      name: "ЖК «Ямская слобода»",
      image: "https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=800&h=600&fit=crop",
      location: "Центральный округ",
      priceFrom: 4200000,
      priceTo: 12000000,
      status: "construction",
      area: "48-170 м²",
      rooms: "2-5",
      floors: 26,
    },
  ],
  voronezh: [
    {
      id: "vrn-1",
      name: "ЖК «Острова»",
      image: "https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=800&h=600&fit=crop",
      location: "Коминтерновский район",
      priceFrom: 3200000,
      priceTo: 9800000,
      status: "completed",
      area: "36-125 м²",
      rooms: "1-4",
      floors: 24,
    },
    {
      id: "vrn-2",
      name: "ЖК «Черноземье»",
      image: "https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=800&h=600&fit=crop",
      location: "Советский район",
      priceFrom: 2900000,
      priceTo: 8500000,
      status: "construction",
      area: "32-110 м²",
      rooms: "Студия-4",
      floors: 20,
    },
    {
      id: "vrn-3",
      name: "ЖК «Ботанический сад»",
      image: "https://images.unsplash.com/photo-1600566753086-00f18fb6b3ea?w=800&h=600&fit=crop",
      location: "Центральный район",
      priceFrom: 5500000,
      priceTo: 16000000,
      status: "II кв. 2025",
      area: "50-180 м²",
      rooms: "2-5",
      floors: 30,
    },
    {
      id: "vrn-4",
      name: "ЖК «Северная корона»",
      image: "https://images.unsplash.com/photo-1600585154526-990dced4db0d?w=800&h=600&fit=crop",
      location: "Железнодорожный район",
      priceFrom: 2600000,
      priceTo: 7500000,
      status: "completed",
      area: "28-95 м²",
      rooms: "1-3",
      floors: 17,
    },
    {
      id: "vrn-5",
      name: "ЖК «Петровский»",
      image: "https://images.unsplash.com/photo-1600047509807-ba8f99d2cdde?w=800&h=600&fit=crop",
      location: "Ленинский район",
      priceFrom: 3800000,
      priceTo: 11500000,
      status: "construction",
      area: "40-145 м²",
      rooms: "1-5",
      floors: 25,
    },
    {
      id: "vrn-6",
      name: "ЖК «Галерея»",
      image: "https://images.unsplash.com/photo-1600573472550-8090b5e0745e?w=800&h=600&fit=crop",
      location: "Центральный район",
      priceFrom: 6200000,
      priceTo: 19000000,
      status: "IV кв. 2025",
      area: "55-220 м²",
      rooms: "2-6",
      floors: 32,
    },
    {
      id: "vrn-7",
      name: "ЖК «Левый берег»",
      image: "https://images.unsplash.com/photo-1600566752355-35792bedcfea?w=800&h=600&fit=crop",
      location: "Левобережный район",
      priceFrom: 2200000,
      priceTo: 6500000,
      status: "completed",
      area: "25-85 м²",
      rooms: "Студия-3",
      floors: 14,
    },
  ],
};

const PopularComplexes = () => {
  const sliderRef = useRef<HTMLDivElement>(null);
  const { selectedRegion, regionName } = useRegion();

  const buildings = useMemo(() => {
    return mockBuildingsByRegion[selectedRegion] || mockBuildingsByRegion.belgorod;
  }, [selectedRegion]);

  const scrollSlider = (direction: "left" | "right") => {
    if (sliderRef.current) {
      const cardWidth = 300;
      const gap = 16;
      const scrollAmount = cardWidth + gap;
      sliderRef.current.scrollBy({
        left: direction === "left" ? -scrollAmount : scrollAmount,
        behavior: "smooth",
      });
    }
  };

  return (
    <section className="py-10 md:py-16 bg-gradient-to-b from-background via-background to-muted/20">
      <div className="container mx-auto px-4">
        {/* Header - more breathing room */}
        <div className="flex items-center justify-between mb-8">
          <div className="flex flex-col gap-2">
            <div className="flex items-center gap-3 flex-wrap">
              <h2 className="text-2xl md:text-3xl font-bold text-foreground tracking-tight">
                Популярные ЖК в {regionName}
              </h2>
              <span className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-primary/10 text-primary text-xs font-medium rounded-full">
                <span className="w-1.5 h-1.5 bg-primary rounded-full animate-pulse"></span>
                Топ по просмотрам
              </span>
            </div>
            <p className="text-sm text-muted-foreground/60 font-normal max-w-md">
              Подборка жилых комплексов с актуальными предложениями от застройщиков
            </p>
          </div>
          <div className="hidden lg:flex items-center gap-2">
            <Button
              variant="outline"
              size="icon"
              onClick={() => scrollSlider("left")}
              className="w-11 h-11 rounded-full border-border/60 hover:border-primary hover:bg-primary/5 hover:text-primary transition-all duration-200"
            >
              <ChevronLeft className="w-5 h-5" />
            </Button>
            <Button
              variant="outline"
              size="icon"
              onClick={() => scrollSlider("right")}
              className="w-11 h-11 rounded-full border-border/60 hover:border-primary hover:bg-primary/5 hover:text-primary transition-all duration-200"
            >
              <ChevronRight className="w-5 h-5" />
            </Button>
          </div>
        </div>

        {/* Grid Layout - Desktop */}
        <div className="hidden sm:grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
          {buildings.map((building, index) => (
            <BuildingCard key={building.id} building={building} index={index} />
          ))}
        </div>

        {/* Slider - Mobile */}
        <div
          ref={sliderRef}
          className="sm:hidden flex gap-4 overflow-x-auto scrollbar-hide scroll-smooth pb-6 -mx-4 px-4 snap-x snap-mandatory"
          style={{ scrollbarWidth: "none", msOverflowStyle: "none" }}
        >
          {buildings.map((building, index) => (
            <div
              key={building.id}
              className="flex-shrink-0 w-[280px] snap-start"
            >
              <BuildingCard building={building} index={index} />
            </div>
          ))}
        </div>

        {/* Mobile Navigation Hint */}
        <div className="sm:hidden flex justify-center mt-4">
          <div className="flex gap-2">
            <Button
              variant="outline"
              size="icon"
              onClick={() => scrollSlider("left")}
              className="w-10 h-10 rounded-full"
            >
              <ChevronLeft className="w-5 h-5" />
            </Button>
            <Button
              variant="outline"
              size="icon"
              onClick={() => scrollSlider("right")}
              className="w-10 h-10 rounded-full"
            >
              <ChevronRight className="w-5 h-5" />
            </Button>
          </div>
        </div>
      </div>
    </section>
  );
};

export default PopularComplexes;

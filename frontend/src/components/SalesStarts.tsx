import { useRef } from "react";
import { Link } from "react-router-dom";
import { ChevronLeft, ChevronRight, ArrowRight, Heart } from "lucide-react";
import { Button } from "@/components/ui/button";
import { useFavorites } from "@/hooks/useFavorites";
import { toast } from "sonner";

interface SalesStartItem {
  id: string;
  name: string;
  image: string;
  priceFrom: number;
  completion: string;
}

const mockSalesStarts: SalesStartItem[] = [
  {
    id: "ss-1",
    name: "ЖК «Весенний»",
    image: "https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?w=1200&h=800&fit=crop",
    priceFrom: 3200000,
    completion: "III кв. 2025",
  },
  {
    id: "ss-2",
    name: "ЖК «Солнечный»",
    image: "https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=1200&h=800&fit=crop",
    priceFrom: 2800000,
    completion: "IV кв. 2025",
  },
  {
    id: "ss-3",
    name: "ЖК «Новый горизонт»",
    image: "https://images.unsplash.com/photo-1515263487990-61b07816b324?w=1200&h=800&fit=crop",
    priceFrom: 4500000,
    completion: "II кв. 2025",
  },
  {
    id: "ss-4",
    name: "ЖК «Панорама»",
    image: "https://images.unsplash.com/photo-1460317442991-0ec209397118?w=1200&h=800&fit=crop",
    priceFrom: 3800000,
    completion: "I кв. 2026",
  },
  {
    id: "ss-5",
    name: "ЖК «Лесной»",
    image: "https://images.unsplash.com/photo-1567496898669-ee935f5f647a?w=1200&h=800&fit=crop",
    priceFrom: 2500000,
    completion: "III кв. 2025",
  },
  {
    id: "ss-6",
    name: "ЖК «Городской сад»",
    image: "https://images.unsplash.com/photo-1448630360428-65456885c650?w=1200&h=800&fit=crop",
    priceFrom: 5200000,
    completion: "II кв. 2026",
  },
  {
    id: "ss-7",
    name: "ЖК «Речной»",
    image: "https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=1200&h=800&fit=crop",
    priceFrom: 3100000,
    completion: "IV кв. 2025",
  },
  {
    id: "ss-8",
    name: "ЖК «Престиж»",
    image: "https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=1200&h=800&fit=crop",
    priceFrom: 6800000,
    completion: "I кв. 2026",
  },
  {
    id: "ss-9",
    name: "ЖК «Акварель»",
    image: "https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=1200&h=800&fit=crop",
    priceFrom: 2900000,
    completion: "III кв. 2025",
  },
  {
    id: "ss-10",
    name: "ЖК «Эталон»",
    image: "https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=1200&h=800&fit=crop",
    priceFrom: 4200000,
    completion: "II кв. 2025",
  },
];

const formatPrice = (price: number) => {
  if (price >= 1000000) {
    return `${(price / 1000000).toFixed(1)} млн`;
  }
  return `${(price / 1000).toFixed(0)} тыс`;
};

const SalesStarts = () => {
  const sliderRef = useRef<HTMLDivElement>(null);
  const { isFavorite, addToFavorites, removeFromFavorites } = useFavorites();

  const scrollSlider = (direction: "left" | "right") => {
    if (sliderRef.current) {
      const cardWidth = 400;
      const gap = 20;
      const scrollAmount = cardWidth + gap;
      sliderRef.current.scrollBy({
        left: direction === "left" ? -scrollAmount : scrollAmount,
        behavior: "smooth",
      });
    }
  };

  const toggleFavorite = (e: React.MouseEvent, item: SalesStartItem) => {
    e.preventDefault();
    e.stopPropagation();
    const favorite = isFavorite(item.id);
    if (favorite) {
      removeFromFavorites(item.id);
      toast.success("Удалено из избранного");
    } else {
      addToFavorites({
        id: item.id,
        title: item.name,
        image: item.image,
        price: item.priceFrom,
        area: 0,
        rooms: 0,
        floor: 0,
        address: item.completion,
        type: "Новостройка",
      });
      toast.success("Добавлено в избранное");
    }
  };

  return (
    <section className="py-10 md:py-14 bg-gradient-to-b from-muted/40 to-background">
      <div className="container mx-auto px-4">
        {/* Header */}
        <div className="flex items-center justify-between mb-8">
          <div>
            <div className="flex items-center gap-3 mb-1">
              <h2 className="text-2xl md:text-3xl font-bold text-foreground tracking-tight">
                Старт продаж 2025
              </h2>
              <span className="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 bg-amber-500/10 text-amber-600 text-xs font-medium rounded-full">
                🔥 Горячие старты
              </span>
            </div>
            <p className="text-sm text-muted-foreground/60">
              Новые жилые комплексы со стартом продаж
            </p>
          </div>
          <div className="hidden sm:flex items-center gap-2">
            <Button
              variant="outline"
              size="icon"
              onClick={() => scrollSlider("left")}
              className="w-10 h-10 rounded-full border-border/60 hover:border-primary hover:bg-primary/5 hover:text-primary transition-all"
            >
              <ChevronLeft className="w-5 h-5" />
            </Button>
            <Button
              variant="outline"
              size="icon"
              onClick={() => scrollSlider("right")}
              className="w-10 h-10 rounded-full border-border/60 hover:border-primary hover:bg-primary/5 hover:text-primary transition-all"
            >
              <ChevronRight className="w-5 h-5" />
            </Button>
          </div>
        </div>

        {/* Slider */}
        <div
          ref={sliderRef}
          className="flex gap-5 overflow-x-auto scrollbar-hide scroll-smooth pb-4 -mx-4 px-4 snap-x snap-mandatory"
          style={{ scrollbarWidth: "none", msOverflowStyle: "none" }}
        >
          {mockSalesStarts.map((item) => (
            <Link
              key={item.id}
              to={`/buildings/${item.id}`}
              className="flex-shrink-0 w-[85vw] sm:w-[380px] md:w-[400px] snap-start group"
            >
              <div 
                className="relative aspect-[16/10] rounded-[24px] overflow-hidden border border-white/10"
                style={{ boxShadow: '0 8px 32px rgba(10, 35, 66, 0.12)' }}
              >
                {/* Background Image */}
                <img
                  src={item.image}
                  alt={item.name}
                  className="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                />

                {/* Gradient Overlay */}
                <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent" />

                {/* Favorite - subtle */}
                <button
                  onClick={(e) => toggleFavorite(e, item)}
                  className="absolute top-4 right-4 w-7 h-7 rounded-full bg-white/70 backdrop-blur-sm flex items-center justify-center hover:bg-white transition-colors z-10"
                >
                  <Heart
                    className={`w-3.5 h-3.5 transition-colors ${
                      isFavorite(item.id)
                        ? "fill-primary text-primary"
                        : "text-muted-foreground/60"
                    }`}
                  />
                </button>

                {/* Content */}
                <div className="absolute inset-0 p-5 flex flex-col justify-between">
                  {/* Badge */}
                  <span className="self-start inline-flex items-center px-3 py-1.5 rounded-full bg-primary text-white text-xs font-semibold shadow-lg">
                    Старт продаж
                  </span>

                  {/* Info */}
                  <div>
                    <h3 className="text-xl font-bold text-white mb-2 drop-shadow-lg">
                      {item.name}
                    </h3>
                    <div className="flex items-center gap-3 mb-3">
                      <span className="text-lg font-bold text-white drop-shadow-md">
                        от {formatPrice(item.priceFrom)} ₽
                      </span>
                      <span className="text-white/70 text-sm">
                        Сдача: {item.completion}
                      </span>
                    </div>

                    {/* CTA Button */}
                    <Button
                      variant="secondary"
                      size="sm"
                      className="h-9 px-4 text-xs font-semibold rounded-full bg-white/95 text-foreground hover:bg-white shadow-lg"
                    >
                      Подробнее
                      <ArrowRight className="w-3.5 h-3.5 ml-1.5" />
                    </Button>
                  </div>
                </div>
              </div>
            </Link>
          ))}
        </div>

        {/* Mobile Navigation */}
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

export default SalesStarts;

import { useRef } from "react";
import { Link } from "react-router-dom";
import { ChevronLeft, ChevronRight, Heart } from "lucide-react";
import { Button } from "@/components/ui/button";
import { useFavorites } from "@/hooks/useFavorites";
import { toast } from "sonner";

interface RecommendedProperty {
  id: string;
  title: string;
  image: string;
  price: number;
  area: number;
  rooms: number;
  address: string;
}

const mockRecommended: RecommendedProperty[] = [
  {
    id: "rec-1",
    title: "2-комнатная квартира",
    image: "https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=600&h=400&fit=crop",
    price: 4200000,
    area: 58,
    rooms: 2,
    address: "ул. Победы, 45",
  },
  {
    id: "rec-2",
    title: "Студия с ремонтом",
    image: "https://images.unsplash.com/photo-1560185127-6ed189bf02f4?w=600&h=400&fit=crop",
    price: 2900000,
    area: 32,
    rooms: 1,
    address: "пр. Славы, 78",
  },
  {
    id: "rec-3",
    title: "3-комнатная квартира",
    image: "https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?w=600&h=400&fit=crop",
    price: 6800000,
    area: 92,
    rooms: 3,
    address: "ул. Щорса, 12",
  },
  {
    id: "rec-4",
    title: "1-комнатная квартира",
    image: "https://images.unsplash.com/photo-1600585154526-990dced4db0d?w=600&h=400&fit=crop",
    price: 3500000,
    area: 45,
    rooms: 1,
    address: "ул. Губкина, 8",
  },
  {
    id: "rec-5",
    title: "Пентхаус",
    image: "https://images.unsplash.com/photo-1600047509807-ba8f99d2cdde?w=600&h=400&fit=crop",
    price: 12500000,
    area: 145,
    rooms: 4,
    address: "ул. Центральная, 1",
  },
  {
    id: "rec-6",
    title: "2-комнатная квартира",
    image: "https://images.unsplash.com/photo-1600573472550-8090b5e0745e?w=600&h=400&fit=crop",
    price: 5100000,
    area: 65,
    rooms: 2,
    address: "ул. Садовая, 33",
  },
];

const MayLikeSection = () => {
  const sliderRef = useRef<HTMLDivElement>(null);
  const { isFavorite, addToFavorites, removeFromFavorites } = useFavorites();

  const scrollSlider = (direction: "left" | "right") => {
    if (sliderRef.current) {
      const cardWidth = 280;
      const gap = 16;
      const scrollAmount = cardWidth + gap;
      sliderRef.current.scrollBy({
        left: direction === "left" ? -scrollAmount : scrollAmount,
        behavior: "smooth",
      });
    }
  };

  const toggleFavorite = (e: React.MouseEvent, property: RecommendedProperty) => {
    e.preventDefault();
    e.stopPropagation();
    const favorite = isFavorite(property.id);
    if (favorite) {
      removeFromFavorites(property.id);
      toast.success("Удалено из избранного");
    } else {
      addToFavorites({
        id: property.id,
        title: property.title,
        image: property.image,
        price: property.price,
        area: property.area,
        rooms: property.rooms,
        floor: 1,
        address: property.address,
        type: "Новостройка",
      });
      toast.success("Добавлено в избранное");
    }
  };

  return (
    <section className="py-10 md:py-14 bg-gradient-to-b from-background to-muted/30">
      <div className="container mx-auto px-4">
        {/* Header */}
        <div className="flex items-center justify-between mb-8">
          <div>
            <h2 className="text-2xl md:text-3xl font-bold text-foreground tracking-tight">
              Вам может понравиться
            </h2>
            <p className="text-sm text-muted-foreground/60 mt-1">
              Подборка на основе ваших предпочтений
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
          {mockRecommended.map((property) => (
            <Link
              key={property.id}
              to={`/property/${property.id}`}
              className="flex-shrink-0 w-[260px] sm:w-[280px] snap-start group"
            >
              <div 
                className="bg-card rounded-[20px] border border-border/50 overflow-hidden hover:border-primary/20 hover:shadow-[0_16px_40px_rgba(10,35,66,0.10)] hover:-translate-y-1 transition-all duration-300"
                style={{ boxShadow: '0 4px 20px rgba(10, 35, 66, 0.06)' }}
              >
                {/* Image */}
                <div className="relative aspect-[4/3] overflow-hidden">
                  <img
                    src={property.image}
                    alt={property.title}
                    className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                  />
                  {/* Gradient overlay */}
                  <div className="absolute inset-0 bg-gradient-to-t from-black/30 via-transparent to-transparent" />
                  
                  {/* Favorite - subtle, smaller */}
                  <button
                    onClick={(e) => toggleFavorite(e, property)}
                    className="absolute top-2.5 right-2.5 w-7 h-7 rounded-full bg-white/80 backdrop-blur-sm flex items-center justify-center hover:bg-white transition-colors shadow-sm"
                  >
                    <Heart
                      className={`w-3.5 h-3.5 transition-colors ${
                        isFavorite(property.id)
                          ? "fill-primary text-primary"
                          : "text-muted-foreground/50"
                      }`}
                    />
                  </button>
                </div>

                {/* Content - unified structure */}
                <div className="p-3.5">
                  {/* Price */}
                  <p className="text-lg font-bold text-primary mb-1 leading-tight">
                    {property.price.toLocaleString("ru-RU")} ₽
                  </p>
                  {/* Type */}
                  <p className="text-sm text-foreground font-medium mb-1.5 line-clamp-1">
                    {property.title}
                  </p>
                  {/* Specs + Address */}
                  <p className="text-[11px] text-muted-foreground/60 truncate">
                    {property.area} м² · {property.rooms} комн. · {property.address}
                  </p>
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

export default MayLikeSection;

import { useState, useEffect } from "react";
import { Link } from "react-router-dom";
import { 
  Heart, Phone, MapPin, 
  ChevronLeft, ChevronRight, X, Expand
} from "lucide-react";
import { Button } from "@/components/ui/button";
import {
  Carousel,
  CarouselContent,
  CarouselItem,
  CarouselNext,
  CarouselPrevious,
  type CarouselApi,
} from "@/components/ui/carousel";
import { cn } from "@/lib/utils";
import ViewingRequestForm from "@/components/ViewingRequestForm";
import { useFavorites } from "@/hooks/useFavorites";
import { toast } from "sonner";

interface PropertyHeroBlockProps {
  property: {
    id: string;
    title: string;
    price: number;
    pricePerMeter: number;
    images: string[];
    area: number;
    rooms: number;
    floor: number;
    totalFloors: number;
    address: string;
    district: string;
    type: string;
  };
  onPhoneClick?: () => void;
  onRequestClick?: () => void;
}

const PropertyHeroBlock = ({ property, onPhoneClick, onRequestClick }: PropertyHeroBlockProps) => {
  const [currentImageIndex, setCurrentImageIndex] = useState(0);
  const [showFullscreen, setShowFullscreen] = useState(false);
  const [api, setApi] = useState<CarouselApi>();
  const [mobileApi, setMobileApi] = useState<CarouselApi>();
  const { isFavorite, addToFavorites, removeFromFavorites } = useFavorites();
  
  const favorite = isFavorite(property.id);

  const formatPrice = (price: number) => {
    return price.toLocaleString("ru-RU") + " ₽";
  };

  const toggleFavorite = () => {
    if (favorite) {
      removeFromFavorites(property.id);
      toast.success("Удалено из избранного");
    } else {
      addToFavorites({
        id: property.id,
        title: property.title,
        price: property.price,
        image: property.images[0],
        area: property.area,
        rooms: property.rooms,
        floor: property.floor,
        address: property.address,
        type: property.type,
      });
      toast.success("Добавлено в избранное");
    }
  };

  const handleImageClick = (index: number) => {
    setCurrentImageIndex(index);
    setShowFullscreen(true);
  };

  // Sync carousel with currentImageIndex
  useEffect(() => {
    if (!api) return;
    api.on("select", () => {
      setCurrentImageIndex(api.selectedScrollSnap());
    });
  }, [api]);

  useEffect(() => {
    if (!mobileApi) return;
    mobileApi.on("select", () => {
      setCurrentImageIndex(mobileApi.selectedScrollSnap());
    });
  }, [mobileApi]);

  // Keyboard navigation for fullscreen
  useEffect(() => {
    if (!showFullscreen) return;

    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === "ArrowLeft") {
        setCurrentImageIndex((prev) => (prev - 1 + property.images.length) % property.images.length);
      } else if (e.key === "ArrowRight") {
        setCurrentImageIndex((prev) => (prev + 1) % property.images.length);
      } else if (e.key === "Escape") {
        setShowFullscreen(false);
      }
    };

    window.addEventListener("keydown", handleKeyDown);
    return () => window.removeEventListener("keydown", handleKeyDown);
  }, [showFullscreen, property.images.length]);

  return (
    <>
      {/* Hero Block - Desktop: 2 columns (60% / 40%), Mobile: stacked */}
      <div className="mb-6 md:mb-8">
        <div className="grid grid-cols-1 lg:grid-cols-[60%_40%] gap-4 lg:gap-6">
          {/* Left Column - Gallery Slider (60% on desktop) */}
          <div className="lg:col-span-1 order-1 lg:order-1">
            {/* Desktop Carousel */}
            <div className="hidden lg:block">
              <Carousel
                className="w-full"
                opts={{
                  align: "start",
                  loop: true,
                }}
                setApi={setApi}
              >
                <CarouselContent className="-ml-0">
                  {property.images.map((image, index) => (
                    <CarouselItem key={index} className="pl-0">
                      <div 
                        className="relative aspect-[4/3] rounded-xl overflow-hidden bg-muted/20 cursor-pointer group"
                        onClick={() => handleImageClick(index)}
                      >
                        <img
                          src={image}
                          alt={`${property.title} - фото ${index + 1}`}
                          className="w-full h-full object-cover transition-transform duration-300 group-hover:scale-[1.02]"
                          loading={index === 0 ? "eager" : "lazy"}
                        />
                        
                        {/* Image Counter */}
                        {property.images.length > 1 && (
                          <div className="absolute top-3 right-3 bg-black/70 backdrop-blur-sm px-2.5 py-1 rounded-md text-white text-xs font-medium">
                            {index + 1} / {property.images.length}
                          </div>
                        )}

                        {/* Fullscreen Button */}
                        <div className="absolute bottom-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity">
                          <Button
                            variant="secondary"
                            size="icon"
                            className="w-9 h-9 rounded-lg bg-white/95 hover:bg-white backdrop-blur-sm shadow-md"
                            onClick={(e) => {
                              e.stopPropagation();
                              handleImageClick(index);
                            }}
                            aria-label="Открыть в полноэкранном режиме"
                          >
                            <Expand className="w-4 h-4" />
                          </Button>
                        </div>
                      </div>
                    </CarouselItem>
                  ))}
                </CarouselContent>
                {property.images.length > 1 && (
                  <>
                    <CarouselPrevious className="left-3 bg-white/95 hover:bg-white backdrop-blur-sm border-0 shadow-lg w-10 h-10" />
                    <CarouselNext className="right-3 bg-white/95 hover:bg-white backdrop-blur-sm border-0 shadow-lg w-10 h-10" />
                  </>
                )}
              </Carousel>

              {/* Thumbnails */}
              {property.images.length > 1 && (
                <div className="mt-3 flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
                  {property.images.map((image, index) => (
                    <button
                      key={index}
                      onClick={() => {
                        api?.scrollTo(index);
                      }}
                      className={cn(
                        "flex-shrink-0 w-20 h-16 rounded-lg overflow-hidden border-2 transition-all",
                        currentImageIndex === index
                          ? "border-primary scale-105 shadow-sm"
                          : "border-transparent hover:border-border/50 opacity-70 hover:opacity-100"
                      )}
                      aria-label={`Показать фото ${index + 1}`}
                    >
                      <img
                        src={image}
                        alt={`Превью ${index + 1}`}
                        className="w-full h-full object-cover"
                      />
                    </button>
                  ))}
                </div>
              )}
            </div>

            {/* Mobile Carousel */}
            <div className="lg:hidden">
              <Carousel
                className="w-full"
                opts={{
                  align: "start",
                  loop: true,
                }}
                setApi={setMobileApi}
              >
                <CarouselContent className="-ml-0">
                  {property.images.map((image, index) => (
                    <CarouselItem key={index} className="pl-0">
                      <div 
                        className="relative aspect-[4/3] rounded-xl overflow-hidden bg-muted/20"
                        onClick={() => handleImageClick(index)}
                      >
                        <img
                          src={image}
                          alt={`${property.title} - фото ${index + 1}`}
                          className="w-full h-full object-cover"
                          loading={index === 0 ? "eager" : "lazy"}
                        />
                        
                        {/* Image Counter */}
                        {property.images.length > 1 && (
                          <div className="absolute top-2 right-2 bg-black/70 backdrop-blur-sm px-2 py-0.5 rounded-md text-white text-xs font-medium">
                            {index + 1} / {property.images.length}
                          </div>
                        )}
                      </div>
                    </CarouselItem>
                  ))}
                </CarouselContent>
                {property.images.length > 1 && (
                  <>
                    <CarouselPrevious className="left-2 bg-white/95 hover:bg-white backdrop-blur-sm border-0 shadow-lg h-8 w-8" />
                    <CarouselNext className="right-2 bg-white/95 hover:bg-white backdrop-blur-sm border-0 shadow-lg h-8 w-8" />
                  </>
                )}
              </Carousel>

              {/* Dots Indicator */}
              {property.images.length > 1 && (
                <div className="flex justify-center gap-1.5 mt-3">
                  {property.images.map((_, index) => (
                    <button
                      key={index}
                      onClick={() => {
                        mobileApi?.scrollTo(index);
                      }}
                      className={cn(
                        "h-1.5 rounded-full transition-all",
                        currentImageIndex === index
                          ? "bg-primary w-6"
                          : "bg-muted-foreground/30 w-1.5 hover:bg-muted-foreground/50"
                      )}
                      aria-label={`Показать фото ${index + 1}`}
                    />
                  ))}
                </div>
              )}
            </div>
          </div>

          {/* Right Column - Info Block (40% on desktop, sticky) */}
          <div className="lg:col-span-1 order-2 lg:order-2">
            <div className="lg:sticky lg:top-6 bg-white rounded-xl border border-border/50 p-5 lg:p-6 space-y-5 shadow-sm">
              {/* Price Section - Main Accent */}
              <div className="pb-4 border-b border-border/50">
                <p className="text-3xl md:text-4xl font-bold text-foreground mb-1.5 leading-tight">
                  {formatPrice(property.price)}
                </p>
                <p className="text-sm text-muted-foreground">
                  {property.pricePerMeter.toLocaleString("ru-RU")} ₽/м²
                </p>
              </div>

              {/* Type Badge */}
              <div>
                <span className="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-primary/10 text-primary border border-primary/20">
                  {property.type}
                </span>
              </div>

              {/* Quick Parameters - One Line */}
              <div className="flex items-center gap-2.5 text-sm text-muted-foreground flex-wrap">
                <span className="font-semibold text-foreground">{property.rooms} комн.</span>
                <span className="text-muted-foreground/50">•</span>
                <span>{property.area} м²</span>
                <span className="text-muted-foreground/50">•</span>
                <span>{property.floor}/{property.totalFloors} этаж</span>
              </div>

              {/* Address */}
              <div className="space-y-2">
                <div className="flex items-start gap-2 text-muted-foreground">
                  <MapPin className="w-4 h-4 text-primary flex-shrink-0 mt-0.5" />
                  <div className="flex-1 min-w-0">
                    <p className="text-sm text-foreground font-medium leading-snug">{property.address}</p>
                    <p className="text-xs text-muted-foreground mt-0.5">{property.district}</p>
                  </div>
                </div>
                <a
                  href={`https://yandex.ru/maps/?text=${encodeURIComponent(property.address)}`}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-sm text-primary hover:text-primary/80 hover:underline inline-flex items-center gap-1 transition-colors"
                >
                  На карте
                  <ChevronRight className="w-3.5 h-3.5" />
                </a>
              </div>

              {/* Action Buttons */}
              <div className="space-y-2.5 pt-1">
                {/* Favorite Button */}
                <Button
                  variant="outline"
                  size="lg"
                  className="w-full justify-center h-11 border-border/50 hover:bg-muted/50"
                  onClick={toggleFavorite}
                  aria-label={favorite ? "Удалить из избранного" : "Добавить в избранное"}
                >
                  <Heart className={cn(
                    "w-5 h-5 mr-2",
                    favorite && "fill-primary text-primary"
                  )} />
                  {favorite ? "В избранном" : "В избранное"}
                </Button>

                {/* Main CTA - Забронировать / Оставить заявку */}
                <ViewingRequestForm
                  propertyId={property.id}
                  propertyTitle={property.title}
                  propertyImage={property.images[0]}
                  propertyPrice={property.price}
                  trigger={
                    <Button
                      variant="primary"
                      size="lg"
                      className="w-full min-h-[48px] h-12 font-semibold text-base"
                      onClick={onRequestClick}
                    >
                      Забронировать
                    </Button>
                  }
                />

                {/* Secondary CTA - Контакты / Показать телефон */}
                <Button
                  variant="outline"
                  size="lg"
                  className="w-full h-11 border-border/50 hover:bg-muted/50"
                  onClick={onPhoneClick}
                >
                  <Phone className="w-4 h-4 mr-2" />
                  Контакты
                </Button>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Mobile Sticky Bottom Bar */}
      <div className="fixed bottom-0 left-0 right-0 lg:hidden bg-white border-t border-border/50 p-3 z-50 shadow-[0_-2px_12px_rgba(0,0,0,0.08)]">
        <div className="container mx-auto flex items-center gap-2.5">
          <Button
            variant="ghost"
            size="icon"
            className={cn(
              "flex-shrink-0 min-h-[44px] min-w-[44px] w-11 h-11 rounded-lg",
              favorite ? "text-primary bg-primary/10" : "text-muted-foreground hover:bg-muted/50"
            )}
            onClick={toggleFavorite}
            aria-label={favorite ? "Удалить из избранного" : "Добавить в избранное"}
          >
            <Heart className={cn(
              "w-5 h-5",
              favorite && "fill-primary text-primary"
            )} />
          </Button>
          <ViewingRequestForm
            propertyId={property.id}
            propertyTitle={property.title}
            propertyImage={property.images[0]}
            propertyPrice={property.price}
            trigger={
              <Button
                variant="primary"
                size="lg"
                className="flex-1 min-h-[44px] h-11 rounded-lg font-semibold text-base"
                onClick={onRequestClick}
              >
                Забронировать
              </Button>
            }
          />
        </div>
      </div>

      {/* Fullscreen Gallery Modal */}
      {showFullscreen && (
        <div 
          className="fixed inset-0 z-[100] bg-black/95 flex items-center justify-center"
          onClick={() => setShowFullscreen(false)}
        >
          <button
            onClick={() => setShowFullscreen(false)}
            className="absolute top-4 right-4 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition-colors z-10"
            aria-label="Закрыть"
          >
            <X className="w-6 h-6" />
          </button>
          
          <button
            onClick={(e) => {
              e.stopPropagation();
              setCurrentImageIndex((prev) => (prev - 1 + property.images.length) % property.images.length);
            }}
            className="absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors z-10"
            aria-label="Предыдущее фото"
          >
            <ChevronLeft className="w-6 h-6 text-white" />
          </button>
          
          <img
            src={property.images[currentImageIndex]}
            alt={property.title}
            className="max-w-full max-h-[85vh] object-contain"
            onClick={(e) => e.stopPropagation()}
          />
          
          <button
            onClick={(e) => {
              e.stopPropagation();
              setCurrentImageIndex((prev) => (prev + 1) % property.images.length);
            }}
            className="absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors z-10"
            aria-label="Следующее фото"
          >
            <ChevronRight className="w-6 h-6 text-white" />
          </button>

          {/* Thumbnails */}
          <div 
            className="absolute bottom-8 left-1/2 -translate-x-1/2 flex gap-2 max-w-[80%] overflow-x-auto pb-2"
            onClick={(e) => e.stopPropagation()}
          >
            {property.images.map((img, index) => (
              <button
                key={index}
                onClick={() => setCurrentImageIndex(index)}
                className={cn(
                  "flex-shrink-0 w-16 h-12 rounded-lg overflow-hidden transition-all",
                  currentImageIndex === index 
                    ? "ring-2 ring-white scale-110" 
                    : "opacity-50 hover:opacity-100"
                )}
                aria-label={`Показать фото ${index + 1}`}
              >
                <img src={img} alt="" className="w-full h-full object-cover" />
              </button>
            ))}
          </div>

          <div className="absolute bottom-4 left-1/2 -translate-x-1/2 text-white/80 text-sm">
            {currentImageIndex + 1} / {property.images.length}
          </div>
        </div>
      )}
    </>
  );
};

export default PropertyHeroBlock;

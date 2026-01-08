import { useState, useRef } from "react";
import { Link } from "react-router-dom";
import { 
  Heart, Phone, MapPin, Maximize2, 
  ChevronLeft, ChevronRight, X
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

  return (
    <>
      {/* Hero Block - Desktop: 2 columns, Mobile: stacked */}
      <div className="mb-8">
        <div className="grid grid-cols-1 lg:grid-cols-5 gap-4 lg:gap-6">
          {/* Left Column - Gallery (60% on desktop) */}
          <div className="lg:col-span-3">
            {/* Desktop Carousel */}
            <div className="hidden lg:block">
              <Carousel
                className="w-full"
                opts={{
                  align: "start",
                  loop: true,
                }}
                setApi={(api) => {
                  if (api) {
                    api.on("select", () => {
                      setCurrentImageIndex(api.selectedScrollSnap());
                    });
                  }
                }}
              >
                <CarouselContent className="-ml-0">
                  {property.images.map((image, index) => (
                    <CarouselItem key={index} className="pl-0">
                      <div 
                        className="relative aspect-[4/3] rounded-2xl overflow-hidden bg-muted/30 cursor-pointer group"
                        onClick={() => handleImageClick(index)}
                      >
                        <img
                          src={image}
                          alt={`${property.title} - фото ${index + 1}`}
                          className="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                        />
                        <div className="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity" />
                        
                        {/* Image Counter */}
                        <div className="absolute top-4 right-4 bg-black/50 backdrop-blur-sm px-3 py-1.5 rounded-full text-white text-sm font-medium">
                          {index + 1} / {property.images.length}
                        </div>

                        {/* Fullscreen Button */}
                        <div className="absolute bottom-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity">
                          <Button
                            variant="secondary"
                            size="icon"
                            className="w-10 h-10 rounded-full bg-white/90 hover:bg-white backdrop-blur-sm"
                            onClick={(e) => {
                              e.stopPropagation();
                              handleImageClick(index);
                            }}
                          >
                            <Maximize2 className="w-5 h-5" />
                          </Button>
                        </div>
                      </div>
                    </CarouselItem>
                  ))}
                </CarouselContent>
                {property.images.length > 1 && (
                  <>
                    <CarouselPrevious className="left-4 bg-white/90 hover:bg-white backdrop-blur-sm border-0 shadow-lg" />
                    <CarouselNext className="right-4 bg-white/90 hover:bg-white backdrop-blur-sm border-0 shadow-lg" />
                  </>
                )}
              </Carousel>

              {/* Thumbnails */}
              {property.images.length > 1 && (
                <div className="mt-3 flex gap-2 overflow-x-auto pb-2">
                  {property.images.map((image, index) => (
                    <button
                      key={index}
                      onClick={() => {
                        api?.scrollTo(index);
                      }}
                      className={cn(
                        "flex-shrink-0 w-20 h-16 rounded-lg overflow-hidden border-2 transition-all",
                        currentImageIndex === index
                          ? "border-primary scale-105"
                          : "border-transparent hover:border-border opacity-70 hover:opacity-100"
                      )}
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
                setApi={(carouselApi) => {
                  if (carouselApi) {
                    setMobileApi(carouselApi);
                    carouselApi.on("select", () => {
                      setCurrentImageIndex(carouselApi.selectedScrollSnap());
                    });
                  }
                }}
              >
                <CarouselContent className="-ml-0">
                  {property.images.map((image, index) => (
                    <CarouselItem key={index} className="pl-0">
                      <div 
                        className="relative aspect-[4/3] rounded-2xl overflow-hidden bg-muted/30"
                        onClick={() => handleImageClick(index)}
                      >
                        <img
                          src={image}
                          alt={`${property.title} - фото ${index + 1}`}
                          className="w-full h-full object-cover"
                        />
                        
                        {/* Image Counter */}
                        <div className="absolute top-3 right-3 bg-black/50 backdrop-blur-sm px-2.5 py-1 rounded-full text-white text-xs font-medium">
                          {index + 1} / {property.images.length}
                        </div>
                      </div>
                    </CarouselItem>
                  ))}
                </CarouselContent>
                {property.images.length > 1 && (
                  <>
                    <CarouselPrevious className="left-2 bg-white/90 hover:bg-white backdrop-blur-sm border-0 shadow-lg h-8 w-8" />
                    <CarouselNext className="right-2 bg-white/90 hover:bg-white backdrop-blur-sm border-0 shadow-lg h-8 w-8" />
                  </>
                )}
              </Carousel>

              {/* Dots Indicator */}
              {property.images.length > 1 && (
                <div className="flex justify-center gap-2 mt-3">
                  {property.images.map((_, index) => (
                    <button
                      key={index}
                      onClick={() => {
                        mobileApi?.scrollTo(index);
                      }}
                      className={cn(
                        "h-2 rounded-full transition-all",
                        currentImageIndex === index
                          ? "bg-primary w-6"
                          : "bg-muted-foreground/30 w-2 hover:bg-muted-foreground/50"
                      )}
                    />
                  ))}
                </div>
              )}
            </div>
          </div>

          {/* Right Column - Info Block (40% on desktop) */}
          <div className="lg:col-span-2">
            <div className="lg:sticky lg:top-6 bg-card rounded-2xl border border-border p-6 shadow-card space-y-6">
              {/* Price Section */}
              <div className="pb-4 border-b border-border">
                <p className="text-3xl md:text-4xl font-bold text-foreground mb-1">
                  {formatPrice(property.price)}
                </p>
                <p className="text-sm text-muted-foreground">
                  {property.pricePerMeter.toLocaleString("ru-RU")} ₽/м²
                </p>
              </div>

              {/* Type Badge */}
              <div>
                <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-primary/10 text-primary">
                  {property.type}
                </span>
              </div>

              {/* Quick Parameters */}
              <div className="flex items-center gap-4 text-sm text-muted-foreground">
                <span>{property.rooms} комн.</span>
                <span>•</span>
                <span>{property.area} м²</span>
                <span>•</span>
                <span>{property.floor}/{property.totalFloors} этаж</span>
              </div>

              {/* Address */}
              <div className="space-y-1">
                <div className="flex items-start gap-2 text-muted-foreground">
                  <MapPin className="w-4 h-4 text-primary flex-shrink-0 mt-0.5" />
                  <div className="flex-1">
                    <p className="text-sm">{property.address}</p>
                    <p className="text-xs mt-0.5">{property.district}</p>
                  </div>
                </div>
                <a
                  href={`https://yandex.ru/maps/?text=${encodeURIComponent(property.address)}`}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-sm text-primary hover:underline inline-flex items-center gap-1"
                >
                  На карте
                </a>
              </div>

              {/* Action Buttons */}
              <div className="space-y-3 pt-2">
                {/* Favorite Button */}
                <Button
                  variant="outline"
                  size="lg"
                  fullWidth
                  className="justify-center"
                  onClick={toggleFavorite}
                >
                  <Heart className={cn(
                    "w-5 h-5 mr-2",
                    favorite && "fill-primary text-primary"
                  )} />
                  {favorite ? "В избранном" : "В избранное"}
                </Button>

                {/* Main CTA */}
                <ViewingRequestForm
                  propertyId={property.id}
                  propertyTitle={property.title}
                  propertyImage={property.images[0]}
                  propertyPrice={property.price}
                />

                {/* Secondary CTA */}
                <Button
                  variant="outline"
                  size="lg"
                  fullWidth
                  leftIcon={<Phone className="w-5 h-5" />}
                  onClick={onPhoneClick}
                >
                  Контакты
                </Button>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Fullscreen Gallery Modal */}
      {showFullscreen && (
        <div 
          className="fixed inset-0 z-50 bg-black/95 flex items-center justify-center"
          onClick={() => setShowFullscreen(false)}
        >
          <button
            onClick={() => setShowFullscreen(false)}
            className="absolute top-4 right-4 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition-colors z-10"
          >
            <X className="w-6 h-6" />
          </button>
          
          <button
            onClick={(e) => {
              e.stopPropagation();
              setCurrentImageIndex((prev) => (prev - 1 + property.images.length) % property.images.length);
            }}
            className="absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors z-10"
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
          >
            <ChevronRight className="w-6 h-6 text-white" />
          </button>

          {/* Thumbnails */}
          <div 
            className="absolute bottom-8 left-1/2 -translate-x-1/2 flex gap-2 max-w-[80%] overflow-x-auto"
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


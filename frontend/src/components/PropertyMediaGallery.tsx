import { useState, useEffect, useCallback, useRef } from "react";
import useEmblaCarousel from "embla-carousel-react";
import { cn } from "@/lib/utils";
import { X, ChevronLeft, ChevronRight } from "lucide-react";

interface Photo {
  id: string;
  url: string;
  alt: string;
}

interface PropertyMediaGalleryProps {
  photos: Photo[];
  propertyTitle?: string;
}

const PropertyMediaGallery = ({ photos, propertyTitle = "Объект" }: PropertyMediaGalleryProps) => {
  const [emblaRef, emblaApi] = useEmblaCarousel({ loop: true, align: "start" });
  const [selectedIndex, setSelectedIndex] = useState(0);
  const [isLoading, setIsLoading] = useState(true);
  const [showFullscreen, setShowFullscreen] = useState(false);
  const [touchStart, setTouchStart] = useState<number | null>(null);
  const [touchEnd, setTouchEnd] = useState<number | null>(null);
  const [showArrows, setShowArrows] = useState(false);
  const galleryRef = useRef<HTMLDivElement>(null);

  // Minimum swipe distance (in px)
  const minSwipeDistance = 50;

  const onSelect = useCallback(() => {
    if (!emblaApi) return;
    setSelectedIndex(emblaApi.selectedScrollSnap());
  }, [emblaApi]);

  useEffect(() => {
    if (!emblaApi) return;
    onSelect();
    emblaApi.on("select", onSelect);
    emblaApi.on("reInit", onSelect);
    return () => {
      emblaApi.off("select", onSelect);
      emblaApi.off("reInit", onSelect);
    };
  }, [emblaApi, onSelect]);

  // Keyboard navigation
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (showFullscreen) {
        if (e.key === "ArrowLeft") {
          scrollPrev();
        } else if (e.key === "ArrowRight") {
          scrollNext();
        } else if (e.key === "Escape") {
          setShowFullscreen(false);
        }
      } else {
        if (e.key === "ArrowLeft" || e.key === "ArrowRight") {
          e.preventDefault();
          if (e.key === "ArrowLeft") {
            scrollPrev();
          } else {
            scrollNext();
          }
        }
      }
    };

    window.addEventListener("keydown", handleKeyDown);
    return () => window.removeEventListener("keydown", handleKeyDown);
  }, [showFullscreen, emblaApi]);

  // Touch handlers for swipe
  const onTouchStart = (e: React.TouchEvent) => {
    setTouchEnd(null);
    setTouchStart(e.targetTouches[0].clientX);
  };

  const onTouchMove = (e: React.TouchEvent) => {
    setTouchEnd(e.targetTouches[0].clientX);
  };

  const onTouchEnd = () => {
    if (!touchStart || !touchEnd) return;
    const distance = touchStart - touchEnd;
    const isLeftSwipe = distance > minSwipeDistance;
    const isRightSwipe = distance < -minSwipeDistance;

    if (isLeftSwipe) {
      scrollNext();
    }
    if (isRightSwipe) {
      scrollPrev();
    }
  };

  const scrollPrev = useCallback(() => {
    if (emblaApi) emblaApi.scrollPrev();
  }, [emblaApi]);

  const scrollNext = useCallback(() => {
    if (emblaApi) emblaApi.scrollNext();
  }, [emblaApi]);

  const scrollTo = useCallback(
    (index: number) => {
      if (emblaApi) emblaApi.scrollTo(index);
    },
    [emblaApi]
  );

  const handleImageLoad = () => {
    setIsLoading(false);
  };

  const handleFullscreen = () => {
    setShowFullscreen(true);
  };

  const handleFullscreenClose = () => {
    setShowFullscreen(false);
  };

  // Show arrows on hover (desktop only)
  useEffect(() => {
    const gallery = galleryRef.current;
    if (!gallery) return;

    const handleMouseEnter = () => setShowArrows(true);
    const handleMouseLeave = () => setShowArrows(false);

    gallery.addEventListener("mouseenter", handleMouseEnter);
    gallery.addEventListener("mouseleave", handleMouseLeave);

    return () => {
      gallery.removeEventListener("mouseenter", handleMouseEnter);
      gallery.removeEventListener("mouseleave", handleMouseLeave);
    };
  }, []);

  if (!photos || photos.length === 0) {
    return (
      <div className="w-full h-[400px] md:h-[500px] lg:h-[600px] bg-muted/20 rounded-xl flex items-center justify-center">
        <p className="text-muted-foreground">Нет фотографий</p>
      </div>
    );
  }

  return (
    <>
      {/* Main Gallery */}
      <div
        ref={galleryRef}
        className="relative w-full h-[400px] md:h-[500px] lg:h-[600px] rounded-xl overflow-hidden bg-muted/20 group"
        onTouchStart={onTouchStart}
        onTouchMove={onTouchMove}
        onTouchEnd={onTouchEnd}
      >
        {/* Loading Spinner */}
        {isLoading && (
          <div className="absolute inset-0 flex items-center justify-center z-10 bg-black/10">
            <div className="w-10 h-10 border-4 border-[#2563EB] border-t-transparent rounded-full animate-spin" />
          </div>
        )}

        {/* Embla Carousel */}
        <div className="overflow-hidden h-full" ref={emblaRef}>
          <div className="flex h-full">
            {photos.map((photo, index) => (
              <div key={photo.id} className="flex-[0_0_100%] min-w-0 h-full">
                <img
                  src={photo.url}
                  alt={photo.alt || `${propertyTitle} - фото ${index + 1}`}
                  className="w-full h-full object-cover"
                  loading={index === 0 ? "eager" : "lazy"}
                  onLoad={index === 0 ? handleImageLoad : undefined}
                  onError={() => setIsLoading(false)}
                />
              </div>
            ))}
          </div>
        </div>

        {/* Counter (top-right) */}
        {photos.length > 1 && (
          <div className="absolute top-[10px] right-[10px] bg-black/50 backdrop-blur-sm px-[10px] py-[6px] rounded-md text-white text-[12px] font-medium z-20">
            {selectedIndex + 1} / {photos.length}
          </div>
        )}

        {/* Fullscreen Button (top-right) */}
        <button
          onClick={handleFullscreen}
          className="absolute top-[10px] right-[60px] w-12 h-12 flex items-center justify-center bg-black/30 hover:bg-black/50 rounded-lg transition-colors z-20 cursor-pointer touch-manipulation"
          aria-label="Открыть в полноэкранном режиме"
        >
          <svg
            width="24"
            height="24"
            viewBox="0 0 24 24"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            className="text-white"
          >
            <path
              d="M8 3H5C3.89543 3 3 3.89543 3 5V8M21 8V5C21 3.89543 20.1046 3 19 3H16M16 21H19C20.1046 21 21 20.1046 21 19V16M3 16V19C3 20.1046 3.89543 21 5 21H8"
              stroke="currentColor"
              strokeWidth="2"
              strokeLinecap="round"
              strokeLinejoin="round"
            />
          </svg>
        </button>

        {/* Navigation Arrows */}
        {photos.length > 1 && (
          <>
            {/* Previous Arrow */}
            <button
              onClick={scrollPrev}
              className={cn(
                "absolute left-[10px] top-1/2 -translate-y-1/2 w-12 h-12 flex items-center justify-center bg-black/30 hover:bg-black/50 rounded-lg transition-all z-20 cursor-pointer touch-manipulation",
                "lg:opacity-0 lg:group-hover:opacity-100 lg:pointer-events-none lg:group-hover:pointer-events-auto",
                "opacity-100"
              )}
              aria-label="Предыдущее фото"
            >
              <ChevronLeft className="w-6 h-6 text-white" />
            </button>

            {/* Next Arrow */}
            <button
              onClick={scrollNext}
              className={cn(
                "absolute right-[10px] top-1/2 -translate-y-1/2 w-12 h-12 flex items-center justify-center bg-black/30 hover:bg-black/50 rounded-lg transition-all z-20 cursor-pointer touch-manipulation",
                "lg:opacity-0 lg:group-hover:opacity-100 lg:pointer-events-none lg:group-hover:pointer-events-auto",
                "opacity-100"
              )}
              aria-label="Следующее фото"
            >
              <ChevronRight className="w-6 h-6 text-white" />
            </button>
          </>
        )}

        {/* Dot Indicators (bottom) */}
        {photos.length > 1 && (
          <div className="absolute bottom-[10px] left-1/2 -translate-x-1/2 flex items-center gap-1.5 z-20">
            {photos.map((_, index) => (
              <button
                key={index}
                onClick={() => scrollTo(index)}
                className={cn(
                  "rounded-full transition-all touch-manipulation",
                  index === selectedIndex
                    ? "w-2 h-2 bg-white opacity-100"
                    : "w-1.5 h-1.5 bg-white opacity-50 hover:opacity-75"
                )}
                aria-label={`Показать фото ${index + 1}`}
              />
            ))}
          </div>
        )}
      </div>

      {/* Fullscreen Modal */}
      {showFullscreen && (
        <div
          className="fixed inset-0 z-[200] bg-black/95 flex items-center justify-center"
          onClick={handleFullscreenClose}
        >
          {/* Close Button */}
          <button
            onClick={handleFullscreenClose}
            className="absolute top-4 right-4 w-12 h-12 flex items-center justify-center bg-white/10 hover:bg-white/20 rounded-lg transition-colors z-30"
            aria-label="Закрыть"
          >
            <X className="w-6 h-6 text-white" />
          </button>

          {/* Fullscreen Image */}
          <div className="relative max-w-[90vw] max-h-[90vh] flex items-center justify-center">
            <img
              src={photos[selectedIndex].url}
              alt={photos[selectedIndex].alt || `${propertyTitle} - фото ${selectedIndex + 1}`}
              className="max-w-full max-h-[90vh] object-contain"
              onClick={(e) => e.stopPropagation()}
            />

            {/* Navigation Arrows in Fullscreen */}
            {photos.length > 1 && (
              <>
                <button
                  onClick={(e) => {
                    e.stopPropagation();
                    scrollPrev();
                  }}
                  className="absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 flex items-center justify-center bg-white/10 hover:bg-white/20 rounded-lg transition-colors z-30"
                  aria-label="Предыдущее фото"
                >
                  <ChevronLeft className="w-6 h-6 text-white" />
                </button>

                <button
                  onClick={(e) => {
                    e.stopPropagation();
                    scrollNext();
                  }}
                  className="absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 flex items-center justify-center bg-white/10 hover:bg-white/20 rounded-lg transition-colors z-30"
                  aria-label="Следующее фото"
                >
                  <ChevronRight className="w-6 h-6 text-white" />
                </button>

                {/* Dot Indicators in Fullscreen */}
                <div className="absolute bottom-8 left-1/2 -translate-x-1/2 flex items-center gap-1.5 z-30">
                  {photos.map((_, index) => (
                    <button
                      key={index}
                      onClick={(e) => {
                        e.stopPropagation();
                        scrollTo(index);
                      }}
                      className={cn(
                        "rounded-full transition-all",
                        index === selectedIndex
                          ? "w-2 h-2 bg-white opacity-100"
                          : "w-1.5 h-1.5 bg-white opacity-50 hover:opacity-75"
                      )}
                      aria-label={`Показать фото ${index + 1}`}
                    />
                  ))}
                </div>

                {/* Counter in Fullscreen */}
                <div className="absolute top-4 left-1/2 -translate-x-1/2 bg-black/50 backdrop-blur-sm px-3 py-1.5 rounded-md text-white text-sm font-medium z-30">
                  {selectedIndex + 1} / {photos.length}
                </div>
              </>
            )}
          </div>
        </div>
      )}
    </>
  );
};

export default PropertyMediaGallery;


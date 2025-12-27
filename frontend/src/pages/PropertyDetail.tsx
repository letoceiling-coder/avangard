import { useState, useRef, useEffect } from "react";
import { useParams, Link } from "react-router-dom";
import Header from "@/components/Header";
import Footer from "@/components/Footer";
import { Button } from "@/components/ui/button";
import { 
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from "@/components/ui/breadcrumb";
import { 
  Heart, Phone, MapPin, Maximize2, Home, Calendar, 
  ChevronLeft, ChevronRight, CreditCard, Building2, 
  Car, Shield, Trees, Share2, Check, Copy, MessageCircle,
  Ruler, DoorOpen, Bath, Sparkles, X
} from "lucide-react";
import PropertyCard from "@/components/PropertyCard";
import ViewingRequestForm from "@/components/ViewingRequestForm";
import { useFavorites } from "@/hooks/useFavorites";
import { toast } from "sonner";

const mockProperty = {
  id: "1",
  title: "3-комнатная квартира в ЖК «Белый город»",
  price: 6500000,
  pricePerMeter: 76470,
  mortgagePayment: 58000,
  images: [
    "https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?w=1200&h=800&fit=crop",
    "https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=1200&h=800&fit=crop",
    "https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=1200&h=800&fit=crop",
    "https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=1200&h=800&fit=crop",
    "https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=1200&h=800&fit=crop",
    "https://images.unsplash.com/photo-1560185127-6ed189bf02f4?w=1200&h=800&fit=crop",
  ],
  area: 85,
  rooms: 3,
  floor: 12,
  totalFloors: 25,
  ceilingHeight: 3.2,
  address: "ул. Победы, 89, Белгород",
  district: "Центральный район",
  type: "Новостройка",
  year: 2024,
  readiness: "Сдан",
  finishing: "Чистовая",
  material: "Монолит-кирпич",
  bathroom: "Раздельный",
  balcony: "2 лоджии",
  description: "Премиальная квартира в современном жилом комплексе «Белый город». Панорамное остекление, высокие потолки 3.2 м, чистовая отделка. Развитая инфраструктура, подземная парковка, детские площадки. Рядом парк, школа, торговый центр. Квартира готова к проживанию. Отличная планировка с просторной кухней-гостиной и изолированными спальнями. Вид на благоустроенный двор.",
  features: [
    "Панорамное остекление",
    "Высокие потолки 3.2 м",
    "Чистовая отделка",
    "Подземная парковка",
    "Консьерж-сервис",
    "Детская площадка",
    "Видеонаблюдение",
    "Закрытая территория",
  ],
  complex: "ЖК «Белый город»",
  complexId: "beliy-gorod",
  developer: "Строительная компания «Новый Дом»",
  developerId: "noviy-dom",
  coordinates: [50.5997, 36.5873] as [number, number],
};

const similarProperties = [
  {
    id: "2",
    title: "2-комнатная квартира с видом на парк",
    price: 4800000,
    image: "https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=800&h=600&fit=crop",
    area: 62,
    rooms: 2,
    floor: 8,
    address: "пр. Славы, 45",
    type: "Новостройка",
  },
  {
    id: "3",
    title: "Пентхаус в элитном ЖК «Империал»",
    price: 15000000,
    image: "https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=800&h=600&fit=crop",
    area: 145,
    rooms: 4,
    floor: 25,
    address: "ул. Щорса, 2",
    type: "Новостройка",
  },
  {
    id: "4",
    title: "Студия в ЖК «Современник»",
    price: 2900000,
    image: "https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=800&h=600&fit=crop",
    area: 28,
    rooms: 1,
    floor: 5,
    address: "ул. Губкина, 17",
    type: "Новостройка",
  },
];

const PropertyDetail = () => {
  const { id } = useParams();
  const [currentImageIndex, setCurrentImageIndex] = useState(0);
  const [showAllPhotos, setShowAllPhotos] = useState(false);
  const [showFullDescription, setShowFullDescription] = useState(false);
  const { isFavorite, addToFavorites, removeFromFavorites } = useFavorites();
  const galleryRef = useRef<HTMLDivElement>(null);
  const touchStartX = useRef<number>(0);
  const touchEndX = useRef<number>(0);
  
  const favorite = isFavorite(mockProperty.id);

  const formatPrice = (price: number) => {
    return price.toLocaleString("ru-RU") + " ₽";
  };

  const calculateMortgage = (price: number, rate = 3, years = 20) => {
    const monthlyRate = rate / 100 / 12;
    const months = years * 12;
    const payment = price * (monthlyRate * Math.pow(1 + monthlyRate, months)) / (Math.pow(1 + monthlyRate, months) - 1);
    return Math.round(payment);
  };

  const toggleFavorite = () => {
    if (favorite) {
      removeFromFavorites(mockProperty.id);
      toast.success("Удалено из избранного");
    } else {
      addToFavorites({
        id: mockProperty.id,
        title: mockProperty.title,
        price: mockProperty.price,
        image: mockProperty.images[0],
        area: mockProperty.area,
        rooms: mockProperty.rooms,
        floor: mockProperty.floor,
        address: mockProperty.address,
        type: mockProperty.type,
      });
      toast.success("Добавлено в избранное");
    }
  };

  const handleShare = () => {
    navigator.clipboard.writeText(window.location.href);
    toast.success("Ссылка скопирована");
  };

  const nextImage = () => {
    setCurrentImageIndex((prev) => (prev + 1) % mockProperty.images.length);
  };

  const prevImage = () => {
    setCurrentImageIndex((prev) => (prev - 1 + mockProperty.images.length) % mockProperty.images.length);
  };

  const handlePhoneClick = () => {
    toast.success("Номер телефона: +7 (999) 123-45-67");
  };

  const handleRequestClick = () => {
    toast.success("Форма заявки открыта");
  };

  // Swipe handlers for mobile gallery
  const handleTouchStart = (e: React.TouchEvent) => {
    touchStartX.current = e.touches[0].clientX;
  };

  const handleTouchMove = (e: React.TouchEvent) => {
    touchEndX.current = e.touches[0].clientX;
  };

  const handleTouchEnd = () => {
    if (!touchStartX.current || !touchEndX.current) return;
    
    const distance = touchStartX.current - touchEndX.current;
    const minSwipeDistance = 50;

    if (distance > minSwipeDistance) {
      // Swipe left - next image
      nextImage();
    } else if (distance < -minSwipeDistance) {
      // Swipe right - previous image
      prevImage();
    }

    touchStartX.current = 0;
    touchEndX.current = 0;
  };

  return (
    <div className="min-h-screen bg-background">
      <Header />

      <main className="container mx-auto px-4 py-6 md:py-8 pb-24 lg:pb-8">
        {/* Breadcrumbs */}
        <Breadcrumb className="mb-6">
          <BreadcrumbList>
            <BreadcrumbItem>
              <BreadcrumbLink asChild>
                <Link to="/" className="text-muted-foreground hover:text-primary transition-colors">
                  Главная
                </Link>
              </BreadcrumbLink>
            </BreadcrumbItem>
            <BreadcrumbSeparator />
            <BreadcrumbItem>
              <BreadcrumbLink asChild>
                <Link to="/catalog" className="text-muted-foreground hover:text-primary transition-colors">
                  Новостройки
                </Link>
              </BreadcrumbLink>
            </BreadcrumbItem>
            <BreadcrumbSeparator />
            <BreadcrumbItem>
              <BreadcrumbLink asChild>
                <Link to={`/complex/${mockProperty.complexId}`} className="text-muted-foreground hover:text-primary transition-colors">
                  {mockProperty.complex}
                </Link>
              </BreadcrumbLink>
            </BreadcrumbItem>
            <BreadcrumbSeparator />
            <BreadcrumbItem>
              <BreadcrumbPage className="text-foreground font-medium truncate max-w-[200px]">
                {mockProperty.title}
              </BreadcrumbPage>
            </BreadcrumbItem>
          </BreadcrumbList>
        </Breadcrumb>

        {/* Gallery Section */}
        <div className="mb-8">
          {/* Desktop Gallery Grid */}
          <div className="hidden md:grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
            {/* Main Image */}
            <div 
              className="md:col-span-2 lg:col-span-2 lg:row-span-2 relative aspect-video lg:aspect-auto lg:h-[480px] rounded-2xl overflow-hidden group cursor-pointer shadow-md"
              onClick={() => { setCurrentImageIndex(0); setShowAllPhotos(true); }}
            >
              <img
                src={mockProperty.images[0]}
                alt={mockProperty.title}
                className="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
              />
              <div className="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity" />
              
              {/* Price Badge */}
              <div className="absolute bottom-4 left-4 bg-white/95 backdrop-blur-sm px-4 py-2 rounded-xl shadow-lg">
                <span className="text-lg font-bold text-foreground">{formatPrice(mockProperty.price)}</span>
              </div>
            </div>

            {/* Thumbnail Images */}
            {mockProperty.images.slice(1, 5).map((image, index) => (
              <div 
                key={index} 
                className={`hidden md:block relative aspect-video lg:h-[234px] rounded-2xl overflow-hidden group cursor-pointer shadow-sm ${
                  index === 3 ? "relative" : ""
                }`}
                onClick={() => { setCurrentImageIndex(index + 1); setShowAllPhotos(true); }}
              >
                <img
                  src={image}
                  alt={`${mockProperty.title} ${index + 2}`}
                  className="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                />
                <div className="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent opacity-0 group-hover:opacity-100 transition-opacity" />
                
                {/* Show More Overlay */}
                {index === 3 && mockProperty.images.length > 5 && (
                  <div className="absolute inset-0 bg-black/60 flex items-center justify-center">
                    <span className="text-white font-semibold text-lg">+{mockProperty.images.length - 5} фото</span>
                  </div>
                )}
              </div>
            ))}
          </div>

          {/* Mobile Swipeable Gallery */}
          <div 
            ref={galleryRef}
            className="md:hidden relative aspect-[4/3] rounded-2xl overflow-hidden bg-muted/30"
            onTouchStart={handleTouchStart}
            onTouchMove={handleTouchMove}
            onTouchEnd={handleTouchEnd}
          >
            <div 
              className="flex transition-transform duration-300 ease-out h-full"
              style={{ transform: `translateX(-${currentImageIndex * 100}%)` }}
            >
              {mockProperty.images.map((image, index) => (
                <div key={index} className="min-w-full h-full relative">
                  <img
                    src={image}
                    alt={`${mockProperty.title} ${index + 1}`}
                    className="w-full h-full object-cover"
                  />
                  {/* Image Counter */}
                  <div className="absolute top-4 right-4 bg-black/50 backdrop-blur-sm px-3 py-1.5 rounded-full text-white text-sm font-medium">
                    {index + 1} / {mockProperty.images.length}
                  </div>
                </div>
              ))}
            </div>
            
            {/* Navigation Dots */}
            <div className="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2">
              {mockProperty.images.map((_, index) => (
                <button
                  key={index}
                  onClick={() => setCurrentImageIndex(index)}
                  className={`w-2 h-2 rounded-full transition-all ${
                    currentImageIndex === index 
                      ? "bg-white w-6" 
                      : "bg-white/50 hover:bg-white/75"
                  }`}
                />
              ))}
            </div>

            {/* Swipe Navigation Arrows */}
            {mockProperty.images.length > 1 && (
              <>
                <button
                  onClick={prevImage}
                  className="absolute left-2 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-black/50 backdrop-blur-sm flex items-center justify-center text-white hover:bg-black/70 transition-colors"
                >
                  <ChevronLeft className="w-5 h-5" />
                </button>
                <button
                  onClick={nextImage}
                  className="absolute right-2 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-black/50 backdrop-blur-sm flex items-center justify-center text-white hover:bg-black/70 transition-colors"
                >
                  <ChevronRight className="w-5 h-5" />
                </button>
              </>
            )}
          </div>

          {/* View All Button */}
          <button 
            onClick={() => setShowAllPhotos(true)}
            className="mt-4 text-primary font-medium hover:underline flex items-center gap-2 transition-colors"
          >
            <Maximize2 className="w-4 h-4" />
            Показать все ({mockProperty.images.length})
          </button>
        </div>

        {/* Fullscreen Gallery Modal */}
        {showAllPhotos && (
          <div className="fixed inset-0 z-50 bg-black/95 flex items-center justify-center">
            <button
              onClick={() => setShowAllPhotos(false)}
              className="absolute top-4 right-4 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition-colors z-10"
            >
              <X className="w-6 h-6" />
            </button>
            
            <button
              onClick={prevImage}
              className="absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors z-10"
            >
              <ChevronLeft className="w-6 h-6 text-white" />
            </button>
            
            <img
              src={mockProperty.images[currentImageIndex]}
              alt={mockProperty.title}
              className="max-w-full max-h-[85vh] object-contain"
            />
            
            <button
              onClick={nextImage}
              className="absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors z-10"
            >
              <ChevronRight className="w-6 h-6 text-white" />
            </button>

            {/* Thumbnails */}
            <div className="absolute bottom-8 left-1/2 -translate-x-1/2 flex gap-2 max-w-[80%] overflow-x-auto">
              {mockProperty.images.map((img, index) => (
                <button
                  key={index}
                  onClick={() => setCurrentImageIndex(index)}
                  className={`flex-shrink-0 w-16 h-12 rounded-lg overflow-hidden transition-all ${
                    currentImageIndex === index ? "ring-2 ring-white scale-110" : "opacity-50 hover:opacity-100"
                  }`}
                >
                  <img src={img} alt="" className="w-full h-full object-cover" />
                </button>
              ))}
            </div>

            <div className="absolute bottom-4 left-1/2 -translate-x-1/2 text-white/80 text-sm">
              {currentImageIndex + 1} / {mockProperty.images.length}
            </div>
          </div>
        )}

        {/* Main Content Grid */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Left Content (60%) */}
          <div className="lg:col-span-2 space-y-6">
            {/* Title & Address */}
            <div>
              <div className="flex items-center gap-2 mb-3">
                <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-primary/10 text-primary">
                  {mockProperty.type}
                </span>
                <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                  {mockProperty.readiness}
                </span>
              </div>
              <h1 className="text-2xl md:text-3xl font-display font-bold text-foreground mb-3">
                {mockProperty.title}
              </h1>
              <div className="flex items-center gap-2 text-muted-foreground">
                <MapPin className="w-4 h-4 text-primary flex-shrink-0" />
                <span>{mockProperty.address}</span>
              </div>
            </div>

            {/* Quick Stats */}
            <div className="bg-card rounded-2xl border border-border p-6 shadow-card">
              <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div className="text-center">
                  <div className="text-3xl font-bold text-foreground">{mockProperty.area}</div>
                  <div className="text-sm text-muted-foreground">м²</div>
                </div>
                <div className="text-center">
                  <div className="text-3xl font-bold text-foreground">{mockProperty.rooms}</div>
                  <div className="text-sm text-muted-foreground">комнат</div>
                </div>
                <div className="text-center">
                  <div className="text-3xl font-bold text-foreground">{mockProperty.floor}/{mockProperty.totalFloors}</div>
                  <div className="text-sm text-muted-foreground">этаж</div>
                </div>
                <div className="text-center">
                  <div className="text-3xl font-bold text-foreground">{mockProperty.ceilingHeight}</div>
                  <div className="text-sm text-muted-foreground">м высота</div>
                </div>
              </div>
            </div>

            {/* Description */}
            <div className="bg-card rounded-2xl border border-border p-6 shadow-card">
              <h2 className="text-xl font-display font-semibold mb-4">Описание</h2>
              <p className={`text-muted-foreground leading-relaxed ${!showFullDescription ? "line-clamp-3" : ""}`}>
                {mockProperty.description}
              </p>
              {mockProperty.description.length > 200 && (
                <button 
                  onClick={() => setShowFullDescription(!showFullDescription)}
                  className="mt-3 text-primary font-medium hover:underline"
                >
                  {showFullDescription ? "Свернуть" : "Читать полностью →"}
                </button>
              )}
            </div>

            {/* Parameters Block - с иконками и grid */}
            <div className="bg-card rounded-2xl border border-border p-6 shadow-card">
              <h2 className="text-xl font-display font-semibold mb-6">Параметры</h2>
              <div className="grid grid-cols-2 sm:grid-cols-3 gap-4">
                {[
                  { label: "Площадь", value: `${mockProperty.area} м²`, icon: Maximize2 },
                  { label: "Комнат", value: mockProperty.rooms, icon: DoorOpen },
                  { label: "Этаж", value: `${mockProperty.floor}/${mockProperty.totalFloors}`, icon: Building2 },
                  { label: "Высота потолков", value: `${mockProperty.ceilingHeight} м`, icon: Ruler },
                  { label: "Год постройки", value: mockProperty.year, icon: Calendar },
                  { label: "Материал", value: mockProperty.material, icon: Building2 },
                  { label: "Санузел", value: mockProperty.bathroom, icon: Bath },
                  { label: "Балкон", value: mockProperty.balcony, icon: DoorOpen },
                  { label: "Отделка", value: mockProperty.finishing, icon: Sparkles },
                ].map((item, index) => (
                  <div key={index} className="flex flex-col items-center p-4 rounded-xl bg-muted/30 border border-border/50">
                    <div className="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center mb-3">
                      <item.icon className="w-6 h-6 text-primary" />
                    </div>
                    <span className="text-xs text-muted-foreground text-center mb-1">{item.label}</span>
                    <span className="font-semibold text-foreground text-center">{item.value}</span>
                  </div>
                ))}
              </div>
            </div>

            {/* Features */}
            <div className="bg-card rounded-2xl border border-border p-6 shadow-card">
              <h2 className="text-xl font-display font-semibold mb-4">Особенности и удобства</h2>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                {mockProperty.features.map((feature, index) => (
                  <div key={index} className="flex items-center gap-3 p-3 rounded-xl bg-muted/30">
                    <div className="w-6 h-6 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                      <Check className="w-3.5 h-3.5 text-primary" />
                    </div>
                    <span className="text-foreground">{feature}</span>
                  </div>
                ))}
              </div>
            </div>

            {/* Infrastructure */}
            <div className="bg-card rounded-2xl border border-border p-6 shadow-card">
              <h2 className="text-xl font-display font-semibold mb-4">Инфраструктура</h2>
              <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                {[
                  { icon: Car, label: "Парковка", time: "1 мин" },
                  { icon: Trees, label: "Парк", time: "5 мин" },
                  { icon: Building2, label: "Школа", time: "7 мин" },
                  { icon: Shield, label: "Поликлиника", time: "10 мин" },
                ].map((item, index) => (
                  <div key={index} className="text-center p-4 rounded-xl bg-muted/30">
                    <div className="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center mx-auto mb-2">
                      <item.icon className="w-5 h-5 text-primary" />
                    </div>
                    <p className="font-medium text-foreground text-sm">{item.label}</p>
                    <p className="text-xs text-muted-foreground">{item.time}</p>
                  </div>
                ))}
              </div>
            </div>

            {/* Map Placeholder */}
            <div className="bg-card rounded-2xl border border-border p-6 shadow-card">
              <div className="flex items-center justify-between mb-4">
                <h2 className="text-xl font-display font-semibold">Расположение на карте</h2>
                <a 
                  href={`https://yandex.ru/maps/?text=${encodeURIComponent(mockProperty.address)}`}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-sm text-primary hover:underline flex items-center gap-1"
                >
                  Открыть в Яндекс.Картах
                  <ChevronRight className="w-4 h-4" />
                </a>
              </div>
              {/* Map Placeholder */}
              <div className="relative h-80 rounded-xl overflow-hidden bg-gradient-to-br from-sky-50 via-blue-50 to-slate-100 border border-border/50">
                {/* Grid pattern */}
                <div 
                  className="absolute inset-0 opacity-20"
                  style={{
                    backgroundImage: `
                      linear-gradient(rgba(100, 150, 200, 0.1) 1px, transparent 1px),
                      linear-gradient(90deg, rgba(100, 150, 200, 0.1) 1px, transparent 1px)
                    `,
                    backgroundSize: '40px 40px'
                  }}
                />
                {/* Map Pin Icon */}
                <div className="absolute inset-0 flex items-center justify-center">
                  <div className="text-center">
                    <MapPin className="w-16 h-16 text-primary/40 mx-auto mb-3" />
                    <p className="text-sm text-muted-foreground font-medium">Карта</p>
                    <p className="text-xs text-muted-foreground/70 mt-1">{mockProperty.address}</p>
                  </div>
                </div>
              </div>
              <p className="mt-3 text-sm text-muted-foreground flex items-center gap-2">
                <MapPin className="w-4 h-4 text-primary" />
                {mockProperty.address}, {mockProperty.district}
              </p>
            </div>
          </div>

          {/* Right Sidebar (40%) - Desktop Only */}
          <div className="hidden lg:block lg:col-span-1">
            <div className="bg-card rounded-2xl border border-border p-6 shadow-card sticky top-24 space-y-6">
              {/* Price Section */}
              <div className="pb-4 border-b border-border">
                <p className="text-sm text-muted-foreground mb-1">Стоимость</p>
                <p className="text-3xl font-bold text-foreground">
                  {formatPrice(mockProperty.price)}
                </p>
                <p className="text-sm text-muted-foreground">
                  {mockProperty.pricePerMeter.toLocaleString("ru-RU")} ₽/м²
                </p>
              </div>

              {/* Mortgage Section */}
              <div className="pb-4 border-b border-border">
                <p className="text-sm text-muted-foreground mb-1">Ипотечный платёж</p>
                <p className="text-2xl font-bold text-primary">
                  {calculateMortgage(mockProperty.price).toLocaleString("ru-RU")} ₽
                  <span className="text-sm font-normal text-muted-foreground">/мес</span>
                </p>
                <p className="text-xs text-muted-foreground mt-1">при 3% ставке, 20 лет</p>
                <button className="mt-2 text-sm text-primary hover:underline">
                  Рассчитать →
                </button>
              </div>

              {/* Developer Section */}
              <div className="pb-4 border-b border-border">
                <p className="text-sm text-muted-foreground mb-1">Застройщик</p>
                <p className="font-medium text-foreground">{mockProperty.developer}</p>
                <Link 
                  to={`/developers/${mockProperty.developerId}`}
                  className="text-sm text-primary hover:underline"
                >
                  Другие объекты →
                </Link>
              </div>

              {/* Action Buttons */}
              <div className="space-y-3">
                <Button 
                  variant="primary" 
                  size="lg" 
                  fullWidth
                  leftIcon={<Phone className="w-5 h-5" />}
                  onClick={handlePhoneClick}
                  className="min-h-[44px]"
                  aria-label="Показать номер телефона агента"
                >
                  Показать номер
                </Button>
                <ViewingRequestForm
                  propertyId={mockProperty.id}
                  propertyTitle={mockProperty.title}
                  propertyImage={mockProperty.images[0]}
                  propertyPrice={mockProperty.price}
                />
                <Button
                  variant="ghost"
                  size="md"
                  fullWidth
                  leftIcon={<Heart className={`w-5 h-5 ${favorite ? "fill-primary text-primary" : ""}`} />}
                  onClick={toggleFavorite}
                >
                  {favorite ? "В избранном" : "В избранное"}
                </Button>
              </div>

              {/* Share Section */}
              <div className="pt-4 border-t border-border">
                <p className="text-sm text-muted-foreground mb-3">Поделиться</p>
                <div className="flex gap-2">
                  <button 
                    onClick={handleShare}
                    className="w-10 h-10 rounded-lg bg-muted/50 hover:bg-muted flex items-center justify-center transition-colors"
                  >
                    <Copy className="w-5 h-5 text-muted-foreground" />
                  </button>
                  <a 
                    href={`https://t.me/share/url?url=${encodeURIComponent(window.location.href)}`}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="w-10 h-10 rounded-lg bg-muted/50 hover:bg-muted flex items-center justify-center transition-colors"
                  >
                    <MessageCircle className="w-5 h-5 text-muted-foreground" />
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Similar Properties */}
        <section className="mt-16">
          <div className="flex items-center justify-between mb-6">
            <h2 className="text-2xl font-display font-bold text-foreground">Похожие объекты</h2>
            <Link 
              to="/catalog" 
              className="text-primary font-medium hover:underline flex items-center gap-1"
            >
              Смотреть все
              <ChevronRight className="w-4 h-4" />
            </Link>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {similarProperties.map((property) => (
              <PropertyCard key={property.id} property={property} />
            ))}
          </div>
        </section>
      </main>

      {/* Mobile Sticky Bottom Bar */}
      <div className="fixed bottom-0 left-0 right-0 lg:hidden bg-white border-t border-border/50 p-4 z-50 shadow-[0_-4px_20px_rgba(0,0,0,0.1)]">
        <div className="flex items-center gap-3">
          <Button
            variant="ghost"
            size="icon"
            className={`flex-shrink-0 min-h-[44px] min-w-[44px] w-12 h-12 rounded-xl ${favorite ? "text-primary bg-primary/10" : "text-muted-foreground"}`}
            onClick={toggleFavorite}
            aria-label={favorite ? "Удалить из избранного" : "Добавить в избранное"}
          >
            <Heart className={`w-6 h-6 ${favorite ? "fill-primary text-primary" : ""}`} />
          </Button>
          <Button
            variant="primary"
            size="lg"
            className="flex-1 min-h-[44px] h-12 rounded-xl font-semibold text-base"
            onClick={handleRequestClick}
            aria-label="Оставить заявку на просмотр объекта"
          >
            Оставить заявку
          </Button>
        </div>
      </div>

      <Footer />
    </div>
  );
};

export default PropertyDetail;

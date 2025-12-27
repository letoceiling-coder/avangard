import { useState } from "react";
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
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { 
  Heart, Phone, MapPin, Maximize2,
  ChevronLeft, ChevronRight, Share2, Copy, X,
  MessageCircle, Download, Send
} from "lucide-react";
import YandexMap from "@/components/YandexMap";
import PropertyCard from "@/components/PropertyCard";
import { useFavorites } from "@/hooks/useFavorites";
import { toast } from "sonner";

const mockObject = {
  id: "obj-1",
  title: "3-комнатная квартира в ЖК «Белый город»",
  price: 6500000,
  pricePerMeter: 76470,
  images: [
    "https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?w=1200&h=750&fit=crop",
    "https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=1200&h=750&fit=crop",
    "https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=1200&h=750&fit=crop",
    "https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=1200&h=750&fit=crop",
    "https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=1200&h=750&fit=crop",
    "https://images.unsplash.com/photo-1560185127-6ed189bf02f4?w=1200&h=750&fit=crop",
    "https://images.unsplash.com/photo-1484154218962-a197022b5858?w=1200&h=750&fit=crop",
    "https://images.unsplash.com/photo-1493809842364-78817add7ffb?w=1200&h=750&fit=crop",
  ],
  area: 85,
  rooms: 3,
  floor: 12,
  totalFloors: 25,
  address: "ул. Победы, 89, Белгород",
  district: "Центральный район",
  type: "Новостройка",
  year: 2024,
  description: "Премиальная квартира в современном жилом комплексе «Белый город». Панорамное остекление, высокие потолки 3.2 м, чистовая отделка. Развитая инфраструктура, подземная парковка, детские площадки. Рядом парк, школа, торговый центр. Квартира готова к проживанию. Отличная планировка с просторной кухней-гостиной и изолированными спальнями. Вид на благоустроенный двор.",
  complex: "ЖК «Белый город»",
  complexId: "beliy-gorod",
  coordinates: [50.5997, 36.5873] as [number, number],
  agentPhone: "+7 (999) 123-45-67",
  agentName: "Алексей Иванов",
  pdfUrl: "/documents/property-obj-1.pdf",
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

const ObjectDetail = () => {
  const { id } = useParams();
  const [currentImageIndex, setCurrentImageIndex] = useState(0);
  const [showGalleryModal, setShowGalleryModal] = useState(false);
  const [showRequestModal, setShowRequestModal] = useState(false);
  const [requestForm, setRequestForm] = useState({ name: "", phone: "", message: "" });
  const { isFavorite, addToFavorites, removeFromFavorites } = useFavorites();
  
  const favorite = isFavorite(mockObject.id);

  const formatPrice = (price: number) => {
    return price.toLocaleString("ru-RU") + " ₽";
  };

  const toggleFavorite = () => {
    if (favorite) {
      removeFromFavorites(mockObject.id);
      toast.success("Удалено из избранного");
    } else {
      addToFavorites({
        id: mockObject.id,
        title: mockObject.title,
        price: mockObject.price,
        image: mockObject.images[0],
        area: mockObject.area,
        rooms: mockObject.rooms,
        floor: mockObject.floor,
        address: mockObject.address,
        type: mockObject.type,
      });
      toast.success("Добавлено в избранное");
    }
  };

  const handleShare = () => {
    navigator.clipboard.writeText(window.location.href);
    toast.success("Ссылка скопирована в буфер обмена");
  };

  const nextImage = () => {
    setCurrentImageIndex((prev) => (prev + 1) % mockObject.images.length);
  };

  const prevImage = () => {
    setCurrentImageIndex((prev) => (prev - 1 + mockObject.images.length) % mockObject.images.length);
  };

  const handleRequestSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    toast.success("Заявка отправлена! Мы свяжемся с вами в ближайшее время.");
    setShowRequestModal(false);
    setRequestForm({ name: "", phone: "", message: "" });
  };

  const parametersTable = [
    { label: "Тип объекта", value: mockObject.type },
    { label: "Адрес", value: mockObject.address },
    { label: "Район", value: mockObject.district },
    { label: "Жилой комплекс", value: mockObject.complex },
    { label: "Площадь", value: `${mockObject.area} м²` },
    { label: "Количество комнат", value: `${mockObject.rooms}` },
    { label: "Этаж", value: `${mockObject.floor} из ${mockObject.totalFloors}` },
    { label: "Год постройки", value: `${mockObject.year}` },
    { label: "Цена", value: formatPrice(mockObject.price) },
    { label: "Цена за м²", value: `${mockObject.pricePerMeter.toLocaleString("ru-RU")} ₽` },
  ];

  return (
    <div className="min-h-screen bg-background">
      <Header />

      <main className="container mx-auto px-4 py-6 md:py-8 pb-32 md:pb-8">
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
                  Каталог
                </Link>
              </BreadcrumbLink>
            </BreadcrumbItem>
            <BreadcrumbSeparator />
            <BreadcrumbItem>
              <BreadcrumbPage className="text-foreground font-medium truncate max-w-[200px]">
                {mockObject.title}
              </BreadcrumbPage>
            </BreadcrumbItem>
          </BreadcrumbList>
        </Breadcrumb>

        {/* Main Layout: 65% Left / 35% Right */}
        <div className="flex flex-col lg:flex-row gap-8">
          {/* Left Column - 65% */}
          <div className="w-full lg:w-[65%] space-y-8">
            {/* Gallery Section */}
            <div className="relative">
              {/* Main Image */}
              <div 
                className="relative aspect-[16/10] rounded-xl overflow-hidden cursor-pointer group"
                onClick={() => setShowGalleryModal(true)}
              >
                <img
                  src={mockObject.images[currentImageIndex]}
                  alt={mockObject.title}
                  className="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                />
                
                {/* Fullscreen Icon */}
                <button
                  className="absolute top-4 right-4 w-10 h-10 rounded-xl bg-background/80 backdrop-blur-sm flex items-center justify-center hover:bg-background transition-colors shadow-lg"
                  onClick={(e) => {
                    e.stopPropagation();
                    setShowGalleryModal(true);
                  }}
                >
                  <Maximize2 className="w-5 h-5 text-foreground" />
                </button>

                {/* Heart Icon */}
                <button
                  className={`absolute top-4 left-4 w-10 h-10 rounded-xl flex items-center justify-center transition-all shadow-lg ${
                    favorite 
                      ? "bg-primary text-white" 
                      : "bg-background/80 backdrop-blur-sm hover:bg-white text-foreground"
                  }`}
                  onClick={(e) => {
                    e.stopPropagation();
                    toggleFavorite();
                  }}
                >
                  <Heart className={`w-5 h-5 ${favorite ? "fill-current" : ""}`} />
                </button>

                {/* Image Counter */}
                <div className="absolute bottom-4 right-4 bg-background/80 backdrop-blur-sm px-3 py-1.5 rounded-lg text-sm font-medium shadow-lg">
                  {currentImageIndex + 1} / {mockObject.images.length}
                </div>
              </div>

              {/* Thumbnails Grid - 4 columns */}
              <div className="grid grid-cols-4 gap-3 mt-3">
                {mockObject.images.slice(0, 8).map((image, index) => (
                  <button
                    key={index}
                    onClick={() => setCurrentImageIndex(index)}
                    className={`aspect-[16/10] rounded-lg overflow-hidden transition-all ${
                      currentImageIndex === index 
                        ? "ring-2 ring-primary ring-offset-2" 
                        : "opacity-70 hover:opacity-100"
                    }`}
                  >
                    <img 
                      src={image} 
                      alt={`${mockObject.title} ${index + 1}`}
                      className="w-full h-full object-cover"
                    />
                  </button>
                ))}
              </div>
            </div>

            {/* Title & Description */}
            <div className="space-y-4">
              <h1 className="text-2xl md:text-3xl font-display font-bold text-foreground">
                {mockObject.title}
              </h1>
              <div className="flex items-center gap-2 text-muted-foreground">
                <MapPin className="w-4 h-4 text-primary flex-shrink-0" />
                <span>{mockObject.address}</span>
              </div>
              <p className="text-muted-foreground leading-relaxed">
                {mockObject.description}
              </p>
            </div>

            {/* Parameters Table */}
            <div className="bg-card rounded-2xl border border-border overflow-hidden shadow-card">
              <div className="px-6 py-4 border-b border-border bg-muted/30">
                <h2 className="text-lg font-display font-semibold text-foreground">Параметры объекта</h2>
              </div>
              <div className="divide-y divide-border">
                {parametersTable.map((param, index) => (
                  <div 
                    key={index}
                    className="flex items-center justify-between px-6 py-3 hover:bg-muted/20 transition-colors"
                  >
                    <span className="text-muted-foreground">{param.label}</span>
                    <span className="font-medium text-foreground text-right">{param.value}</span>
                  </div>
                ))}
              </div>
            </div>

            {/* Map Section */}
            <div className="bg-card rounded-2xl border border-border p-6 shadow-card">
              <div className="flex items-center justify-between mb-4">
                <h2 className="text-lg font-display font-semibold text-foreground">Расположение</h2>
                <a 
                  href={`https://yandex.ru/maps/?text=${encodeURIComponent(mockObject.address)}`}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-sm text-primary hover:underline flex items-center gap-1"
                >
                  Открыть в Яндекс.Картах
                  <ChevronRight className="w-4 h-4" />
                </a>
              </div>
              <YandexMap 
                address={mockObject.address}
                coordinates={mockObject.coordinates}
                zoom={16}
                className="h-72 md:h-80 rounded-xl"
              />
              <p className="mt-3 text-sm text-muted-foreground flex items-center gap-2">
                <MapPin className="w-4 h-4 text-primary" />
                {mockObject.address}, {mockObject.district}
              </p>
            </div>

            {/* Bottom Actions */}
            <div className="flex flex-wrap gap-3">
              {mockObject.pdfUrl && (
                <Button
                  variant="outline"
                  leftIcon={<Download className="w-4 h-4" />}
                  onClick={() => toast.info("PDF документ будет скачан")}
                >
                  Скачать PDF
                </Button>
              )}
              <Button
                variant="outline"
                leftIcon={<Share2 className="w-4 h-4" />}
                onClick={handleShare}
              >
                Поделиться
              </Button>
            </div>
          </div>

          {/* Right Column - 35% - Sticky Price Card (Desktop) */}
          <div className="hidden lg:block w-[35%]">
            <div className="sticky top-24 bg-card rounded-2xl border border-border p-6 shadow-card space-y-6">
              {/* Price */}
              <div>
                <h2 className="text-3xl font-bold text-primary">
                  {formatPrice(mockObject.price)}
                </h2>
                <p className="text-sm text-muted-foreground mt-1">
                  {mockObject.pricePerMeter.toLocaleString("ru-RU")} ₽/м²
                </p>
              </div>

              {/* Quick Params */}
              <div className="flex items-center gap-4 py-4 border-y border-border">
                <div className="text-center flex-1">
                  <p className="text-xl font-bold text-foreground">{mockObject.area}</p>
                  <p className="text-xs text-muted-foreground">м²</p>
                </div>
                <div className="w-px h-8 bg-border" />
                <div className="text-center flex-1">
                  <p className="text-xl font-bold text-foreground">{mockObject.rooms}</p>
                  <p className="text-xs text-muted-foreground">комнат</p>
                </div>
                <div className="w-px h-8 bg-border" />
                <div className="text-center flex-1">
                  <p className="text-xl font-bold text-foreground">{mockObject.floor}/{mockObject.totalFloors}</p>
                  <p className="text-xs text-muted-foreground">этаж</p>
                </div>
              </div>

              {/* Action Buttons */}
              <div className="space-y-3">
                <Button
                  variant="primary"
                  size="lg"
                  fullWidth
                  leftIcon={<Phone className="w-5 h-5" />}
                  asChild
                >
                  <a href={`tel:${mockObject.agentPhone.replace(/\D/g, '')}`}>
                    ☎ Позвонить
                  </a>
                </Button>

                <Button
                  variant="outline"
                  size="lg"
                  fullWidth
                  leftIcon={<Send className="w-5 h-5" />}
                  asChild
                >
                  <a 
                    href={`https://t.me/+${mockObject.agentPhone.replace(/\D/g, '')}`}
                    target="_blank"
                    rel="noopener noreferrer"
                  >
                    ✉️ Telegram
                  </a>
                </Button>

                <Button
                  variant="outline"
                  size="lg"
                  fullWidth
                  leftIcon={<MessageCircle className="w-5 h-5" />}
                  asChild
                >
                  <a 
                    href={`https://wa.me/${mockObject.agentPhone.replace(/\D/g, '')}`}
                    target="_blank"
                    rel="noopener noreferrer"
                  >
                    💬 WhatsApp
                  </a>
                </Button>

                <Dialog open={showRequestModal} onOpenChange={setShowRequestModal}>
                  <DialogTrigger asChild>
                    <Button
                      variant="secondary"
                      size="lg"
                      fullWidth
                    >
                      📋 Оставить заявку
                    </Button>
                  </DialogTrigger>
                  <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                      <DialogTitle>Оставить заявку</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleRequestSubmit} className="space-y-4 mt-4">
                      <div>
                        <Input
                          placeholder="Ваше имя"
                          value={requestForm.name}
                          onChange={(e) => setRequestForm(prev => ({ ...prev, name: e.target.value }))}
                          required
                        />
                      </div>
                      <div>
                        <Input
                          placeholder="Телефон"
                          type="tel"
                          value={requestForm.phone}
                          onChange={(e) => setRequestForm(prev => ({ ...prev, phone: e.target.value }))}
                          required
                        />
                      </div>
                      <div>
                        <Textarea
                          placeholder="Сообщение (необязательно)"
                          value={requestForm.message}
                          onChange={(e) => setRequestForm(prev => ({ ...prev, message: e.target.value }))}
                          rows={3}
                        />
                      </div>
                      <Button type="submit" variant="primary" fullWidth>
                        Отправить заявку
                      </Button>
                    </form>
                  </DialogContent>
                </Dialog>
              </div>

              {/* Agent Info */}
              <div className="pt-4 border-t border-border">
                <p className="text-sm text-muted-foreground mb-1">Агент</p>
                <p className="font-medium text-foreground">{mockObject.agentName}</p>
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

      {/* Mobile Sticky Price Card (Bottom Fixed) */}
      <div className="fixed bottom-0 left-0 right-0 lg:hidden bg-background border-t border-border p-4 z-40 shadow-xl">
        <div className="flex items-center gap-3">
          <div className="flex-1 min-w-0">
            <p className="text-lg font-bold text-primary truncate">{formatPrice(mockObject.price)}</p>
            <p className="text-xs text-muted-foreground">
              {mockObject.area} м² · {mockObject.rooms} комн. · {mockObject.floor} этаж
            </p>
          </div>
          <Button
            variant="ghost"
            size="icon"
            className={`flex-shrink-0 ${favorite ? "text-primary" : ""}`}
            onClick={toggleFavorite}
          >
            <Heart className={`w-5 h-5 ${favorite ? "fill-primary" : ""}`} />
          </Button>
          <Button
            variant="primary"
            size="md"
            leftIcon={<Phone className="w-4 h-4" />}
            asChild
          >
            <a href={`tel:${mockObject.agentPhone.replace(/\D/g, '')}`}>
              Позвонить
            </a>
          </Button>
        </div>
      </div>

      {/* Fullscreen Gallery Modal */}
      {showGalleryModal && (
        <div className="fixed inset-0 z-50 bg-black/95 flex items-center justify-center">
          <button
            onClick={() => setShowGalleryModal(false)}
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
            src={mockObject.images[currentImageIndex]}
            alt={mockObject.title}
            className="max-w-full max-h-[85vh] object-contain"
          />
          
          <button
            onClick={nextImage}
            className="absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors z-10"
          >
            <ChevronRight className="w-6 h-6 text-white" />
          </button>

          {/* Thumbnails */}
          <div className="absolute bottom-8 left-1/2 -translate-x-1/2 flex gap-2 max-w-[80%] overflow-x-auto pb-2">
            {mockObject.images.map((img, index) => (
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
            {currentImageIndex + 1} / {mockObject.images.length}
          </div>
        </div>
      )}

      <Footer />
    </div>
  );
};

export default ObjectDetail;

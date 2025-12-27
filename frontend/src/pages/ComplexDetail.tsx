import { useState } from "react";
import { useParams, Link } from "react-router-dom";
import Header from "@/components/Header";
import Footer from "@/components/Footer";
import PropertyCard from "@/components/PropertyCard";
import ChessBoard from "@/components/ChessBoard";
import YandexMap from "@/components/YandexMap";
import { Button } from "@/components/ui/button";
import { 
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from "@/components/ui/breadcrumb";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Input } from "@/components/ui/input";
import { 
  Building2, MapPin, Calendar, Shield, Home, Car, Trees, 
  GraduationCap, ShoppingBag, Heart, Phone, ChevronRight,
  Camera, Check, Users, Maximize2
} from "lucide-react";
import { toast } from "sonner";

const mockComplex = {
  id: "1",
  name: "ЖК «Белый город»",
  developer: {
    id: "noviy-dom",
    name: "СК «Новый Дом»",
    logo: "https://images.unsplash.com/photo-1560179707-f14e90ef3623?w=100&h=100&fit=crop",
    description: "Надёжный застройщик с 15-летним опытом работы. Более 50 сданных объектов в Белгородской области.",
    projectsCount: 12,
  },
  address: "ул. Победы, 89, Белгород",
  district: "Центральный район",
  minPrice: 2800000,
  maxPrice: 15000000,
  pricePerMeter: 76000,
  apartmentCount: 420,
  completion: "IV кв. 2024",
  readiness: "Сдан",
  type: "Монолит-кирпич",
  class: "Комфорт+",
  floors: "16-25",
  parking: "Подземная, 280 м/м",
  ceilingHeight: "2.8-3.2 м",
  description: "Современный жилой комплекс в развивающемся районе города. Комплекс состоит из трёх корпусов высотой от 16 до 25 этажей. Закрытая благоустроенная территория с детскими площадками, зонами отдыха и подземной парковкой. Рядом расположены школы, детские сады, торговые центры и парки.",
  fullDescription: `ЖК «Белый город» — это современный жилой комплекс комфорт-класса, расположенный в одном из самых перспективных районов Белгорода.

Комплекс состоит из трёх монолитно-кирпичных корпусов высотой от 16 до 25 этажей. Архитектурный проект выполнен в современном стиле с использованием качественных фасадных материалов.

На территории комплекса предусмотрены:
• Закрытый благоустроенный двор
• Детские игровые площадки для разных возрастов
• Зоны отдыха с ландшафтным дизайном
• Подземный паркинг на 280 машиномест
• Гостевая парковка
• Колясочные на первых этажах

Квартиры с продуманными планировками от студий до 4-комнатных. Высота потолков от 2.8 до 3.2 метра в зависимости от этажа. Все квартиры сдаются с чистовой отделкой или под ключ.`,
  images: [
    "https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?w=1200&h=800&fit=crop",
    "https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=1200&h=800&fit=crop",
    "https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=1200&h=800&fit=crop",
    "https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=1200&h=800&fit=crop",
    "https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=1200&h=800&fit=crop",
  ],
  constructionImages: [
    { date: "Декабрь 2024", image: "https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?w=800&h=600&fit=crop", description: "Сдача в эксплуатацию" },
    { date: "Сентябрь 2024", image: "https://images.unsplash.com/photo-1503387762-592deb58ef4e?w=800&h=600&fit=crop", description: "Отделка фасадов" },
    { date: "Июнь 2024", image: "https://images.unsplash.com/photo-1504307651254-35680f356dfd?w=800&h=600&fit=crop", description: "Монтаж инженерных систем" },
    { date: "Март 2024", image: "https://images.unsplash.com/photo-1541888946425-d81bb19240f5?w=800&h=600&fit=crop", description: "Строительство этажей" },
  ],
  amenities: [
    { icon: Shield, label: "Охраняемая территория", description: "Видеонаблюдение 24/7" },
    { icon: Car, label: "Подземный паркинг", description: "280 машиномест" },
    { icon: Trees, label: "Благоустроенный двор", description: "Ландшафтный дизайн" },
    { icon: Users, label: "Детские площадки", description: "Для разных возрастов" },
    { icon: Home, label: "Консьерж-сервис", description: "Круглосуточно" },
    { icon: ShoppingBag, label: "Коммерция на 1 этаже", description: "Магазины и кафе" },
  ],
  infrastructure: [
    { icon: GraduationCap, label: "Школа №45", distance: "300 м", time: "4 мин" },
    { icon: Trees, label: "Парк Победы", distance: "500 м", time: "7 мин" },
    { icon: ShoppingBag, label: "ТЦ «Мега»", distance: "1.2 км", time: "15 мин" },
    { icon: Car, label: "Остановка транспорта", distance: "100 м", time: "2 мин" },
  ],
  coordinates: [50.5997, 36.5873] as [number, number],
};

const mockChessApartments = [
  { id: "c1", section: 1, floor: 16, number: "161", rooms: 1, area: 28, price: 2800000, status: "available" as const },
  { id: "c2", section: 1, floor: 15, number: "151", rooms: 2, area: 54, price: 4500000, status: "available" as const },
  { id: "c3", section: 1, floor: 14, number: "141", rooms: 3, area: 85, price: 6500000, status: "booked" as const },
  { id: "c4", section: 1, floor: 13, number: "131", rooms: 1, area: 30, price: 2900000, status: "sold" as const },
  { id: "c5", section: 1, floor: 12, number: "121", rooms: 2, area: 52, price: 4300000, status: "available" as const },
  { id: "c6", section: 1, floor: 11, number: "111", rooms: 3, area: 82, price: 6200000, status: "available" as const },
  { id: "c7", section: 1, floor: 10, number: "101", rooms: 1, area: 29, price: 2850000, status: "sold" as const },
  { id: "c8", section: 2, floor: 16, number: "162", rooms: 2, area: 56, price: 4800000, status: "available" as const },
  { id: "c9", section: 2, floor: 15, number: "152", rooms: 3, area: 88, price: 6800000, status: "booked" as const },
  { id: "c10", section: 2, floor: 14, number: "142", rooms: 1, area: 27, price: 2750000, status: "available" as const },
  { id: "c11", section: 2, floor: 13, number: "132", rooms: 2, area: 55, price: 4600000, status: "available" as const },
  { id: "c12", section: 2, floor: 12, number: "122", rooms: 3, area: 84, price: 6400000, status: "sold" as const },
  { id: "c13", section: 2, floor: 11, number: "112", rooms: 1, area: 31, price: 2950000, status: "available" as const },
  { id: "c14", section: 2, floor: 10, number: "102", rooms: 2, area: 53, price: 4400000, status: "booked" as const },
  { id: "c15", section: 3, floor: 16, number: "163", rooms: 3, area: 90, price: 7200000, status: "available" as const },
  { id: "c16", section: 3, floor: 15, number: "153", rooms: 1, area: 28, price: 2800000, status: "sold" as const },
  { id: "c17", section: 3, floor: 14, number: "143", rooms: 2, area: 57, price: 4900000, status: "available" as const },
  { id: "c18", section: 3, floor: 13, number: "133", rooms: 3, area: 86, price: 6600000, status: "available" as const },
  { id: "c19", section: 3, floor: 12, number: "123", rooms: 1, area: 29, price: 2850000, status: "booked" as const },
  { id: "c20", section: 3, floor: 11, number: "113", rooms: 2, area: 54, price: 4500000, status: "available" as const },
  { id: "c21", section: 3, floor: 10, number: "103", rooms: 3, area: 83, price: 6300000, status: "sold" as const },
];

const availableApartments = [
  {
    id: "1",
    title: "3-комнатная квартира",
    price: 6500000,
    image: "https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?w=800&h=600&fit=crop",
    area: 85,
    rooms: 3,
    floor: 12,
    address: "ул. Победы, 89",
    type: "Новостройка",
  },
  {
    id: "2",
    title: "2-комнатная квартира",
    price: 4500000,
    image: "https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=800&h=600&fit=crop",
    area: 54,
    rooms: 2,
    floor: 15,
    address: "ул. Победы, 89",
    type: "Новостройка",
  },
  {
    id: "3",
    title: "Студия",
    price: 2800000,
    image: "https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=800&h=600&fit=crop",
    area: 28,
    rooms: 1,
    floor: 5,
    address: "ул. Победы, 89",
    type: "Новостройка",
  },
  {
    id: "4",
    title: "4-комнатная квартира",
    price: 9500000,
    image: "https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=800&h=600&fit=crop",
    area: 120,
    rooms: 4,
    floor: 20,
    address: "ул. Победы, 89",
    type: "Новостройка",
  },
];

const ComplexDetail = () => {
  const { id } = useParams();
  const [activeTab, setActiveTab] = useState("description");
  const [showAllPhotos, setShowAllPhotos] = useState(false);
  const [currentImageIndex, setCurrentImageIndex] = useState(0);
  const [formData, setFormData] = useState({ name: "", phone: "" });

  const formatPrice = (price: number) => {
    if (price >= 1000000) {
      return `${(price / 1000000).toFixed(1)} млн ₽`;
    }
    return `${price.toLocaleString("ru-RU")} ₽`;
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    toast.success("Заявка отправлена! Мы свяжемся с вами в ближайшее время.");
    setFormData({ name: "", phone: "" });
  };

  return (
    <div className="min-h-screen bg-background">
      <Header />

      {/* Hero Section */}
      <section className="relative h-[400px] md:h-[500px] overflow-hidden">
        <img
          src={mockComplex.images[0]}
          alt={mockComplex.name}
          className="w-full h-full object-cover"
        />
        <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent" />
        
        {/* Hero Content */}
        <div className="absolute bottom-0 left-0 right-0 p-6 md:p-10">
          <div className="container mx-auto">
            <h1 className="text-3xl md:text-5xl font-display font-bold text-white mb-3">
              {mockComplex.name}
            </h1>
            <div className="flex items-center gap-2 text-white/80 mb-4">
              <MapPin className="w-5 h-5" />
              <span>{mockComplex.address}</span>
            </div>
            <div className="flex flex-wrap items-center gap-4">
              <div className="bg-white/20 backdrop-blur-sm px-4 py-2 rounded-xl">
                <span className="text-white font-semibold">{mockComplex.apartmentCount} кв.</span>
              </div>
              <div className="bg-white/20 backdrop-blur-sm px-4 py-2 rounded-xl">
                <span className="text-white font-semibold">От {formatPrice(mockComplex.minPrice)}</span>
              </div>
              <div className="bg-white/20 backdrop-blur-sm px-4 py-2 rounded-xl">
                <span className="text-white font-semibold">{mockComplex.readiness}</span>
              </div>
            </div>
          </div>
        </div>

        {/* Photo Counter */}
        <button 
          onClick={() => setShowAllPhotos(true)}
          className="absolute bottom-6 right-6 md:bottom-10 md:right-10 bg-black/50 backdrop-blur-sm px-4 py-2 rounded-xl flex items-center gap-2 text-white hover:bg-black/70 transition-colors"
        >
          <Camera className="w-5 h-5" />
          <span className="font-medium">{mockComplex.images.length} фото</span>
        </button>
      </section>

      {/* Fullscreen Gallery Modal */}
      {showAllPhotos && (
        <div className="fixed inset-0 z-50 bg-black/95 flex items-center justify-center">
          <button
            onClick={() => setShowAllPhotos(false)}
            className="absolute top-4 right-4 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition-colors z-10"
          >
            ✕
          </button>
          
          <button
            onClick={() => setCurrentImageIndex((prev) => (prev - 1 + mockComplex.images.length) % mockComplex.images.length)}
            className="absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors z-10"
          >
            <ChevronRight className="w-6 h-6 text-white rotate-180" />
          </button>
          
          <img
            src={mockComplex.images[currentImageIndex]}
            alt={mockComplex.name}
            className="max-w-full max-h-[85vh] object-contain"
          />
          
          <button
            onClick={() => setCurrentImageIndex((prev) => (prev + 1) % mockComplex.images.length)}
            className="absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors z-10"
          >
            <ChevronRight className="w-6 h-6 text-white" />
          </button>

          <div className="absolute bottom-8 left-1/2 -translate-x-1/2 flex gap-2">
            {mockComplex.images.map((img, index) => (
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
        </div>
      )}

      <main className="container mx-auto px-4 py-8">
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
                <Link to="/residential-complex" className="text-muted-foreground hover:text-primary transition-colors">
                  Жилые комплексы
                </Link>
              </BreadcrumbLink>
            </BreadcrumbItem>
            <BreadcrumbSeparator />
            <BreadcrumbItem>
              <BreadcrumbPage className="text-foreground font-medium">
                {mockComplex.name}
              </BreadcrumbPage>
            </BreadcrumbItem>
          </BreadcrumbList>
        </Breadcrumb>

        {/* Tabs Navigation */}
        <div className="border-b border-border mb-8 -mx-4 px-4 overflow-x-auto">
          <div className="flex gap-8 min-w-max">
            {[
              { id: "description", label: "Описание" },
              { id: "apartments", label: "Квартиры" },
              { id: "construction", label: "Ход строительства" },
              { id: "location", label: "Расположение" },
            ].map((tab) => (
              <button
                key={tab.id}
                onClick={() => setActiveTab(tab.id)}
                className={`pb-4 font-medium text-base whitespace-nowrap transition-colors ${
                  activeTab === tab.id
                    ? "text-primary border-b-2 border-primary"
                    : "text-muted-foreground hover:text-foreground"
                }`}
              >
                {tab.label}
              </button>
            ))}
          </div>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Main Content */}
          <div className="lg:col-span-2 space-y-8">
            {/* Tab: Description */}
            {activeTab === "description" && (
              <>
                {/* About Section */}
                <div className="bg-card rounded-2xl border border-border p-6 shadow-card">
                  <h2 className="text-xl font-display font-semibold mb-4">О комплексе</h2>
                  <p className="text-muted-foreground leading-relaxed whitespace-pre-line">
                    {mockComplex.fullDescription}
                  </p>
                </div>

                {/* Quick Info */}
                <div className="bg-card rounded-2xl border border-border p-6 shadow-card">
                  <h2 className="text-xl font-display font-semibold mb-6">Основная информация</h2>
                  <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
                    {[
                      { label: "Тип здания", value: mockComplex.type },
                      { label: "Класс", value: mockComplex.class },
                      { label: "Этажность", value: mockComplex.floors },
                      { label: "Готовность", value: mockComplex.readiness },
                      { label: "Паркинг", value: mockComplex.parking },
                      { label: "Потолки", value: mockComplex.ceilingHeight },
                    ].map((item, index) => (
                      <div key={index} className="p-3 rounded-xl bg-muted/30">
                        <p className="text-sm text-muted-foreground mb-1">{item.label}</p>
                        <p className="font-medium text-foreground">{item.value}</p>
                      </div>
                    ))}
                  </div>
                </div>

                {/* Amenities */}
                <div className="bg-card rounded-2xl border border-border p-6 shadow-card">
                  <h2 className="text-xl font-display font-semibold mb-6">Инфраструктура комплекса</h2>
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {mockComplex.amenities.map((amenity, index) => (
                      <div key={index} className="flex items-start gap-4 p-4 rounded-xl bg-muted/30">
                        <div className="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
                          <amenity.icon className="w-6 h-6 text-primary" />
                        </div>
                        <div>
                          <p className="font-medium text-foreground">{amenity.label}</p>
                          <p className="text-sm text-muted-foreground">{amenity.description}</p>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>

                {/* Developer */}
                <div className="bg-card rounded-2xl border border-border p-6 shadow-card">
                  <h2 className="text-xl font-display font-semibold mb-4">О застройщике</h2>
                  <div className="flex flex-col sm:flex-row gap-4">
                    <img
                      src={mockComplex.developer.logo}
                      alt={mockComplex.developer.name}
                      className="w-20 h-20 rounded-xl object-cover"
                    />
                    <div className="flex-1">
                      <p className="font-semibold text-foreground text-lg">{mockComplex.developer.name}</p>
                      <p className="text-muted-foreground mt-1">{mockComplex.developer.description}</p>
                      <Link 
                        to={`/developers/${mockComplex.developer.id}`}
                        className="inline-flex items-center gap-1 text-primary font-medium hover:underline mt-3"
                      >
                        Другие проекты ({mockComplex.developer.projectsCount})
                        <ChevronRight className="w-4 h-4" />
                      </Link>
                    </div>
                  </div>
                </div>
              </>
            )}

            {/* Tab: Apartments */}
            {activeTab === "apartments" && (
              <>
                <div className="bg-card rounded-2xl border border-border p-6 shadow-card">
                  <h2 className="text-xl font-display font-semibold mb-6">Доступные квартиры</h2>
                  
                  <Tabs defaultValue="chessboard" className="w-full">
                    <TabsList className="mb-6 bg-muted/50">
                      <TabsTrigger value="chessboard">Шахматка</TabsTrigger>
                      <TabsTrigger value="list">Список</TabsTrigger>
                    </TabsList>
                    
                    <TabsContent value="chessboard">
                      <ChessBoard apartments={mockChessApartments} complexId={mockComplex.id} />
                    </TabsContent>
                    
                    <TabsContent value="list">
                      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {availableApartments.map((apartment) => (
                          <PropertyCard key={apartment.id} property={apartment} />
                        ))}
                      </div>
                    </TabsContent>
                  </Tabs>
                </div>
              </>
            )}

            {/* Tab: Construction Progress */}
            {activeTab === "construction" && (
              <div className="bg-card rounded-2xl border border-border p-6 shadow-card">
                <h2 className="text-xl font-display font-semibold mb-6">Ход строительства</h2>
                <div className="space-y-6">
                  {mockComplex.constructionImages.map((item, index) => (
                    <div key={index} className="flex flex-col sm:flex-row gap-4">
                      <img
                        src={item.image}
                        alt={item.description}
                        className="w-full sm:w-48 h-32 rounded-xl object-cover"
                      />
                      <div className="flex-1">
                        <p className="text-sm text-primary font-medium">{item.date}</p>
                        <p className="font-medium text-foreground mt-1">{item.description}</p>
                        {index === 0 && (
                          <span className="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-green-100 text-green-700 text-xs font-medium mt-2">
                            <Check className="w-3 h-3" />
                            Завершено
                          </span>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* Tab: Location */}
            {activeTab === "location" && (
              <>
                <div className="bg-card rounded-2xl border border-border p-6 shadow-card">
                  <h2 className="text-xl font-display font-semibold mb-4">Расположение на карте</h2>
                  <YandexMap 
                    address={mockComplex.address}
                    coordinates={mockComplex.coordinates}
                    zoom={15}
                    className="h-80 rounded-xl"
                  />
                  <p className="mt-3 text-sm text-muted-foreground flex items-center gap-2">
                    <MapPin className="w-4 h-4 text-primary" />
                    {mockComplex.address}, {mockComplex.district}
                  </p>
                </div>

                <div className="bg-card rounded-2xl border border-border p-6 shadow-card">
                  <h2 className="text-xl font-display font-semibold mb-6">Инфраструктура рядом</h2>
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {mockComplex.infrastructure.map((item, index) => (
                      <div key={index} className="flex items-center gap-4 p-4 rounded-xl bg-muted/30">
                        <div className="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
                          <item.icon className="w-5 h-5 text-primary" />
                        </div>
                        <div className="flex-1">
                          <p className="font-medium text-foreground">{item.label}</p>
                          <p className="text-sm text-muted-foreground">{item.distance} • {item.time} пешком</p>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              </>
            )}
          </div>

          {/* Sidebar */}
          <div className="lg:col-span-1">
            <div className="bg-card rounded-2xl border border-border p-6 shadow-card sticky top-24 space-y-6">
              {/* Price Info */}
              <div className="pb-4 border-b border-border">
                <p className="text-sm text-muted-foreground mb-1">Цена от</p>
                <p className="text-3xl font-bold text-foreground">
                  {formatPrice(mockComplex.minPrice)}
                </p>
                <p className="text-sm text-muted-foreground mt-1">
                  от {mockComplex.pricePerMeter.toLocaleString("ru-RU")} ₽/м²
                </p>
              </div>

              {/* Quick Stats */}
              <div className="grid grid-cols-2 gap-3 pb-4 border-b border-border">
                <div className="text-center p-3 rounded-xl bg-muted/30">
                  <p className="text-xl font-bold text-foreground">{mockComplex.apartmentCount}</p>
                  <p className="text-xs text-muted-foreground">квартир</p>
                </div>
                <div className="text-center p-3 rounded-xl bg-muted/30">
                  <p className="text-xl font-bold text-foreground">{mockComplex.floors}</p>
                  <p className="text-xs text-muted-foreground">этажей</p>
                </div>
              </div>

              {/* Contact Form */}
              <form onSubmit={handleSubmit} className="space-y-3">
                <h3 className="text-lg font-display font-semibold">Хотите узнать больше?</h3>
                <p className="text-sm text-muted-foreground">
                  Оставьте заявку, и наш менеджер свяжется с вами
                </p>
                <Input
                  type="text"
                  placeholder="Ваше имя"
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  required
                />
                <Input
                  type="tel"
                  placeholder="Телефон"
                  value={formData.phone}
                  onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                  required
                />
                <Button type="submit" variant="primary" size="lg" fullWidth>
                  Оставить заявку
                </Button>
              </form>

              {/* Call Button */}
              <Button 
                variant="secondary" 
                size="lg" 
                fullWidth
                leftIcon={<Phone className="w-5 h-5" />}
                onClick={() => toast.success("Номер телефона: +7 (999) 123-45-67")}
              >
                Позвонить
              </Button>
            </div>
          </div>
        </div>
      </main>

      <Footer />
    </div>
  );
};

export default ComplexDetail;

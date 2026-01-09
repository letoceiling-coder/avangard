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
  MapPin, Home, Calendar, 
  CreditCard, Building2, Car, Shield, Trees, 
  Share2, Check, Copy, MessageCircle,
  Ruler, DoorOpen, Bath, Sparkles, ChevronRight, Maximize2
} from "lucide-react";
import PropertyCard from "@/components/PropertyCard";
import PropertyDetailHeader from "@/components/PropertyDetailHeader";
import PropertyMainBlock from "@/components/PropertyMainBlock";
import PropertyStickyCTA from "@/components/PropertyStickyCTA";
import { toast } from "sonner";

const mockProperty = {
  id: "1",
  title: "3-комнатная квартира в ЖК «Белый город»",
  price: 15900000,
  pricePerMeter: 496875,
  status: "good_price" as const,
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
  propertyType: "квартира" as const,
  city: "Белгород",
  metro: null,
  keyFeatures: [
    { label: "Комнат", value: "3" },
    { label: "Площадь", value: "85 м²" },
    { label: "Этаж", value: "12/25" },
    { label: "Тип жилья", value: "Квартира" },
    { label: "Ремонт", value: "Чистовая" },
    { label: "Санузел", value: "Раздельный" },
    { label: "Балкон", value: "2 лоджии" },
    { label: "Вид из окна", value: "На двор" },
    { label: "Перепланировка", value: "Нет" },
    { label: "Газ", value: "Да" },
  ],
  fullDetails: [
    {
      title: "О КВАРТИРЕ",
      parameters: [
        { label: "Тип", value: "Квартира" },
        { label: "Комнат", value: "3" },
        { label: "Площадь", value: "85 м²" },
        { label: "Жилая площадь", value: "65 м²" },
        { label: "Кухня", value: "12 м²" },
        { label: "Этаж", value: "12" },
        { label: "Всего этажей", value: "25" },
        { label: "Ремонт", value: "Чистовая" },
        { label: "Год постройки", value: "2024" },
      ],
    },
    {
      title: "О ДОМЕ",
      parameters: [
        { label: "Год постройки", value: "2024" },
        { label: "Тип", value: "Монолит-кирпич" },
        { label: "Этажность", value: "25" },
        { label: "Лифт", value: "Пассажирский" },
        { label: "Интернет", value: "Да" },
        { label: "Парковка", value: "Подземная" },
      ],
    },
    {
      title: "ДОКУМЕНТЫ И СДЕЛКА",
      parameters: [
        { label: "Собственник", value: "Частный" },
        { label: "Право собственности", value: "Сертификат" },
        { label: "Ипотека", value: "Возможна" },
        { label: "Договор", value: "Купля-продажа" },
      ],
    },
    {
      title: "КОММУНИКАЦИИ",
      parameters: [
        { label: "Вода", value: "Холодное/горячее" },
        { label: "Электричество", value: "Да" },
        { label: "Газ", value: "Да" },
        { label: "Отопление", value: "Центральное" },
      ],
    },
  ],
  infrastructure: [
    { type: "school", icon: "school", name: "Школа" },
    { type: "park", icon: "park", name: "Парк" },
    { type: "clinic", icon: "clinic", name: "Поликлиника" },
    { type: "shop", icon: "shop", name: "Магазин" },
    { type: "square", icon: "square", name: "Площадь" },
    { type: "church", icon: "church", name: "Церковь" },
  ],
  agentPhone: "+7 (999) 123-45-67",
  agentName: "Алексей Иванов",
  hasSecurity: true,
  inRegistry: true,
  updatedAt: new Date(Date.now() - 2 * 60 * 60 * 1000), // 2 часа назад
  createdAt: new Date(Date.now() - 15 * 24 * 60 * 60 * 1000), // 15 дней назад
  viewsTotal: 1248,
  viewsToday: 23,
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
  const [showFullDescription, setShowFullDescription] = useState(false);

  const formatPrice = (price: number) => {
    return price.toLocaleString("ru-RU") + " ₽";
  };

  const calculateMortgage = (price: number, rate = 3, years = 20) => {
    const monthlyRate = rate / 100 / 12;
    const months = years * 12;
    const payment = price * (monthlyRate * Math.pow(1 + monthlyRate, months)) / (Math.pow(1 + monthlyRate, months) - 1);
    return Math.round(payment);
  };

  const handlePhoneClick = () => {
    toast.success("Номер телефона: +7 (999) 123-45-67");
  };

  const handleRequestClick = () => {
    toast.success("Форма заявки открыта");
  };

  const handleShare = () => {
    navigator.clipboard.writeText(window.location.href);
    toast.success("Ссылка скопирована");
  };

  return (
    <div className="min-h-screen bg-background">
      <Header />
      
      <main className="container mx-auto px-0 pb-24 lg:pb-8">
        {/* Compact Header: Breadcrumbs + Actions */}
        <div className="px-4 pt-2 pb-2 md:px-6 md:pt-3 md:pb-2">
          <PropertyDetailHeader
            propertyId={mockProperty.id}
            propertyTitle={mockProperty.title}
            breadcrumbs={[
              { label: "Главная", href: "/" },
              { label: "Новостройки", href: "/catalog" },
              { label: mockProperty.complex, href: `/complex/${mockProperty.complexId}` },
            ]}
            property={{
              id: mockProperty.id,
              title: mockProperty.title,
              price: mockProperty.price,
              image: mockProperty.images[0],
              area: mockProperty.area,
              rooms: mockProperty.rooms,
              floor: mockProperty.floor,
              address: mockProperty.address,
              type: mockProperty.type,
            }}
          />
        </div>

        {/* Main Block - Mobile-first adaptive layout */}
        <PropertyMainBlock
          photos={mockProperty.images.map((url, index) => ({
            id: `photo-${index + 1}`,
            url,
            alt: `${mockProperty.title} - фото ${index + 1}`,
          }))}
          propertyTitle={mockProperty.title}
          price={mockProperty.price}
          pricePerSquareMeter={mockProperty.pricePerMeter}
          status={mockProperty.status}
          propertyType={mockProperty.propertyType}
          squareMeters={mockProperty.area}
          floor={mockProperty.floor}
          totalFloors={mockProperty.totalFloors}
          updatedAt={mockProperty.updatedAt || mockProperty.createdAt}
          createdAt={mockProperty.createdAt}
          viewsTotal={mockProperty.viewsTotal}
          viewsToday={mockProperty.viewsToday}
          address={mockProperty.address}
          district={mockProperty.district}
          nearestMetro={mockProperty.metro}
          addressCity={mockProperty.city}
          propertyId={mockProperty.id}
          propertyForActions={{
            id: mockProperty.id,
            title: mockProperty.title,
            price: mockProperty.price,
            image: mockProperty.images[0],
            area: mockProperty.area,
            rooms: mockProperty.rooms,
            floor: mockProperty.floor,
            address: mockProperty.address,
            type: mockProperty.type,
            pricePerMeter: mockProperty.pricePerMeter,
          }}
          phone={mockProperty.agentPhone}
          agentName={mockProperty.agentName}
          hasSecurity={mockProperty.hasSecurity}
          inRegistry={mockProperty.inRegistry}
          ctaPropertyTitle={mockProperty.title}
          keyFeatures={mockProperty.keyFeatures}
          description={mockProperty.description}
          fullDetails={mockProperty.fullDetails}
          infrastructure={mockProperty.infrastructure}
          latitude={mockProperty.coordinates[0]}
          longitude={mockProperty.coordinates[1]}
          mapAddress={mockProperty.address}
          mapCity={mockProperty.city}
          similarObjects={similarProperties.map((p) => ({
            id: p.id,
            image: p.image,
            price: p.price,
            area: p.area,
            floor: p.floor,
            totalFloors: 25,
            rooms: p.rooms,
            district: p.address?.split(",")[0] || "Центральный район",
            address: p.address,
          }))}
        />

        {/* Main Content */}
        <div className="space-y-6">
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

      {/* Mobile Sticky CTA Footer */}
      <PropertyStickyCTA
        price={mockProperty.price}
        phone={mockProperty.agentPhone}
        agentName={mockProperty.agentName}
        propertyTitle={mockProperty.title}
      />

      {/* Spacer for mobile sticky footer */}
      <div className="h-[64px] md:hidden" style={{ paddingBottom: "env(safe-area-inset-bottom)" }} />

      <Footer />
    </div>
  );
};

export default PropertyDetail;

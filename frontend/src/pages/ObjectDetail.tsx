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
  ChevronRight, Share2, Download, MapPin
} from "lucide-react";
import YandexMap from "@/components/YandexMap";
import PropertyCard from "@/components/PropertyCard";
import PropertyHeroBlock from "@/components/PropertyHeroBlock";
import PropertyTopBar from "@/components/PropertyTopBar";
import PropertyMediaGallery from "@/components/PropertyMediaGallery";
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

  const formatPrice = (price: number) => {
    return price.toLocaleString("ru-RU") + " ₽";
  };

  const handlePhoneClick = () => {
    toast.success(`Номер телефона: ${mockObject.agentPhone}`);
  };

  const handleRequestClick = () => {
    toast.success("Форма заявки открыта");
  };

  const handleShare = () => {
    navigator.clipboard.writeText(window.location.href);
    toast.success("Ссылка скопирована в буфер обмена");
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
      
      {/* Top Bar */}
      <PropertyTopBar
        propertyId={mockObject.id}
        propertyTitle={mockObject.title}
        property={{
          id: mockObject.id,
          title: mockObject.title,
          price: mockObject.price,
          image: mockObject.images[0],
          area: mockObject.area,
          rooms: mockObject.rooms,
          floor: mockObject.floor,
          address: mockObject.address,
          type: mockObject.type,
        }}
      />

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

        {/* Hero Block - Gallery + Info */}
        <PropertyHeroBlock
          property={{
            id: mockObject.id,
            title: mockObject.title,
            price: mockObject.price,
            pricePerMeter: mockObject.pricePerMeter,
            images: mockObject.images,
            area: mockObject.area,
            rooms: mockObject.rooms,
            floor: mockObject.floor,
            totalFloors: mockObject.totalFloors,
            address: mockObject.address,
            district: mockObject.district,
            type: mockObject.type,
          }}
          onPhoneClick={handlePhoneClick}
          onRequestClick={handleRequestClick}
        />

        {/* Media Gallery Block */}
        <div className="mb-6 md:mb-8">
          <PropertyMediaGallery
            photos={mockObject.images.map((url, index) => ({
              id: `photo-${index + 1}`,
              url,
              alt: `${mockObject.title} - фото ${index + 1}`,
            }))}
            propertyTitle={mockObject.title}
          />
        </div>

        {/* Main Content */}
        <div className="space-y-8">
            {/* Title & Description */}
            <div className="space-y-4">
              <h1 className="text-2xl md:text-3xl font-display font-bold text-foreground">
                {mockObject.title}
              </h1>
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


      <Footer />
    </div>
  );
};

export default ObjectDetail;

import { Link, useNavigate } from "react-router-dom";
import Header from "@/components/Header";
import Footer from "@/components/Footer";
import SearchCard from "@/components/SearchCard";
import PropertyCard, { ExtendedProperty } from "@/components/PropertyCard";
import { ArrowRight } from "lucide-react";
import heroWinter from "@/assets/hero-bg-winter.png";
import StatsBlock from "@/components/StatsBlock";
import CTASocialBlock from "@/components/CTASocialBlock";
import PopularComplexes from "@/components/PopularComplexes";
import QuickTypeSelection from "@/components/QuickTypeSelection";
import SalesStarts from "@/components/SalesStarts";
import MayLikeSection from "@/components/MayLikeSection";
import NewsSlider from "@/components/NewsSlider";
import HomeMapSection from "@/components/HomeMapSection";

const mockProperties: ExtendedProperty[] = [
  {
    id: "1",
    title: "3-комнатная квартира",
    price: 6500000,
    image: "https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?w=800&h=800&fit=crop",
    area: 85,
    rooms: 3,
    floor: 12,
    address: "ул. Победы, 89, Ленинский район",
    type: "Новостройка",
    buildingName: "ЖК «Белый город»",
    status: "new",
  },
  {
    id: "2",
    title: "2-комнатная квартира",
    price: 4800000,
    image: "https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=800&h=800&fit=crop",
    area: 62,
    rooms: 2,
    floor: 8,
    address: "пр. Славы, 45, Центральный район",
    type: "Вторичка",
    status: "secondary",
  },
  {
    id: "3",
    title: "Пентхаус",
    price: 15000000,
    image: "https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=800&h=800&fit=crop",
    area: 145,
    rooms: 4,
    floor: 25,
    address: "ул. Щорса, 2, Центральный район",
    type: "Новостройка",
    buildingName: "ЖК «Империал»",
    status: "verified",
  },
  {
    id: "4",
    title: "Студия",
    price: 2800000,
    image: "https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=800&h=800&fit=crop",
    area: 28,
    rooms: 1,
    floor: 5,
    address: "ул. Белгородская, 12",
    type: "Новостройка",
    buildingName: "ЖК «Современник»",
    status: "new",
  },
  {
    id: "5",
    title: "1-комнатная квартира",
    price: 3200000,
    image: "https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=800&h=800&fit=crop",
    area: 42,
    rooms: 1,
    floor: 7,
    address: "ул. Губкина, 17, Северный район",
    type: "Новостройка",
    buildingName: "ЖК «Новая высота»",
    status: "new",
  },
  {
    id: "6",
    title: "2-комнатная квартира",
    price: 5100000,
    image: "https://images.unsplash.com/photo-1560185127-6ed189bf02f4?w=800&h=800&fit=crop",
    area: 68,
    rooms: 2,
    floor: 14,
    address: "ул. Садовая, 23, Западный район",
    type: "Вторичка",
    status: "verified",
  },
  {
    id: "7",
    title: "4-комнатная квартира",
    price: 9800000,
    image: "https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=800&h=800&fit=crop",
    area: 120,
    rooms: 4,
    floor: 18,
    address: "ул. Центральная, 1",
    type: "Новостройка",
    buildingName: "ЖК «Центральный»",
    status: "new",
  },
  {
    id: "8",
    title: "3-комнатная квартира",
    price: 7200000,
    image: "https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=800&h=800&fit=crop",
    area: 92,
    rooms: 3,
    floor: 9,
    address: "пр. Богдана Хмельницкого, 78",
    type: "Вторичка",
    status: "secondary",
  },
  {
    id: "9",
    title: "1-комнатная квартира",
    price: 3500000,
    image: "https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?w=800&h=800&fit=crop",
    area: 45,
    rooms: 1,
    floor: 11,
    address: "ул. Харьковская, 15",
    type: "Новостройка",
    buildingName: "ЖК «Парковый»",
    status: "new",
  },
  {
    id: "10",
    title: "2-комнатная квартира",
    price: 4500000,
    image: "https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=800&h=800&fit=crop",
    area: 58,
    rooms: 2,
    floor: 6,
    address: "ул. Попова, 32, Харьковская гора",
    type: "Вторичка",
    status: "verified",
  },
  {
    id: "11",
    title: "Студия с террасой",
    price: 3100000,
    image: "https://images.unsplash.com/photo-1600573472550-8090b5e0745e?w=800&h=800&fit=crop",
    area: 35,
    rooms: 1,
    floor: 3,
    address: "ул. Костюкова, 44",
    type: "Новостройка",
    buildingName: "ЖК «Белый город»",
    status: "new",
  },
  {
    id: "12",
    title: "3-комнатная квартира",
    price: 8500000,
    image: "https://images.unsplash.com/photo-1600047509807-ba8f99d2cdde?w=800&h=800&fit=crop",
    area: 105,
    rooms: 3,
    floor: 16,
    address: "ул. 5 Августа, 7",
    type: "Новостройка",
    buildingName: "ЖК «Империал»",
    status: "verified",
  },
];

const Index = () => {
  const navigate = useNavigate();

  const handleStartSearch = () => {
    navigate('/catalog');
  };

  return (
    <div className="min-h-screen bg-background flex flex-col">
      <Header />

      {/* Hero Background Section - Fits in one screen */}
      <section className="relative w-full min-h-[calc(100vh-80px)] flex items-center overflow-hidden">
        {/* Background Image */}
        <img
          src={heroWinter}
          alt="Современный жилой район зимой"
          className="absolute inset-0 w-full h-full object-cover object-center scale-105 transition-transform duration-700 ease-out"
        />
        
        {/* Gradient Overlay - Enhanced */}
        <div 
          className="absolute inset-0"
          style={{ 
            background: 'linear-gradient(135deg, rgba(15, 23, 42, 0.80) 0%, rgba(30, 58, 138, 0.70) 30%, rgba(15, 23, 42, 0.85) 70%, rgba(15, 23, 42, 0.92) 100%)'
          }}
        />

        {/* Hero Content - Enhanced layout */}
        <div className="w-full px-4 sm:px-6 py-8 sm:py-12 relative z-10">
          <div className="max-w-6xl mx-auto flex flex-col gap-6 sm:gap-8 animate-fade-in">
            {/* Hero Text - Centered with better spacing */}
            <div className="text-center space-y-3 sm:space-y-4">
              <h1 className="text-3xl sm:text-4xl md:text-5xl font-bold text-white leading-tight tracking-tight">
                Найдите свою недвижимость
              </h1>
              
              <p className="text-sm sm:text-base text-white/90 max-w-2xl mx-auto leading-relaxed">
                Квартиры, дома и коммерция от застройщиков и агентств
              </p>
            </div>

            {/* SearchCard - Below title with smooth animation */}
            <div className="animate-slide-up" style={{ animationDelay: '100ms' }}>
              <SearchCard />
            </div>
          </div>
        </div>
      </section>

      {/* Quick Type Selection */}
      <QuickTypeSelection />

      {/* Popular Complexes */}
      <PopularComplexes />

      {/* Properties Section - Enhanced */}
      <section className="py-8 sm:py-12 relative overflow-hidden bg-gradient-to-b from-background to-background/95">
        {/* Subtle background gradient */}
        <div className="absolute inset-0 bg-gradient-to-br from-primary/[0.02] via-transparent to-primary/[0.04]" />
        
        <div className="w-full max-w-7xl mx-auto px-4 sm:px-6 relative">
          {/* Header with decorative element - Enhanced */}
          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4 mb-6 sm:mb-8">
            <div className="space-y-2">
              <div className="flex items-center gap-2">
                <div className="w-10 h-1 bg-primary rounded-full" />
                <span className="text-xs font-semibold text-primary uppercase tracking-wider">
                  Горячие предложения
                </span>
              </div>
              <h2 className="text-xl sm:text-2xl md:text-3xl font-bold text-foreground">
                Актуальные предложения
              </h2>
            </div>
            <Link 
              to="/catalog" 
              className="inline-flex items-center gap-2 text-sm font-semibold text-primary hover:text-primary/80 transition-all group self-start sm:self-auto"
            >
              Смотреть все
              <ArrowRight className="w-4 h-4 group-hover:translate-x-1 transition-transform" />
            </Link>
          </div>

          {/* Grid - Cian-style compact layout */}
          <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            {mockProperties.slice(0, 8).map((property, index) => (
              <div
                key={property.id}
                className="animate-fade-in opacity-0"
                style={{ 
                  animationDelay: `${index * 50}ms`,
                  animationFillMode: 'forwards'
                }}
              >
                <PropertyCard property={property} featured={index < 2} />
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* May Like Section */}
      <MayLikeSection />

      {/* Sales Starts 2025 */}
      <SalesStarts />

      {/* Stats Block */}
      <StatsBlock />

      {/* News Section */}
      <NewsSlider />

      {/* Map Section */}
      <HomeMapSection />

      {/* CTA - Sell Your Property */}
      <CTASocialBlock />

      <Footer />
    </div>
  );
};

export default Index;

import { Link } from "react-router-dom";
import { MapPin, ArrowRight, Home, Building2 } from "lucide-react";
import { Button } from "@/components/ui/button";

interface MapProperty {
  id: string;
  name: string;
  type: "complex" | "property";
  price: string;
  address: string;
  coords: { lat: number; lng: number };
}

const mockMapProperties: MapProperty[] = [
  { id: "1", name: "ЖК «Белый город»", type: "complex", price: "от 2.8 млн ₽", address: "Ленинский район", coords: { lat: 50.595, lng: 36.587 } },
  { id: "2", name: "ЖК «Империал»", type: "complex", price: "от 5.2 млн ₽", address: "Центральный район", coords: { lat: 50.597, lng: 36.598 } },
  { id: "3", name: "2-комн. квартира", type: "property", price: "4.5 млн ₽", address: "ул. Победы, 45", coords: { lat: 50.591, lng: 36.580 } },
  { id: "4", name: "ЖК «Современник»", type: "complex", price: "от 3.4 млн ₽", address: "Харьковская гора", coords: { lat: 50.588, lng: 36.605 } },
  { id: "5", name: "Студия", type: "property", price: "2.9 млн ₽", address: "пр. Славы, 78", coords: { lat: 50.600, lng: 36.575 } },
];

const HomeMapSection = () => {
  return (
    <section className="py-10 md:py-14 bg-background">
      <div className="container mx-auto px-4">
        {/* Header */}
        <div className="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
          <div>
            <h2 className="text-2xl md:text-3xl font-bold text-foreground tracking-tight">
              Объекты на карте
            </h2>
            <p className="text-sm text-muted-foreground/60 mt-1">
              Найдите недвижимость в удобном для вас районе
            </p>
          </div>
          <Link to="/catalog?view=map">
            <Button variant="outline" className="rounded-full gap-2">
              Открыть карту
              <ArrowRight className="w-4 h-4" />
            </Button>
          </Link>
        </div>

        {/* Map Container */}
        <div className="relative rounded-[24px] overflow-hidden border border-border/50 bg-muted/30" style={{ height: '400px' }}>
          {/* Static Map Placeholder with gradient */}
          <div 
            className="absolute inset-0 bg-cover bg-center"
            style={{ 
              backgroundImage: `url('https://api.mapbox.com/styles/v1/mapbox/light-v11/static/36.587,50.595,12,0/1200x400@2x?access_token=pk.placeholder')`,
              backgroundColor: '#e8f4f8'
            }}
          >
            {/* Fallback gradient for placeholder */}
            <div className="absolute inset-0 bg-gradient-to-br from-sky-50 via-blue-50 to-slate-100" />
            
            {/* Grid pattern overlay */}
            <div 
              className="absolute inset-0 opacity-30"
              style={{
                backgroundImage: `
                  linear-gradient(rgba(100, 150, 200, 0.1) 1px, transparent 1px),
                  linear-gradient(90deg, rgba(100, 150, 200, 0.1) 1px, transparent 1px)
                `,
                backgroundSize: '40px 40px'
              }}
            />
          </div>

          {/* Property Markers */}
          <div className="absolute inset-0 flex items-center justify-center">
            {mockMapProperties.map((property, index) => (
              <div
                key={property.id}
                className="absolute group cursor-pointer"
                style={{
                  left: `${20 + (index * 15) % 60}%`,
                  top: `${25 + (index * 12) % 50}%`,
                }}
              >
                {/* Marker */}
                <div className={`
                  w-10 h-10 rounded-full flex items-center justify-center shadow-lg transition-all duration-200
                  ${property.type === 'complex' 
                    ? 'bg-primary text-white group-hover:scale-110' 
                    : 'bg-white text-primary border-2 border-primary group-hover:scale-110'
                  }
                `}>
                  {property.type === 'complex' ? (
                    <Building2 className="w-5 h-5" />
                  ) : (
                    <Home className="w-5 h-5" />
                  )}
                </div>

                {/* Tooltip */}
                <div className="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-10">
                  <div className="bg-white rounded-xl shadow-xl p-3 min-w-[180px] border border-border/50">
                    <p className="text-sm font-medium text-foreground truncate">{property.name}</p>
                    <p className="text-xs text-muted-foreground/70 mt-0.5">{property.address}</p>
                    <p className="text-sm font-bold text-primary mt-1">{property.price}</p>
                  </div>
                  {/* Arrow */}
                  <div className="absolute top-full left-1/2 -translate-x-1/2 -mt-1">
                    <div className="w-3 h-3 bg-white rotate-45 border-r border-b border-border/50" />
                  </div>
                </div>
              </div>
            ))}
          </div>

          {/* Legend */}
          <div className="absolute bottom-4 left-4 flex items-center gap-3 bg-white/90 backdrop-blur-sm rounded-full px-4 py-2 shadow-sm">
            <div className="flex items-center gap-1.5">
              <div className="w-4 h-4 rounded-full bg-primary flex items-center justify-center">
                <Building2 className="w-2.5 h-2.5 text-white" />
              </div>
              <span className="text-xs text-muted-foreground">ЖК</span>
            </div>
            <div className="w-px h-4 bg-border" />
            <div className="flex items-center gap-1.5">
              <div className="w-4 h-4 rounded-full bg-white border-2 border-primary flex items-center justify-center">
                <Home className="w-2.5 h-2.5 text-primary" />
              </div>
              <span className="text-xs text-muted-foreground">Квартиры</span>
            </div>
          </div>

          {/* Zoom hint */}
          <div className="absolute bottom-4 right-4 bg-white/90 backdrop-blur-sm rounded-full px-3 py-1.5 shadow-sm">
            <span className="text-xs text-muted-foreground">Нажмите для открытия карты</span>
          </div>

          {/* Clickable overlay */}
          <Link to="/catalog?view=map" className="absolute inset-0 z-20" />
        </div>
      </div>
    </section>
  );
};

export default HomeMapSection;

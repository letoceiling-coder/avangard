import { useNavigate } from "react-router-dom";
import { MapPin } from "lucide-react";
import LiveGridLogo from "@/components/LiveGridLogo";

const regions = [
  {
    id: "belgorod",
    name: "Белгород",
    description: "Белгородская область",
    image: "https://images.unsplash.com/photo-1486299267070-83823f5448dd?w=800&h=600&fit=crop",
  },
  {
    id: "krasnodar",
    name: "Краснодарский край",
    description: "Краснодар, Сочи, Анапа",
    image: "https://images.unsplash.com/photo-1496568816309-51d7c20e3b21?w=800&h=600&fit=crop",
  },
  {
    id: "rostov",
    name: "Ростовская область",
    description: "Ростов-на-Дону, Таганрог",
    image: "https://images.unsplash.com/photo-1449824913935-59a10b8d2000?w=800&h=600&fit=crop",
  },
];

const RegionSelect = () => {
  const navigate = useNavigate();

  const handleRegionSelect = (region: typeof regions[0]) => {
    localStorage.setItem("selectedRegion", region.name);
    navigate("/");
  };

  return (
    <div className="min-h-screen flex flex-col items-center justify-center p-4 bg-background relative overflow-hidden">
      {/* Background gradient */}
      <div className="absolute inset-0 bg-gradient-to-br from-background via-background to-primary/5" />
      
      <div className="container mx-auto relative z-10">
        {/* Logo */}
        <div className="text-center mb-12 animate-fade-in">
          <div className="flex justify-center mb-6">
            <LiveGridLogo size="lg" />
          </div>
          <p className="text-xl text-muted-foreground">
            Выберите регион для поиска недвижимости
          </p>
        </div>

        {/* Region Cards */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-6xl mx-auto">
          {regions.map((region, index) => (
            <button
              key={region.id}
              onClick={() => handleRegionSelect(region)}
              className="bg-card rounded-2xl border border-border overflow-hidden group text-left hover:border-primary/50 hover:shadow-lg transition-all duration-300 animate-fade-in"
              style={{ animationDelay: `${index * 100}ms` }}
            >
              <div className="relative h-56 overflow-hidden">
                <img
                  src={region.image}
                  alt={region.name}
                  className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                />
                <div className="absolute inset-0 bg-gradient-to-t from-background via-background/40 to-transparent" />
              </div>
              
              <div className="p-6 -mt-16 relative">
                <div className="inline-flex items-center gap-2 mb-3 px-3 py-1.5 rounded-full bg-card/90 backdrop-blur-sm border border-border">
                  <MapPin className="w-4 h-4 text-primary" />
                  <span className="text-sm text-muted-foreground">{region.description}</span>
                </div>
                <h3 className="text-2xl font-display font-bold text-foreground group-hover:text-primary transition-colors">
                  {region.name}
                </h3>
              </div>
            </button>
          ))}
        </div>
      </div>
    </div>
  );
};

export default RegionSelect;

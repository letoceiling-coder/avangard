import Header from "@/components/Header";
import Footer from "@/components/Footer";
import { Building2, MapPin, Calendar } from "lucide-react";
import { Link } from "react-router-dom";

const complexes = [
  { id: "1", name: "ЖК «Белый город»", developer: "СК «Белгородский»", address: "ул. Победы, 89", minPrice: 3500000, apartments: 234, completion: "IV кв. 2024", image: "https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?w=800&h=600&fit=crop" },
  { id: "2", name: "ЖК «Империал»", developer: "СК «Премьер»", address: "ул. Щорса, 2", minPrice: 5200000, apartments: 156, completion: "II кв. 2025", image: "https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=800&h=600&fit=crop" },
  { id: "3", name: "ЖК «Парковый»", developer: "ГК «Кубань»", address: "пр. Славы, 45", minPrice: 4100000, apartments: 312, completion: "III кв. 2024", image: "https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=800&h=600&fit=crop" },
  { id: "4", name: "ЖК «Южный»", developer: "ДСК «Дон»", address: "ул. Садовая, 12", minPrice: 3800000, apartments: 198, completion: "I кв. 2025", image: "https://images.unsplash.com/photo-1460574283810-2aab119d8511?w=800&h=600&fit=crop" },
];

const ResidentialComplex = () => (
  <div className="min-h-screen bg-background">
    <Header />
    <div className="container mx-auto px-4 py-8">
      <h1 className="text-3xl md:text-4xl font-display font-bold text-foreground mb-2">Жилые комплексы</h1>
      <p className="text-muted-foreground mb-8">Современные ЖК с развитой инфраструктурой</p>

      <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
        {complexes.map((complex) => (
          <Link key={complex.id} to={`/complex/${complex.id}`}>
            <article className="lg-card-hover overflow-hidden group">
              <div className="relative h-52 overflow-hidden">
                <img src={complex.image} alt={complex.name} className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" />
                <div className="absolute top-3 right-3 lg-chip bg-white/90 text-foreground font-semibold">
                  от {(complex.minPrice / 1000000).toFixed(1)} млн ₽
                </div>
                <div className="absolute inset-0 bg-gradient-to-t from-foreground/50 to-transparent" />
              </div>
              <div className="p-5">
                <h3 className="text-lg font-display font-semibold group-hover:text-primary transition-colors mb-3">{complex.name}</h3>
                <div className="space-y-1.5 text-sm text-muted-foreground">
                  <p className="flex items-center gap-2"><Building2 className="w-4 h-4 text-primary" />{complex.developer}</p>
                  <p className="flex items-center gap-2"><MapPin className="w-4 h-4 text-primary" />{complex.address}</p>
                  <p className="flex items-center gap-2"><Calendar className="w-4 h-4 text-primary" />Сдача: {complex.completion}</p>
                </div>
                <p className="text-sm text-muted-foreground mt-3 pt-3 border-t border-border">{complex.apartments} квартир</p>
              </div>
            </article>
          </Link>
        ))}
      </div>
    </div>
    <Footer />
  </div>
);

export default ResidentialComplex;

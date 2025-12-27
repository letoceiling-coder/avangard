import Header from "@/components/Header";
import Footer from "@/components/Footer";
import { Building2, MapPin } from "lucide-react";
import { Link } from "react-router-dom";

const developers = [
  { id: "1", name: "СК «Белгородский»", projects: 12, region: "Белгород", image: "https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=800&h=600&fit=crop" },
  { id: "2", name: "Группа компаний «Юг»", projects: 8, region: "Краснодарский край", image: "https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?w=800&h=600&fit=crop" },
  { id: "3", name: "ДСК «Дон»", projects: 15, region: "Ростовская область", image: "https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=800&h=600&fit=crop" },
  { id: "4", name: "СК «Премьер»", projects: 6, region: "Белгород", image: "https://images.unsplash.com/photo-1460574283810-2aab119d8511?w=800&h=600&fit=crop" },
];

const Developers = () => (
  <div className="min-h-screen bg-background">
    <Header />
    <div className="container mx-auto px-4 py-8">
      <h1 className="text-3xl md:text-4xl font-display font-bold text-foreground mb-2">Застройщики</h1>
      <p className="text-muted-foreground mb-8">Надёжные строительные компании</p>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {developers.map((dev) => (
          <Link key={dev.id} to={`/developers/${dev.id}`}>
            <article className="lg-card-hover overflow-hidden group">
              <div className="relative h-48 overflow-hidden">
                <img src={dev.image} alt={dev.name} className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" />
                <div className="absolute inset-0 bg-gradient-to-t from-foreground/60 to-transparent" />
              </div>
              <div className="p-5">
                <span className="lg-chip mb-2 inline-flex"><MapPin className="w-3 h-3 mr-1" />{dev.region}</span>
                <h3 className="text-lg font-display font-semibold group-hover:text-primary transition-colors">{dev.name}</h3>
                <p className="text-sm text-muted-foreground flex items-center gap-1 mt-1"><Building2 className="w-4 h-4" />{dev.projects} проектов</p>
              </div>
            </article>
          </Link>
        ))}
      </div>
    </div>
    <Footer />
  </div>
);

export default Developers;

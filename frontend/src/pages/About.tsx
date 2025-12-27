import Header from "@/components/Header";
import Footer from "@/components/Footer";
import { Award, Shield, TrendingUp, Users } from "lucide-react";

const About = () => {
  const stats = [
    { icon: Award, value: "15 лет", label: "на рынке", desc: "Опыт и профессионализм" },
    { icon: Users, value: "5000+", label: "клиентов", desc: "Довольных владельцев" },
    { icon: TrendingUp, value: "1000+", label: "объектов", desc: "В базе недвижимости" },
    { icon: Shield, value: "100%", label: "гарантия", desc: "Чистота сделок" },
  ];

  return (
    <div className="min-h-screen bg-background">
      <Header />

      <div className="container mx-auto px-4 py-8 md:py-12">
        <div className="text-center mb-12">
          <h1 className="text-4xl md:text-5xl font-display font-bold text-foreground mb-4">
            О компании <span className="text-gradient">LiveGrid</span>
          </h1>
          <p className="text-lg text-muted-foreground max-w-2xl mx-auto">
            Современная платформа для поиска недвижимости с безупречной репутацией
          </p>
        </div>

        <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-12">
          {stats.map((stat, index) => (
            <div key={index} className="lg-card p-6 text-center glow-primary-hover">
              <div className="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center mx-auto mb-4">
                <stat.icon className="w-6 h-6 text-primary" />
              </div>
              <p className="text-2xl font-display font-bold text-foreground">{stat.value}</p>
              <p className="font-medium text-foreground">{stat.label}</p>
              <p className="text-sm text-muted-foreground">{stat.desc}</p>
            </div>
          ))}
        </div>

        <div className="lg-card p-8 mb-8">
          <h2 className="text-2xl font-display font-bold mb-4">О нас</h2>
          <div className="space-y-4 text-muted-foreground">
            <p>LiveGrid — современная платформа для поиска недвижимости в Белгороде, Краснодарском крае и Ростовской области.</p>
            <p>Наша миссия — помочь каждому клиенту найти идеальное жильё с помощью удобного сервиса и проверенной базы объектов.</p>
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div className="lg-card p-6">
            <h3 className="text-xl font-display font-semibold mb-4">Преимущества</h3>
            <ul className="space-y-3">
              {["Индивидуальный подход", "Проверенные объекты", "Юридическое сопровождение", "Помощь с ипотекой"].map((item) => (
                <li key={item} className="flex items-center gap-2">
                  <div className="w-2 h-2 rounded-full bg-primary" />
                  <span>{item}</span>
                </li>
              ))}
            </ul>
          </div>
          <div className="lg-card p-6">
            <h3 className="text-xl font-display font-semibold mb-4">Услуги</h3>
            <ul className="space-y-3">
              {["Подбор недвижимости", "Продажа объектов", "Консультации", "Оценка стоимости"].map((item) => (
                <li key={item} className="flex items-center gap-2">
                  <div className="w-2 h-2 rounded-full bg-primary" />
                  <span>{item}</span>
                </li>
              ))}
            </ul>
          </div>
        </div>
      </div>

      <Footer />
    </div>
  );
};

export default About;

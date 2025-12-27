import { Zap, Shield, TrendingUp, Headphones } from "lucide-react";

const features = [
  {
    icon: Zap,
    title: "Быстрый поиск",
    description: "Найдите идеальную квартиру за несколько минут с нашей интеллектуальной системой поиска.",
  },
  {
    icon: Shield,
    title: "Безопасность",
    description: "Все объекты проверены. Защита от мошенничества и гарантия честных сделок.",
  },
  {
    icon: TrendingUp,
    title: "Лучшие цены",
    description: "Прямые предложения от застройщиков без комиссий и скрытых платежей.",
  },
  {
    icon: Headphones,
    title: "Поддержка 24/7",
    description: "Наша команда готова помочь вам в любое время. Быстрые ответы на все вопросы.",
  },
];

const WhyChooseBlock = () => {
  return (
    <section className="py-16 sm:py-20 bg-background">
      <div className="max-w-6xl mx-auto px-4 sm:px-6">
        <h2 className="text-3xl sm:text-4xl font-bold text-foreground text-center mb-12">
          Почему выбирают LiveGrid
        </h2>

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-8 lg:gap-12">
          {features.map((feature, index) => (
            <div 
              key={index} 
              className="flex gap-4 animate-fade-in"
              style={{ animationDelay: `${index * 100}ms` }}
            >
              <div className="flex-shrink-0">
                <div className="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center">
                  <feature.icon className="w-6 h-6 text-primary" />
                </div>
              </div>
              <div>
                <h3 className="text-lg font-bold text-foreground mb-2">{feature.title}</h3>
                <p className="text-muted-foreground">{feature.description}</p>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
};

export default WhyChooseBlock;

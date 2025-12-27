import { Building2, TrendingUp, MapPin } from "lucide-react";

const stats = [
  {
    icon: Building2,
    value: "1000+",
    label: "Объектов в базе",
    description: "Квартиры, дома и коммерция.",
  },
  {
    icon: TrendingUp,
    value: "Лучшие цены",
    label: "От застройщиков",
    description: "От застройщиков и честных агентов. Без переплат.",
  },
  {
    icon: MapPin,
    value: "3 региона",
    label: "Охват",
    description: "Белгород, Краснодар, Ростов. Расширяем регионы.",
  },
];

const StatsBlock = () => {
  return (
    <section className="py-12 md:py-16 px-4 md:px-6 lg:px-8 bg-muted/30">
      <div className="max-w-7xl mx-auto">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6 lg:gap-8">
          {stats.map((stat, index) => (
            <div
              key={index}
              className="bg-background rounded-xl p-6 md:p-8 shadow-sm hover:shadow-md transition-all duration-200 border border-border hover:border-primary/20 animate-fade-in group"
              style={{ animationDelay: `${index * 100}ms` }}
            >
              {/* Icon Container */}
              <div className="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center mb-4 group-hover:bg-primary/15 transition-colors">
                <stat.icon className="w-6 h-6 text-primary" />
              </div>

              {/* Content */}
              <h3 className="text-xl font-bold text-foreground mb-2">
                {stat.value}
              </h3>
              <p className="text-sm text-muted-foreground">
                {stat.description}
              </p>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
};

export default StatsBlock;

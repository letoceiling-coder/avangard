import { useRef } from "react";
import { Link } from "react-router-dom";
import { ChevronLeft, ChevronRight, ArrowRight } from "lucide-react";
import { Button } from "@/components/ui/button";
import NewsCard, { NewsItem } from "./NewsCard";

const mockNews: NewsItem[] = [
  {
    id: "1",
    title: "Ипотечные ставки снизились до рекордных значений в декабре 2024",
    category: "Ипотека",
    date: "15.12.2024",
    image: "https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=800&h=600&fit=crop",
    excerpt: "Центральный банк объявил о снижении ключевой ставки, что повлияло на условия ипотечного кредитования.",
  },
  {
    id: "2",
    title: "Новый жилой комплекс «Парковый квартал» получил разрешение на строительство",
    category: "Новостройки",
    date: "12.12.2024",
    image: "https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=800&h=600&fit=crop",
    excerpt: "Масштабный проект включает 5 жилых башен и современную инфраструктуру.",
  },
  {
    id: "3",
    title: "Обзор рынка недвижимости: итоги 2024 года и прогнозы на 2025",
    category: "Аналитика",
    date: "10.12.2024",
    image: "https://images.unsplash.com/photo-1551836022-4c4c79ecde51?w=800&h=600&fit=crop",
    excerpt: "Эксперты подводят итоги года и делятся прогнозами на следующий период.",
  },
  {
    id: "4",
    title: "Программа «Семейная ипотека» продлена до 2030 года",
    category: "Законы",
    date: "08.12.2024",
    image: "https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=800&h=600&fit=crop",
    excerpt: "Правительство приняло решение о продлении популярной программы поддержки.",
  },
  {
    id: "5",
    title: "Топ-10 районов для покупки квартиры в 2025 году",
    category: "Рейтинги",
    date: "05.12.2024",
    image: "https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=800&h=600&fit=crop",
    excerpt: "Анализ перспективных локаций с точки зрения инвестиционной привлекательности.",
  },
];

const NewsSlider = () => {
  const sliderRef = useRef<HTMLDivElement>(null);

  const scrollSlider = (direction: "left" | "right") => {
    if (sliderRef.current) {
      const scrollAmount = 380;
      sliderRef.current.scrollBy({
        left: direction === "left" ? -scrollAmount : scrollAmount,
        behavior: "smooth",
      });
    }
  };

  return (
    <section className="py-12 md:py-16 bg-muted/30">
      <div className="container mx-auto px-4">
        {/* Section Header */}
        <div className="flex items-end justify-between mb-8">
          <div>
            <h2 className="text-2xl md:text-3xl font-semibold text-foreground">
              Новости недвижимости
            </h2>
            <p className="text-muted-foreground mt-1">
              Актуальные события рынка и полезные материалы
            </p>
          </div>
          <div className="hidden md:flex items-center gap-3">
            <div className="flex items-center gap-2">
              <Button
                variant="outline"
                size="icon"
                className="rounded-full"
                onClick={() => scrollSlider("left")}
              >
                <ChevronLeft className="w-5 h-5" />
              </Button>
              <Button
                variant="outline"
                size="icon"
                className="rounded-full"
                onClick={() => scrollSlider("right")}
              >
                <ChevronRight className="w-5 h-5" />
              </Button>
            </div>
            <Link
              to="/news"
              className="flex items-center gap-2 text-primary hover:text-primary/80 font-semibold transition-colors ml-4"
            >
              Все новости
              <ArrowRight className="w-5 h-5" />
            </Link>
          </div>
        </div>

        {/* Slider */}
        <div
          ref={sliderRef}
          className="flex gap-5 overflow-x-auto scrollbar-hide pb-4 -mx-4 px-4 snap-x snap-mandatory"
        >
          {mockNews.map((news, index) => (
            <div
              key={news.id}
              className="flex-shrink-0 w-[320px] md:w-[360px] snap-start animate-fade-in"
              style={{ animationDelay: `${index * 50}ms` }}
            >
              <NewsCard news={news} />
            </div>
          ))}
        </div>

        {/* Mobile "View All" Link */}
        <div className="mt-6 md:hidden">
          <Link
            to="/news"
            className="flex items-center justify-center gap-2 text-primary font-semibold"
          >
            Все новости
            <ArrowRight className="w-5 h-5" />
          </Link>
        </div>
      </div>
    </section>
  );
};

export default NewsSlider;

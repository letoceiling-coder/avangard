import { useState } from "react";
import { Link } from "react-router-dom";
import Header from "@/components/Header";
import Footer from "@/components/Footer";
import NewsCard, { NewsItem } from "@/components/NewsCard";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Search } from "lucide-react";

const allNews: NewsItem[] = [
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
  {
    id: "6",
    title: "Как выбрать квартиру в новостройке: полный гайд для покупателя",
    category: "Гайды",
    date: "03.12.2024",
    image: "https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=800&h=600&fit=crop",
    excerpt: "Подробная инструкция по выбору жилья на первичном рынке.",
  },
  {
    id: "7",
    title: "Цены на вторичное жильё стабилизировались в ноябре",
    category: "Аналитика",
    date: "01.12.2024",
    image: "https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800&h=600&fit=crop",
    excerpt: "После периода роста рынок вторичной недвижимости показал признаки стабилизации.",
  },
  {
    id: "8",
    title: "Новые требования к застройщикам вступили в силу",
    category: "Законы",
    date: "28.11.2024",
    image: "https://images.unsplash.com/photo-1504307651254-35680f356dfd?w=800&h=600&fit=crop",
    excerpt: "Изменения в законодательстве касаются проектного финансирования и эскроу-счетов.",
  },
];

const categories = ["Все", "Ипотека", "Новостройки", "Аналитика", "Законы", "Рейтинги", "Гайды"];

const News = () => {
  const [selectedCategory, setSelectedCategory] = useState("Все");
  const [searchQuery, setSearchQuery] = useState("");

  const filteredNews = allNews.filter((news) => {
    const matchesCategory = selectedCategory === "Все" || news.category === selectedCategory;
    const matchesSearch = news.title.toLowerCase().includes(searchQuery.toLowerCase());
    return matchesCategory && matchesSearch;
  });

  return (
    <div className="min-h-screen bg-background">
      <Header />

      {/* Hero Section */}
      <section className="pt-8 pb-10 bg-muted/30">
        <div className="container mx-auto px-4">
          {/* Breadcrumbs */}
          <nav className="flex items-center gap-2 text-sm text-muted-foreground mb-6">
            <Link to="/" className="hover:text-primary transition-colors">
              Главная
            </Link>
            <span>/</span>
            <span className="text-foreground">Новости</span>
          </nav>

          <h1 className="text-3xl md:text-4xl font-bold text-foreground mb-4">
            Новости недвижимости
          </h1>
          <p className="text-muted-foreground text-lg max-w-2xl">
            Актуальные новости рынка недвижимости, аналитика, обзоры и полезные материалы
          </p>
        </div>
      </section>

      {/* Filters Section */}
      <section className="py-6 border-b border-border">
        <div className="container mx-auto px-4">
          <div className="flex flex-col md:flex-row gap-4 md:items-center md:justify-between">
            {/* Categories */}
            <div className="flex gap-2 overflow-x-auto scrollbar-hide pb-2 md:pb-0">
              {categories.map((category) => (
                <Button
                  key={category}
                  variant={selectedCategory === category ? "primary" : "outline"}
                  size="sm"
                  className="whitespace-nowrap rounded-full"
                  onClick={() => setSelectedCategory(category)}
                >
                  {category}
                </Button>
              ))}
            </div>

            {/* Search */}
            <div className="relative w-full md:w-72">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
              <Input
                placeholder="Поиск новостей..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="pl-10"
              />
            </div>
          </div>
        </div>
      </section>

      {/* News Grid */}
      <section className="py-12">
        <div className="container mx-auto px-4">
          {filteredNews.length > 0 ? (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
              {filteredNews.map((news, index) => (
                <div
                  key={news.id}
                  className="animate-fade-in"
                  style={{ animationDelay: `${index * 50}ms` }}
                >
                  <NewsCard news={news} />
                </div>
              ))}
            </div>
          ) : (
            <div className="text-center py-16">
              <p className="text-muted-foreground text-lg">
                Новости не найдены
              </p>
            </div>
          )}
        </div>
      </section>

      <Footer />
    </div>
  );
};

export default News;

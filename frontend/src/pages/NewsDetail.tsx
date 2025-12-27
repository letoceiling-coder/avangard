import { useParams, Link } from "react-router-dom";
import DOMPurify from "dompurify";
import Header from "@/components/Header";
import Footer from "@/components/Footer";
import NewsCard, { NewsItem } from "@/components/NewsCard";
import { Button } from "@/components/ui/button";
import { ArrowLeft, Calendar, Tag, Share2 } from "lucide-react";
import { toast } from "sonner";

const allNews: NewsItem[] = [
  {
    id: "1",
    title: "Ипотечные ставки снизились до рекордных значений в декабре 2024",
    category: "Ипотека",
    date: "15.12.2024",
    image: "https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=800&h=600&fit=crop",
    excerpt: "Центральный банк объявил о снижении ключевой ставки, что повлияло на условия ипотечного кредитования.",
    content: `
      <p>Центральный банк России объявил о значительном снижении ключевой ставки, что незамедлительно отразилось на условиях ипотечного кредитования. Эксперты отмечают, что это создаёт благоприятные условия для покупателей жилья.</p>
      
      <h3>Основные изменения</h3>
      <p>Средняя ставка по ипотеке снизилась до 7,5% годовых, что является минимальным значением за последние несколько лет. Это открывает новые возможности для приобретения недвижимости.</p>
      
      <h3>Что это значит для покупателей</h3>
      <p>Снижение ставок означает уменьшение ежемесячных платежей и общей переплаты по кредиту. При ипотеке на 3 миллиона рублей сроком на 20 лет разница в платежах может составить до 5 000 рублей в месяц.</p>
      
      <h3>Прогнозы экспертов</h3>
      <p>Аналитики прогнозируют, что текущие ставки сохранятся в ближайшие месяцы, однако рекомендуют не откладывать решение о покупке, так как ситуация на рынке может измениться.</p>
    `,
  },
  {
    id: "2",
    title: "Новый жилой комплекс «Парковый квартал» получил разрешение на строительство",
    category: "Новостройки",
    date: "12.12.2024",
    image: "https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=800&h=600&fit=crop",
    excerpt: "Масштабный проект включает 5 жилых башен и современную инфраструктуру.",
    content: `
      <p>Застройщик получил все необходимые разрешения для начала строительства нового жилого комплекса «Парковый квартал». Проект станет одним из крупнейших в регионе.</p>
      
      <h3>Характеристики проекта</h3>
      <p>ЖК «Парковый квартал» включает 5 жилых башен высотой от 18 до 25 этажей. Общая площадь квартир составит более 150 000 квадратных метров.</p>
      
      <h3>Инфраструктура</h3>
      <p>На территории комплекса запланированы детский сад, школа, фитнес-центр и торговые помещения. Подземный паркинг рассчитан на 2000 машиномест.</p>
      
      <h3>Сроки реализации</h3>
      <p>Первые две башни планируется сдать в эксплуатацию в конце 2026 года. Полное завершение проекта намечено на 2028 год.</p>
    `,
  },
  {
    id: "3",
    title: "Обзор рынка недвижимости: итоги 2024 года и прогнозы на 2025",
    category: "Аналитика",
    date: "10.12.2024",
    image: "https://images.unsplash.com/photo-1551836022-4c4c79ecde51?w=800&h=600&fit=crop",
    excerpt: "Эксперты подводят итоги года и делятся прогнозами на следующий период.",
    content: `
      <p>2024 год стал переломным для рынка недвижимости. Эксперты анализируют ключевые тренды и делают прогнозы на предстоящий период.</p>
      
      <h3>Итоги 2024 года</h3>
      <p>Рынок продемонстрировал стабильный рост в сегменте новостроек при умеренной динамике вторичного жилья. Общий объём сделок вырос на 15% по сравнению с прошлым годом.</p>
      
      <h3>Ценовая динамика</h3>
      <p>Средняя стоимость квадратного метра в новостройках выросла на 8%, на вторичном рынке — на 5%. Наибольший рост цен зафиксирован в сегменте комфорт-класса.</p>
      
      <h3>Прогнозы на 2025 год</h3>
      <p>Эксперты ожидают продолжения умеренного роста цен на уровне 5-7% годовых. Спрос на качественное жильё с развитой инфраструктурой останется высоким.</p>
    `,
  },
  {
    id: "4",
    title: "Программа «Семейная ипотека» продлена до 2030 года",
    category: "Законы",
    date: "08.12.2024",
    image: "https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=800&h=600&fit=crop",
    excerpt: "Правительство приняло решение о продлении популярной программы поддержки.",
    content: `
      <p>Правительство Российской Федерации приняло решение о продлении программы «Семейная ипотека» до 2030 года. Это позволит большему количеству семей приобрести собственное жильё.</p>
      
      <h3>Условия программы</h3>
      <p>Ставка по программе составляет 6% годовых для семей с детьми. Максимальная сумма кредита — 12 миллионов рублей для Москвы и Санкт-Петербурга, 6 миллионов для других регионов.</p>
      
      <h3>Кто может воспользоваться</h3>
      <p>Программа доступна семьям, в которых с 1 января 2018 года родился первый или последующий ребёнок. Также программа распространяется на семьи с детьми-инвалидами.</p>
    `,
  },
  {
    id: "5",
    title: "Топ-10 районов для покупки квартиры в 2025 году",
    category: "Рейтинги",
    date: "05.12.2024",
    image: "https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=800&h=600&fit=crop",
    excerpt: "Анализ перспективных локаций с точки зрения инвестиционной привлекательности.",
    content: `
      <p>Аналитики составили рейтинг наиболее перспективных районов для покупки недвижимости в 2025 году, основываясь на показателях развития инфраструктуры и транспортной доступности.</p>
      
      <h3>Критерии оценки</h3>
      <p>При составлении рейтинга учитывались: транспортная доступность, экологическая обстановка, развитие социальной инфраструктуры и потенциал роста цен.</p>
      
      <h3>Лидеры рейтинга</h3>
      <p>В топ-3 вошли районы с активным строительством метро и развитой социальной инфраструктурой. Эксперты прогнозируют рост цен в этих локациях на 10-15% в течение года.</p>
    `,
  },
];

const NewsDetail = () => {
  const { id } = useParams();
  const news = allNews.find((n) => n.id === id);
  const relatedNews = allNews.filter((n) => n.id !== id).slice(0, 3);

  const handleShare = () => {
    navigator.clipboard.writeText(window.location.href);
    toast.success("Ссылка скопирована в буфер обмена");
  };

  if (!news) {
    return (
      <div className="min-h-screen bg-background">
        <Header />
        <div className="container mx-auto px-4 py-20 text-center">
          <h1 className="text-2xl font-bold text-foreground mb-4">
            Новость не найдена
          </h1>
          <Link to="/news">
            <Button>
              <ArrowLeft className="w-4 h-4 mr-2" />
              Вернуться к новостям
            </Button>
          </Link>
        </div>
        <Footer />
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-background">
      <Header />

      {/* Breadcrumbs */}
      <section className="pt-6 pb-4">
        <div className="container mx-auto px-4">
          <nav className="flex items-center gap-2 text-sm text-muted-foreground">
            <Link to="/" className="hover:text-primary transition-colors">
              Главная
            </Link>
            <span>/</span>
            <Link to="/news" className="hover:text-primary transition-colors">
              Новости
            </Link>
            <span>/</span>
            <span className="text-foreground line-clamp-1">{news.title}</span>
          </nav>
        </div>
      </section>

      {/* Article */}
      <article className="pb-16">
        <div className="container mx-auto px-4">
          <div className="max-w-4xl mx-auto">
            {/* Back Button */}
            <Link
              to="/news"
              className="inline-flex items-center gap-2 text-muted-foreground hover:text-primary transition-colors mb-6"
            >
              <ArrowLeft className="w-4 h-4" />
              Назад к новостям
            </Link>

            {/* Header */}
            <header className="mb-8">
              <div className="flex items-center gap-4 text-sm text-muted-foreground mb-4">
                <span className="flex items-center gap-1.5">
                  <Calendar className="w-4 h-4" />
                  {news.date}
                </span>
                <span className="flex items-center gap-1.5">
                  <Tag className="w-4 h-4" />
                  {news.category}
                </span>
              </div>
              <h1 className="text-3xl md:text-4xl font-bold text-foreground leading-tight">
                {news.title}
              </h1>
            </header>

            {/* Featured Image */}
            <div className="relative aspect-video rounded-2xl overflow-hidden mb-8">
              <img
                src={news.image}
                alt={news.title}
                className="w-full h-full object-cover"
              />
            </div>

            {/* Content - Sanitized for XSS protection */}
            <div
              className="prose prose-lg max-w-none text-foreground prose-headings:text-foreground prose-p:text-muted-foreground prose-h3:text-xl prose-h3:font-semibold prose-h3:mt-8 prose-h3:mb-4 prose-p:leading-relaxed"
              dangerouslySetInnerHTML={{ 
                __html: DOMPurify.sanitize(news.content || news.excerpt) 
              }}
            />

            {/* Share */}
            <div className="mt-10 pt-6 border-t border-border flex items-center justify-between">
              <span className="text-muted-foreground">Поделиться статьёй</span>
              <Button variant="outline" onClick={handleShare}>
                <Share2 className="w-4 h-4 mr-2" />
                Скопировать ссылку
              </Button>
            </div>
          </div>
        </div>
      </article>

      {/* Related News */}
      {relatedNews.length > 0 && (
        <section className="py-12 bg-muted/30">
          <div className="container mx-auto px-4">
            <h2 className="text-2xl font-semibold text-foreground mb-8">
              Читайте также
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              {relatedNews.map((item, index) => (
                <div
                  key={item.id}
                  className="animate-fade-in"
                  style={{ animationDelay: `${index * 50}ms` }}
                >
                  <NewsCard news={item} />
                </div>
              ))}
            </div>
          </div>
        </section>
      )}

      <Footer />
    </div>
  );
};

export default NewsDetail;

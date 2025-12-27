import { Link } from "react-router-dom";
import { ArrowRight } from "lucide-react";

export interface NewsItem {
  id: string;
  title: string;
  category: string;
  date: string;
  image: string;
  excerpt: string;
  content?: string;
}

interface NewsCardProps {
  news: NewsItem;
}

const NewsCard = ({ news }: NewsCardProps) => {
  return (
    <Link
      to={`/news/${news.id}`}
      className="group flex flex-col h-full bg-card rounded-[20px] overflow-hidden border border-border/60 hover:border-primary/20 transition-all duration-300 hover:shadow-[0_20px_48px_rgba(10,35,66,0.10),0_8px_20px_rgba(10,35,66,0.06)] hover:-translate-y-1"
      style={{ boxShadow: '0 8px 24px rgba(10, 35, 66, 0.06), 0 2px 8px rgba(10, 35, 66, 0.04)' }}
    >
      {/* Image */}
      <div className="relative aspect-[4/3] bg-foreground/95 overflow-hidden rounded-t-[20px]">
        <img
          src={news.image}
          alt={news.title}
          className="w-full h-full object-cover opacity-90 group-hover:scale-105 transition-transform duration-500"
        />
        <div className="absolute inset-0 bg-gradient-to-t from-foreground/20 to-transparent" />
      </div>

      {/* Content */}
      <div className="flex flex-col flex-1 p-4">
        {/* Meta */}
        <div className="flex items-center justify-between text-[11px] text-muted-foreground/80 mb-2.5">
          <span className="font-normal">{news.date}</span>
          <span className="uppercase tracking-wider font-medium text-primary/70">
            {news.category}
          </span>
        </div>

        {/* Title */}
        <h3 className="text-base font-medium text-foreground leading-snug mb-auto group-hover:text-primary transition-colors line-clamp-3">
          {news.title}
        </h3>

        {/* Arrow Button */}
        <div className="mt-5 pt-3 border-t border-border/40">
          <div className="w-9 h-9 rounded-full bg-foreground/90 flex items-center justify-center group-hover:bg-primary transition-colors">
            <ArrowRight className="w-4 h-4 text-background" />
          </div>
        </div>
      </div>
    </Link>
  );
};

export default NewsCard;

import { useNavigate } from "react-router-dom";
import heroWinter from "@/assets/hero-bg-winter.png";
import { Button } from "@/components/ui/button";
import { ArrowRight } from "lucide-react";

const HeroSection = () => {
  const navigate = useNavigate();

  const handleStartSearch = () => {
    // Прокручиваем к блоку поиска или переходим в каталог
    const searchElement = document.getElementById('hero-search');
    if (searchElement) {
      searchElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } else {
      navigate('/catalog');
    }
  };

  return (
    <section className="relative w-full min-h-[400px] sm:min-h-[500px] max-h-[100vh] overflow-hidden flex items-center">
      {/* Background Image */}
      <img
        src={heroWinter}
        alt="Современный жилой район зимой"
        className="absolute inset-0 w-full h-full object-cover object-center"
      />
      
      {/* Выразительный Gradient Overlay */}
      <div 
        className="absolute inset-0"
        style={{ 
          background: 'linear-gradient(135deg, rgba(15, 23, 42, 0.75) 0%, rgba(30, 58, 138, 0.65) 30%, rgba(15, 23, 42, 0.85) 70%, rgba(15, 23, 42, 0.90) 100%)'
        }}
      />

      {/* Hero Content */}
      <div className="w-full max-w-screen-sm mx-auto px-4 relative z-10 flex flex-col items-center justify-center py-12 sm:py-16 min-h-[400px] sm:min-h-[500px]">
        {/* Text Block */}
        <div className="text-center animate-fade-in w-full">
          {/* Main Heading - Крупный и жирный */}
          <h1 className="text-2xl sm:text-3xl md:text-4xl font-bold text-white leading-[1.2] tracking-[-0.02em] mb-4 sm:mb-5">
            Найди свою недвижимость
          </h1>
          
          {/* Subtitle */}
          <p className="text-base text-muted-foreground max-w-full sm:max-w-[600px] mx-auto leading-[1.6] font-normal px-2 mb-6 sm:mb-8 text-center">
            Квартиры, дома и коммерция — от застройщиков и агентств Белгорода
          </p>

          {/* CTA Button */}
          <Button
            onClick={handleStartSearch}
            className="min-h-[44px] bg-primary text-white rounded-xl px-6 py-3 text-base font-medium shadow-md hover:scale-[1.02] active:scale-[0.98] transition-transform duration-200 flex items-center gap-2"
            aria-label="Перейти к каталогу недвижимости"
          >
            Перейти к каталогу
            <ArrowRight className="w-4 h-4" />
          </Button>
        </div>
      </div>
    </section>
  );
};

export default HeroSection;


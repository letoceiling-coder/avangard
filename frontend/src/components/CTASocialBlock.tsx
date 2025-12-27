import { Link } from "react-router-dom";
import { Button } from "@/components/ui/button";
import { MessageCircle, Send, ArrowRight, UserPlus } from "lucide-react";

const socialLinks = [
  {
    icon: Send,
    href: "https://t.me/livegrid",
    label: "Telegram",
  },
  {
    icon: MessageCircle,
    href: "https://vk.com/livegrid",
    label: "VK",
  },
];

const CTASocialBlock = () => {
  return (
    <section className="py-16 md:py-20 px-4 md:px-6 lg:px-8 bg-gradient-to-r from-primary to-primary/80 relative overflow-hidden">
      {/* Background Pattern */}
      <div className="absolute inset-0 opacity-10">
        <div
          className="absolute inset-0"
          style={{
            backgroundImage: `url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")`,
          }}
        />
      </div>

      <div className="max-w-5xl mx-auto text-center relative z-10">
        {/* Heading */}
        <h2 className="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-4">
          Начните поиск прямо сейчас
        </h2>

        {/* Subheading */}
        <p className="text-base md:text-lg text-white/90 mb-10 max-w-2xl mx-auto">
          Зарегистрируйтесь и сохраняйте избранные объекты, сравнивайте и получайте уведомления о новых объектах.
        </p>

        {/* Buttons */}
        <div className="flex flex-col md:flex-row gap-4 items-center justify-center mb-12">
          {/* Primary Button */}
          <Link to="/auth" className="w-full md:w-auto">
            <Button
              size="lg"
              className="w-full md:w-auto px-8 py-6 bg-white text-primary font-semibold rounded-lg hover:bg-gray-50 transition-all duration-200 shadow-md hover:shadow-lg"
            >
              <UserPlus className="w-5 h-5 mr-2" />
              Зарегистрироваться
            </Button>
          </Link>

          {/* Secondary Button */}
          <Link to="/catalog" className="w-full md:w-auto">
            <Button
              variant="outline"
              size="lg"
              className="w-full md:w-auto px-8 py-6 border-2 border-white text-white font-semibold rounded-lg hover:bg-white/10 bg-transparent transition-all duration-200"
            >
              Перейти в каталог
              <ArrowRight className="w-5 h-5 ml-2" />
            </Button>
          </Link>
        </div>

        {/* Social Links */}
        <div className="flex items-center justify-center gap-6">
          <p className="text-white/80">Следите за нами:</p>
          {socialLinks.map((social, index) => (
            <a
              key={index}
              href={social.href}
              target="_blank"
              rel="noopener noreferrer"
              className="p-3 bg-white/20 rounded-full hover:bg-white/30 transition-all"
              aria-label={social.label}
            >
              <social.icon className="w-6 h-6 text-white" />
            </a>
          ))}
        </div>
      </div>
    </section>
  );
};

export default CTASocialBlock;

import { Link } from "react-router-dom";
import { 
  Mail, MapPin, Phone, Clock, Shield, FileText, 
  Building2, Search, Users, Heart, Home, MessageCircle,
  Send, ExternalLink
} from "lucide-react";
import LiveGridLogo from "./LiveGridLogo";
import { Button } from "./ui/button";

const Footer = () => {
  const currentYear = new Date().getFullYear();

  const catalogLinks = [
    { to: "/catalog", label: "Все объекты", icon: Search },
    { to: "/catalog?type=newbuilding", label: "Новостройки", icon: Building2 },
    { to: "/catalog?type=secondary", label: "Вторичное жильё", icon: Home },
    { to: "/residential-complex", label: "Жилые комплексы", icon: Building2 },
    { to: "/developers", label: "Застройщики", icon: Users },
  ];

  const companyLinks = [
    { to: "/about", label: "О компании" },
    { to: "/contacts", label: "Контакты" },
    { to: "/favorites", label: "Избранное" },
    { to: "/profile", label: "Личный кабинет" },
    { to: "/agent", label: "Для агентов" },
  ];

  const legalLinks = [
    { to: "/privacy", label: "Политика конфиденциальности" },
    { to: "/terms", label: "Пользовательское соглашение" },
  ];

  const socialLinks = [
    { href: "https://t.me/livegrid", label: "Telegram", icon: Send },
    { href: "https://wa.me/79000000000", label: "WhatsApp", icon: MessageCircle },
  ];

  return (
    <footer className="bg-[#0A2342] border-t border-white/10 mt-0">
      {/* Main Footer Content */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 py-8 sm:py-12">
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 sm:gap-8">
          {/* Brand & Company Info */}
          <div className="sm:col-span-2 lg:col-span-2">
            <LiveGridLogo size="lg" variant="dark" className="mb-4" />
            <p className="text-white/70 text-sm leading-relaxed max-w-sm mb-5">
              Современная платформа для поиска недвижимости в Белгороде и регионах России. 
              Находите идеальные квартиры, дома и коммерческую недвижимость.
            </p>
            
            {/* Social Links */}
            <div className="flex items-center gap-3 mb-6">
              {socialLinks.map((social) => (
                <a
                  key={social.label}
                  href={social.href}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="w-10 h-10 rounded-full bg-white/10 hover:bg-primary flex items-center justify-center transition-colors duration-normal"
                  aria-label={social.label}
                >
                  <social.icon className="w-5 h-5 text-white" />
                </a>
              ))}
            </div>

            <div className="text-xs text-white/50 space-y-1">
              <p>ООО «ЛивГрид»</p>
              <p>ОГРН: 1234567890123</p>
              <p>ИНН: 1234567890</p>
            </div>
          </div>

          {/* Catalog Links */}
          <div>
            <h4 className="font-semibold text-white mb-4 text-sm uppercase tracking-wider">Каталог</h4>
            <ul className="space-y-2.5">
              {catalogLinks.map((link) => (
                <li key={link.to}>
                  <Link
                    to={link.to}
                    className="flex items-center gap-2 text-white/60 hover:text-primary transition-colors duration-normal text-sm group"
                  >
                    <link.icon className="w-4 h-4 text-white/40 group-hover:text-primary transition-colors" />
                    {link.label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          {/* Company Links */}
          <div>
            <h4 className="font-semibold text-white mb-4 text-sm uppercase tracking-wider">Компания</h4>
            <ul className="space-y-2.5">
              {companyLinks.map((link) => (
                <li key={link.to}>
                  <Link
                    to={link.to}
                    className="text-white/60 hover:text-primary transition-colors duration-normal text-sm"
                  >
                    {link.label}
                  </Link>
                </li>
              ))}
            </ul>
            
            {/* Legal Links */}
            <h4 className="font-semibold text-white mb-3 mt-6 text-sm uppercase tracking-wider">Документы</h4>
            <ul className="space-y-2.5">
              {legalLinks.map((link) => (
                <li key={link.to}>
                  <Link
                    to={link.to}
                    className="text-white/60 hover:text-primary transition-colors duration-normal text-sm"
                  >
                    {link.label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          {/* Contact Info */}
          <div>
            <h4 className="font-semibold text-white mb-4 text-sm uppercase tracking-wider">Контакты</h4>
            <ul className="space-y-3">
              <li>
                <a
                  href="tel:+79000000000"
                  className="flex items-center gap-2.5 text-white/80 hover:text-primary transition-colors duration-normal text-sm font-medium"
                >
                  <div className="w-8 h-8 rounded-full bg-primary/20 flex items-center justify-center flex-shrink-0">
                    <Phone className="w-4 h-4 text-primary" />
                  </div>
                  +7 (900) 000-00-00
                </a>
              </li>
              <li>
                <a
                  href="mailto:info@livegrid.ru"
                  className="flex items-center gap-2.5 text-white/60 hover:text-primary transition-colors duration-normal text-sm"
                >
                  <div className="w-8 h-8 rounded-full bg-white/5 flex items-center justify-center flex-shrink-0">
                    <Mail className="w-4 h-4 text-white/60" />
                  </div>
                  info@livegrid.ru
                </a>
              </li>
              <li className="flex items-start gap-2.5 text-white/60 text-sm">
                <div className="w-8 h-8 rounded-full bg-white/5 flex items-center justify-center flex-shrink-0 mt-0.5">
                  <MapPin className="w-4 h-4 text-white/60" />
                </div>
                <span>308000, г. Белгород,<br />ул. Примерная, д. 1</span>
              </li>
              <li className="flex items-center gap-2.5 text-white/60 text-sm">
                <div className="w-8 h-8 rounded-full bg-white/5 flex items-center justify-center flex-shrink-0">
                  <Clock className="w-4 h-4 text-white/60" />
                </div>
                Пн-Пт: 9:00 - 18:00
              </li>
            </ul>

            {/* CTA Button */}
            <Link to="/contacts" className="block mt-5">
              <Button variant="primary" size="sm" fullWidth className="bg-primary hover:bg-primary/90">
                Связаться с нами
              </Button>
            </Link>
          </div>
        </div>
      </div>

      {/* Disclaimer Section */}
      <div className="border-t border-white/10">
        <div className="container mx-auto px-4 py-6">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="flex items-start gap-3 p-4 bg-white/5 rounded-xl">
              <Shield className="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
              <p className="text-xs text-white/60 leading-relaxed">
                Информация на сайте носит справочный характер и не является публичной офертой (ст. 437 ГК РФ). 
                Изображения могут отличаться от реальных. Цены актуальны на момент публикации.
              </p>
            </div>

            <div className="flex items-start gap-3 p-4 bg-white/5 rounded-xl">
              <FileText className="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
              <p className="text-xs text-white/60 leading-relaxed">
                Обработка персональных данных — ФЗ № 152-ФЗ. Отправляя данные, вы соглашаетесь с{" "}
                <Link to="/privacy" className="text-primary hover:underline">Политикой конфиденциальности</Link>.
              </p>
            </div>
          </div>
        </div>
      </div>

      {/* Bottom Bar */}
      <div className="border-t border-white/10 bg-[#071a33]">
        <div className="container mx-auto px-4 py-4">
          <div className="flex flex-col md:flex-row items-center justify-between gap-4">
            <p className="text-xs text-white/50 text-center md:text-left">
              © {currentYear} ООО «ЛивГрид». Все права защищены.
            </p>
            <div className="flex flex-wrap items-center justify-center gap-4 md:gap-6">
              <Link to="/privacy" className="text-xs text-white/50 hover:text-primary transition-colors duration-normal">
                Конфиденциальность
              </Link>
              <Link to="/terms" className="text-xs text-white/50 hover:text-primary transition-colors duration-normal">
                Условия
              </Link>
              <Link to="/region-select" className="text-xs text-white/50 hover:text-primary transition-colors duration-normal">
                Выбор региона
              </Link>
            </div>
          </div>
        </div>
      </div>
    </footer>
  );
};

export default Footer;

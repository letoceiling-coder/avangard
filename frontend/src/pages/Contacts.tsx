import { useState } from "react";
import Header from "@/components/Header";
import Footer from "@/components/Footer";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Checkbox } from "@/components/ui/checkbox";
import { MapPin, Phone, Mail, Clock, Send, MessageCircle } from "lucide-react";
import { toast } from "sonner";
import { Link } from "react-router-dom";
import { z } from "zod";

const contactSchema = z.object({
  name: z.string().min(2, "Минимум 2 символа").max(100, "Максимум 100 символов"),
  phone: z.string().min(10, "Введите корректный номер телефона").max(20),
  email: z.string().email("Введите корректный email").optional().or(z.literal("")),
  message: z.string().min(10, "Минимум 10 символов").max(1000, "Максимум 1000 символов"),
});

const Contacts = () => {
  const [formData, setFormData] = useState({
    name: "",
    phone: "",
    email: "",
    message: "",
  });
  const [agreed, setAgreed] = useState(false);
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});

  const contactInfo = [
    { icon: Phone, title: "Телефон", lines: ["+7 (900) 000-00-00", "+7 (900) 000-00-01"], action: "tel:+79000000000" },
    { icon: Mail, title: "Email", lines: ["info@livegrid.ru", "sales@livegrid.ru"], action: "mailto:info@livegrid.ru" },
    { icon: MapPin, title: "Адрес", lines: ["г. Белгород, ул. Победы, 89", 'БЦ "Премиум", офис 301'] },
    { icon: Clock, title: "Режим работы", lines: ["Пн-Пт: 09:00 - 20:00", "Сб-Вс: 10:00 - 18:00"] },
  ];

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
    // Clear error on change
    if (errors[name]) {
      setErrors(prev => ({ ...prev, [name]: "" }));
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrors({});

    if (!agreed) {
      toast.error("Необходимо согласие на обработку данных");
      return;
    }

    try {
      contactSchema.parse(formData);
      setLoading(true);
      
      // Simulate API call
      await new Promise(resolve => setTimeout(resolve, 1000));
      
      // Save to localStorage for demo
      const contacts = JSON.parse(localStorage.getItem("contact_requests") || "[]");
      contacts.push({
        ...formData,
        createdAt: new Date().toISOString(),
        id: crypto.randomUUID(),
      });
      localStorage.setItem("contact_requests", JSON.stringify(contacts));
      
      toast.success("Сообщение отправлено! Мы свяжемся с вами в ближайшее время.");
      setFormData({ name: "", phone: "", email: "", message: "" });
      setAgreed(false);
    } catch (error) {
      if (error instanceof z.ZodError) {
        const fieldErrors: Record<string, string> = {};
        error.errors.forEach(err => {
          if (err.path[0]) {
            fieldErrors[err.path[0] as string] = err.message;
          }
        });
        setErrors(fieldErrors);
      }
    } finally {
      setLoading(false);
    }
  };

  const handleWhatsApp = () => {
    window.open("https://wa.me/79000000000?text=Здравствуйте! Хотел бы узнать подробнее о недвижимости.", "_blank");
  };

  const handleTelegram = () => {
    window.open("https://t.me/livegrid", "_blank");
  };

  return (
    <div className="min-h-screen bg-background">
      <Header />

      <div className="container mx-auto px-4 py-8">
        <h1 className="text-3xl md:text-4xl font-display font-bold text-foreground mb-2">Контакты</h1>
        <p className="text-muted-foreground mb-8">Свяжитесь с нами любым удобным способом</p>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
          {/* Contact Form */}
          <div className="bg-card rounded-2xl border border-border p-6 md:p-8 shadow-card">
            <h2 className="text-xl font-display font-semibold mb-6">Напишите нам</h2>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div>
                <Input 
                  name="name"
                  value={formData.name}
                  onChange={handleChange}
                  placeholder="Ваше имя *" 
                  error={!!errors.name}
                  errorMessage={errors.name}
                />
              </div>
              <div>
                <Input 
                  name="phone"
                  value={formData.phone}
                  onChange={handleChange}
                  placeholder="+7 (___) ___-__-__ *" 
                  type="tel"
                  error={!!errors.phone}
                  errorMessage={errors.phone}
                />
              </div>
              <div>
                <Input 
                  name="email"
                  value={formData.email}
                  onChange={handleChange}
                  type="email" 
                  placeholder="your@email.com" 
                  error={!!errors.email}
                  errorMessage={errors.email}
                />
              </div>
              <div>
                <Textarea 
                  name="message"
                  value={formData.message}
                  onChange={handleChange}
                  placeholder="Ваш вопрос или сообщение... *" 
                  className={`min-h-32 ${errors.message ? "border-destructive" : ""}`}
                />
                {errors.message && (
                  <p className="text-sm text-destructive mt-1">{errors.message}</p>
                )}
              </div>
              
              <div className="flex items-start gap-2">
                <Checkbox 
                  id="agree" 
                  checked={agreed} 
                  onCheckedChange={(checked) => setAgreed(checked as boolean)} 
                />
                <label htmlFor="agree" className="text-sm text-muted-foreground leading-relaxed cursor-pointer">
                  Я согласен с{" "}
                  <Link to="/privacy" className="text-primary hover:underline">
                    политикой конфиденциальности
                  </Link>{" "}
                  и даю согласие на обработку персональных данных
                </label>
              </div>

              <Button 
                type="submit" 
                variant="primary" 
                fullWidth 
                loading={loading}
                leftIcon={<Send className="w-4 h-4" />}
              >
                Отправить сообщение
              </Button>
            </form>

            {/* Quick Contact */}
            <div className="mt-6 pt-6 border-t border-border">
              <p className="text-sm text-muted-foreground mb-3">Или свяжитесь в мессенджере:</p>
              <div className="flex gap-3">
                <Button 
                  variant="secondary" 
                  onClick={handleWhatsApp}
                  className="flex-1"
                  leftIcon={<MessageCircle className="w-4 h-4" />}
                >
                  WhatsApp
                </Button>
                <Button 
                  variant="secondary" 
                  onClick={handleTelegram}
                  className="flex-1"
                  leftIcon={<Send className="w-4 h-4" />}
                >
                  Telegram
                </Button>
              </div>
            </div>
          </div>

          {/* Contact Info Cards */}
          <div className="space-y-4">
            {contactInfo.map((item, index) => (
              <div 
                key={index} 
                className="bg-card rounded-2xl border border-border p-5 flex items-start gap-4 shadow-card hover:shadow-md transition-shadow"
              >
                <div className="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
                  <item.icon className="w-5 h-5 text-primary" />
                </div>
                <div>
                  <h3 className="font-semibold text-foreground mb-1">{item.title}</h3>
                  {item.lines.map((line, i) => (
                    item.action ? (
                      <a 
                        key={i} 
                        href={item.action} 
                        className="block text-muted-foreground text-sm hover:text-primary transition-colors"
                      >
                        {line}
                      </a>
                    ) : (
                      <p key={i} className="text-muted-foreground text-sm">{line}</p>
                    )
                  ))}
                </div>
              </div>
            ))}

            {/* Social Links */}
            <div className="bg-card rounded-2xl border border-border p-5 shadow-card">
              <h3 className="font-semibold text-foreground mb-4">Мы в социальных сетях</h3>
              <div className="flex gap-3">
                <a 
                  href="https://t.me/livegrid" 
                  target="_blank" 
                  rel="noopener noreferrer"
                  className="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center hover:bg-primary hover:text-white transition-all"
                >
                  <Send className="w-5 h-5" />
                </a>
                <a 
                  href="https://vk.com/livegrid" 
                  target="_blank" 
                  rel="noopener noreferrer"
                  className="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center hover:bg-primary hover:text-white transition-all text-primary"
                >
                  <svg className="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M15.684 0H8.316C1.592 0 0 1.592 0 8.316v7.368C0 22.408 1.592 24 8.316 24h7.368C22.408 24 24 22.408 24 15.684V8.316C24 1.592 22.408 0 15.684 0zm3.692 17.123h-1.744c-.66 0-.864-.525-2.05-1.727-1.033-1-1.49-1.135-1.744-1.135-.356 0-.458.102-.458.593v1.575c0 .424-.135.678-1.253.678-1.846 0-3.896-1.118-5.335-3.202C4.624 10.857 4 8.57 4 8.096c0-.254.102-.491.593-.491h1.744c.44 0 .61.203.78.678.847 2.457 2.27 4.607 2.863 4.607.22 0 .322-.102.322-.66V9.721c-.068-1.186-.695-1.287-.695-1.71 0-.203.17-.407.44-.407h2.744c.372 0 .508.203.508.643v3.473c0 .372.17.508.27.508.22 0 .407-.136.813-.542 1.254-1.406 2.151-3.574 2.151-3.574.119-.254.305-.491.745-.491h1.744c.525 0 .644.27.525.643-.22 1.017-2.354 4.031-2.354 4.031-.186.305-.254.44 0 .78.186.254.796.779 1.203 1.253.745.847 1.32 1.558 1.473 2.05.17.491-.085.745-.576.745z"/>
                  </svg>
                </a>
              </div>
            </div>
          </div>
        </div>

        {/* Map Placeholder */}
        <div className="mt-8 bg-card rounded-2xl border border-border p-6 shadow-card">
          <h2 className="text-xl font-display font-semibold mb-4">Как нас найти</h2>
          <div className="h-80 bg-muted rounded-xl flex items-center justify-center relative overflow-hidden">
            <div className="absolute inset-0 bg-gradient-to-br from-primary/5 to-transparent" />
            <div className="text-center z-10">
              <MapPin className="w-12 h-12 text-primary/40 mx-auto mb-2" />
              <p className="text-muted-foreground">г. Белгород, ул. Победы, 89</p>
              <p className="text-sm text-muted-foreground mt-1">БЦ "Премиум", офис 301</p>
              <Button 
                variant="secondary" 
                size="sm" 
                className="mt-4"
                onClick={() => window.open("https://yandex.ru/maps/-/CCUSiBrE4B", "_blank")}
              >
                Открыть в Яндекс.Картах
              </Button>
            </div>
          </div>
        </div>
      </div>

      <Footer />
    </div>
  );
};

export default Contacts;

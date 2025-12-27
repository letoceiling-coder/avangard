import { useState, useEffect } from "react";
import { Link } from "react-router-dom";
import { Button } from "@/components/ui/button";
import { X } from "lucide-react";

const CookieConsent = () => {
  const [isVisible, setIsVisible] = useState(false);

  useEffect(() => {
    const consent = localStorage.getItem("cookie_consent");
    if (!consent) {
      // Show after a short delay for better UX
      const timer = setTimeout(() => setIsVisible(true), 1000);
      return () => clearTimeout(timer);
    }
  }, []);

  const acceptAll = () => {
    localStorage.setItem("cookie_consent", "all");
    localStorage.setItem("cookie_consent_date", new Date().toISOString());
    setIsVisible(false);
  };

  const acceptRequired = () => {
    localStorage.setItem("cookie_consent", "required");
    localStorage.setItem("cookie_consent_date", new Date().toISOString());
    setIsVisible(false);
  };

  if (!isVisible) return null;

  return (
    <div className="fixed bottom-0 left-0 right-0 z-50 p-4 animate-fade-in">
      <div className="container mx-auto max-w-4xl">
        <div className="bg-card border border-border rounded-2xl shadow-xl p-6 relative">
          {/* Close button */}
          <button
            onClick={acceptRequired}
            className="absolute top-4 right-4 text-muted-foreground hover:text-foreground transition-colors"
            aria-label="Закрыть"
          >
            <X className="w-5 h-5" />
          </button>

          {/* Content */}
          <div className="pr-8">
            <h3 className="text-lg font-semibold text-foreground mb-2">
              Уведомление о cookie
            </h3>
            <p className="text-sm text-muted-foreground mb-4 leading-relaxed">
              Я даю согласие на использование файлов cookie, в том числе Яндекс Метрики, 
              в целях, предусмотренных{" "}
              <Link to="/privacy" className="text-primary hover:underline">
                Политикой сбора и использования файлов cookie
              </Link>
              . Настройками ниже вы можете ограничить согласие только обязательными файлами cookie.
            </p>

            {/* Buttons */}
            <div className="flex flex-col sm:flex-row gap-3">
              <Button
                variant="ghost"
                onClick={acceptRequired}
                className="text-muted-foreground hover:text-foreground"
              >
                Принять только обязательные файлы cookie
              </Button>
              <Button
                variant="primary"
                onClick={acceptAll}
              >
                Принять все файлы cookie
              </Button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default CookieConsent;

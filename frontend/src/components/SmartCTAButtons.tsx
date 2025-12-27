import { useState } from "react";
import { Phone, Eye, Share2, Heart, Calendar, MessageCircle, X, User, PhoneCall, ExternalLink } from "lucide-react";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { useFavorites } from "@/hooks/useFavorites";
import { toast } from "sonner";

interface Property {
  id: string;
  title: string;
  price: number;
  image: string;
  area: number;
  rooms: number;
  floor: number;
  address: string;
  type?: "Новостройка" | "Вторичка";
}

interface SmartCTAButtonsProps {
  property: Property;
  agentName?: string;
  agentPhone?: string;
  onBookingClick?: () => void;
  showFavoriteCount?: boolean;
  favoriteCount?: number;
}

const SmartCTAButtons = ({
  property,
  agentName = "Менеджер LiveGrid",
  agentPhone = "+7 (800) 123-45-67",
  onBookingClick,
  showFavoriteCount = false,
  favoriteCount = 0,
}: SmartCTAButtonsProps) => {
  const { isFavorite, addToFavorites, removeFromFavorites } = useFavorites();
  const [showPhoneModal, setShowPhoneModal] = useState(false);
  const [isPhoneRevealed, setIsPhoneRevealed] = useState(false);

  const handlePhoneReveal = () => {
    setIsPhoneRevealed(true);
    setShowPhoneModal(true);
  };

  const handleShare = async () => {
    const shareUrl = `${window.location.origin}/property/${property.id}`;
    const shareText = `${property.title} - ${property.price.toLocaleString("ru-RU")} ₽`;

    if (navigator.share) {
      try {
        await navigator.share({
          title: property.title,
          text: shareText,
          url: shareUrl,
        });
      } catch {
        // User cancelled or share failed
      }
    } else {
      await navigator.clipboard.writeText(shareUrl);
      toast.success("Ссылка скопирована в буфер обмена");
    }
  };

  const handleFavoriteToggle = () => {
    const propertyWithType = {
      ...property,
      type: property.type || "Новостройка" as const,
    };
    
    if (isFavorite(property.id)) {
      removeFromFavorites(property.id);
      toast.success("Удалено из избранного");
    } else {
      addToFavorites(propertyWithType);
      toast.success("Добавлено в избранное");
    }
  };

  const formatPhoneForCall = (phone: string) => {
    return phone.replace(/[^\d+]/g, "");
  };

  const formatPhoneForWhatsApp = (phone: string) => {
    return phone.replace(/[^\d]/g, "");
  };

  return (
    <>
      <div className="flex flex-wrap gap-2">
        {/* Show Phone Button */}
        <Button
          variant="primary"
          size="md"
          className="gap-2"
          onClick={handlePhoneReveal}
        >
          <Phone className="w-4 h-4" />
          {isPhoneRevealed ? "Показать номер" : "Показать номер"}
        </Button>

        {/* Booking Button */}
        <Button
          variant="secondary"
          size="md"
          className="gap-2"
          onClick={onBookingClick}
        >
          <Calendar className="w-4 h-4" />
          Записаться на просмотр
        </Button>

        {/* Share Button */}
        <Button
          variant="outline"
          size="icon"
          onClick={handleShare}
          title="Поделиться"
          aria-label="Поделиться объектом"
          className="min-h-[44px] min-w-[44px]"
        >
          <Share2 className="w-4 h-4" />
        </Button>

        {/* Favorite Button */}
        <Button
          variant="outline"
          size={showFavoriteCount ? "md" : "icon"}
          onClick={handleFavoriteToggle}
          className={`min-h-[44px] ${showFavoriteCount ? "" : "min-w-[44px]"} gap-2 ${isFavorite(property.id) ? "text-destructive border-destructive hover:bg-destructive/10" : ""}`}
          title={isFavorite(property.id) ? "Удалить из избранного" : "Добавить в избранное"}
          aria-label={isFavorite(property.id) ? "Удалить из избранного" : "Добавить в избранное"}
        >
          <Heart className={`w-4 h-4 ${isFavorite(property.id) ? "fill-current" : ""}`} />
          {showFavoriteCount && favoriteCount > 0 && (
            <span className="text-sm">{favoriteCount}</span>
          )}
        </Button>
      </div>

      {/* Phone Reveal Modal */}
      <Dialog open={showPhoneModal} onOpenChange={setShowPhoneModal}>
        <DialogContent className="sm:max-w-sm">
          <DialogHeader>
            <DialogTitle>Контакт агента</DialogTitle>
          </DialogHeader>

          {/* Agent Info */}
          <div className="flex items-center gap-3 p-4 bg-muted/50 rounded-xl">
            <div className="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center">
              <User className="w-6 h-6 text-primary" />
            </div>
            <div>
              <p className="font-semibold text-foreground">{agentName}</p>
              <p className="text-sm text-muted-foreground">Менеджер по продажам</p>
            </div>
          </div>

          {/* Phone Number */}
          <div className="p-4 bg-muted/30 rounded-xl text-center">
            <p className="text-sm text-muted-foreground mb-1">Номер телефона</p>
            <p className="text-2xl font-bold text-foreground">{agentPhone}</p>
          </div>

          {/* Action Buttons */}
          <div className="grid grid-cols-2 gap-3">
            <a href={`tel:${formatPhoneForCall(agentPhone)}`}>
              <Button variant="primary" className="w-full gap-2">
                <PhoneCall className="w-4 h-4" />
                Позвонить
              </Button>
            </a>
            <a
              href={`https://wa.me/${formatPhoneForWhatsApp(agentPhone)}?text=${encodeURIComponent(`Здравствуйте! Интересует объект: ${property.title}`)}`}
              target="_blank"
              rel="noopener noreferrer"
            >
              <Button variant="secondary" className="w-full gap-2">
                <MessageCircle className="w-4 h-4" />
                WhatsApp
              </Button>
            </a>
          </div>

          {/* Callback option */}
          <p className="text-sm text-muted-foreground text-center">
            Или получить обратный звонок
          </p>
        </DialogContent>
      </Dialog>
    </>
  );
};

export default SmartCTAButtons;

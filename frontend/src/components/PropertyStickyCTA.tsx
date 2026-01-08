import { useState } from "react";
import { Phone, MessageCircle } from "lucide-react";
import { cn } from "@/lib/utils";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import { Button } from "@/components/ui/button";

interface PropertyStickyCTAProps {
  price: number;
  phone?: string;
  agentName?: string;
  propertyTitle?: string;
  className?: string;
}

const formatPrice = (price: number): string => {
  if (price >= 1000000) {
    const millions = price / 1000000;
    return `${millions.toFixed(1).replace(".", ",")}M ₽`;
  }
  return `${price.toLocaleString("ru-RU")} ₽`;
};

const PropertyStickyCTA = ({
  price,
  phone = "+7 (999) 123-45-67",
  agentName = "Менеджер",
  propertyTitle = "Объект",
  className,
}: PropertyStickyCTAProps) => {
  const [showPhoneModal, setShowPhoneModal] = useState(false);
  const [showMessageModal, setShowMessageModal] = useState(false);
  const [messageForm, setMessageForm] = useState({
    name: "",
    phone: "",
    message: "",
  });
  const [isSubmitting, setIsSubmitting] = useState(false);

  const formatPhoneForCall = (phone: string) => {
    return phone.replace(/[^\d+]/g, "");
  };

  const handleCall = () => {
    setShowPhoneModal(true);
  };

  const handleMessage = () => {
    setShowMessageModal(true);
  };

  const handleMessageSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);

    // TODO: Отправить сообщение на сервер
    await new Promise((resolve) => setTimeout(resolve, 1000));

    setIsSubmitting(false);
    setShowMessageModal(false);
    setMessageForm({ name: "", phone: "", message: "" });
    // toast.success("Сообщение отправлено");
  };

  const handleMessageChange = (
    e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>
  ) => {
    const { name, value } = e.target;
    setMessageForm((prev) => ({ ...prev, [name]: value }));
  };

  return (
    <>
      {/* Sticky CTA Footer - Mobile Only */}
      <div
        className={cn(
          "fixed bottom-0 left-0 right-0 z-50",
          "md:hidden",
          "bg-white border-t border-[#EEEEEE]",
          "flex items-center gap-3",
          className
        )}
        style={{
          height: "64px",
          paddingTop: "12px",
          paddingLeft: "16px",
          paddingRight: "16px",
          paddingBottom: "max(12px, env(safe-area-inset-bottom))",
          boxShadow: "0 -4px 12px rgba(0, 0, 0, 0.08)",
        }}
      >
        {/* Left: Price */}
        <div
          style={{
            fontFamily: "Inter, sans-serif",
            fontWeight: 600,
            fontSize: "16px",
            color: "#0F0F0F",
            width: "80px",
            flexShrink: 0,
          }}
        >
          {formatPrice(price)}
        </div>

        {/* Right: Buttons */}
        <div
          className="flex gap-2 flex-1"
          style={{
            gap: "8px",
          }}
        >
          {/* Button #1: Call (64% width) */}
          <button
            onClick={handleCall}
            className={cn(
              "flex-1 flex items-center justify-center gap-2",
              "bg-[#2563EB] text-white",
              "hover:bg-[#1D4ED8]",
              "active:bg-[#1e40af]",
              "transition-all duration-200",
              "cursor-pointer",
              "rounded-xl"
            )}
            style={{
              fontFamily: "Inter, sans-serif",
              fontWeight: 600,
              fontSize: "14px",
              padding: "12px",
              borderRadius: "12px",
              border: "none",
              minWidth: "100px",
            }}
            aria-label="Позвонить"
          >
            <Phone className="w-4 h-4" />
            <span>Позвонить</span>
          </button>

          {/* Button #2: Message (mini, optional) */}
          <button
            onClick={handleMessage}
            className={cn(
              "flex items-center justify-center",
              "bg-transparent text-[#2563EB] border border-[#2563EB]",
              "hover:bg-[#DBEAFE]",
              "active:bg-[#BFE5FF]",
              "transition-all duration-200",
              "cursor-pointer",
              "rounded-lg"
            )}
            style={{
              fontFamily: "Inter, sans-serif",
              fontWeight: 600,
              fontSize: "12px",
              padding: "8px",
              borderRadius: "8px",
              minWidth: "48px",
            }}
            aria-label="Написать"
          >
            <MessageCircle className="w-4 h-4" />
          </button>
        </div>
      </div>

      {/* Phone Modal */}
      <Dialog open={showPhoneModal} onOpenChange={setShowPhoneModal}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Позвонить агенту</DialogTitle>
            <DialogDescription>
              {agentName} · {phone}
            </DialogDescription>
          </DialogHeader>
          <div className="flex flex-col gap-4 mt-4">
            <a
              href={`tel:${formatPhoneForCall(phone)}`}
              className={cn(
                "w-full flex items-center justify-center gap-2",
                "bg-[#2563EB] text-white",
                "hover:bg-[#1D4ED8]",
                "transition-all duration-200",
                "cursor-pointer",
                "rounded-xl",
                "py-3 px-4"
              )}
              style={{
                fontFamily: "Inter, sans-serif",
                fontWeight: 600,
                fontSize: "16px",
              }}
            >
              <Phone className="w-5 h-5" />
              <span>Позвонить {phone}</span>
            </a>
            <a
              href={`https://wa.me/${formatPhoneForCall(phone).replace("+", "")}?text=${encodeURIComponent(`Здравствуйте! Меня интересует объект: ${propertyTitle}`)}`}
              target="_blank"
              rel="noopener noreferrer"
              className={cn(
                "w-full flex items-center justify-center gap-2",
                "bg-[#25D366] text-white",
                "hover:bg-[#20BA5A]",
                "transition-all duration-200",
                "cursor-pointer",
                "rounded-xl",
                "py-3 px-4"
              )}
              style={{
                fontFamily: "Inter, sans-serif",
                fontWeight: 600,
                fontSize: "16px",
              }}
            >
              <MessageCircle className="w-5 h-5" />
              <span>Написать в WhatsApp</span>
            </a>
          </div>
        </DialogContent>
      </Dialog>

      {/* Message Modal */}
      <Dialog open={showMessageModal} onOpenChange={setShowMessageModal}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Написать агенту</DialogTitle>
            <DialogDescription>
              Заполните форму, и мы свяжемся с вами
            </DialogDescription>
          </DialogHeader>
          <form onSubmit={handleMessageSubmit} className="flex flex-col gap-4 mt-4">
            <div className="flex flex-col gap-2">
              <Label htmlFor="name">Ваше имя</Label>
              <Input
                id="name"
                name="name"
                value={messageForm.name}
                onChange={handleMessageChange}
                required
                placeholder="Иван Иванов"
              />
            </div>
            <div className="flex flex-col gap-2">
              <Label htmlFor="phone">Телефон</Label>
              <Input
                id="phone"
                name="phone"
                type="tel"
                value={messageForm.phone}
                onChange={handleMessageChange}
                required
                placeholder="+7 (999) 123-45-67"
              />
            </div>
            <div className="flex flex-col gap-2">
              <Label htmlFor="message">Сообщение</Label>
              <Textarea
                id="message"
                name="message"
                value={messageForm.message}
                onChange={handleMessageChange}
                required
                placeholder="Здравствуйте! Меня интересует этот объект..."
                rows={4}
              />
            </div>
            <Button
              type="submit"
              disabled={isSubmitting}
              className="w-full"
              style={{
                fontFamily: "Inter, sans-serif",
                fontWeight: 600,
              }}
            >
              {isSubmitting ? "Отправка..." : "Отправить"}
            </Button>
          </form>
        </DialogContent>
      </Dialog>

      {/* Add scroll padding to body for mobile */}
      <style>{`
        @media (max-width: 767px) {
          body {
            scroll-padding-bottom: calc(64px + env(safe-area-inset-bottom));
          }
        }
      `}</style>
    </>
  );
};

export default PropertyStickyCTA;


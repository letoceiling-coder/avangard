import { useState } from "react";
import { Phone, MessageCircle, Check } from "lucide-react";
import { cn } from "@/lib/utils";
import { toast } from "sonner";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";

interface PropertyMainCTAButtonsProps {
  phone?: string;
  agentName?: string;
  hasSecurity?: boolean;
  inRegistry?: boolean;
  propertyTitle?: string;
  className?: string;
}

const PropertyMainCTAButtons = ({
  phone = "+7 (999) 123-45-67",
  agentName = "Менеджер",
  hasSecurity = false,
  inRegistry = false,
  propertyTitle = "Объект",
  className,
}: PropertyMainCTAButtonsProps) => {
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

    // Simulate API call
    await new Promise((resolve) => setTimeout(resolve, 1000));

    toast.success("Сообщение отправлено!");
    setMessageForm({ name: "", phone: "", message: "" });
    setShowMessageModal(false);
    setIsSubmitting(false);
  };

  const handleMessageChange = (
    e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>
  ) => {
    const { name, value } = e.target;
    setMessageForm((prev) => ({ ...prev, [name]: value }));
  };

  return (
    <>
      <div
        className={cn(
          "flex flex-col gap-3",
          "px-4 py-3",
          "md:px-6 md:py-4",
          className
        )}
        style={{
          gap: "12px",
        }}
      >
        {/* Call Button */}
        <button
          onClick={handleCall}
          className={cn(
            "w-full h-12 rounded-xl flex items-center justify-center gap-2",
            "bg-[#2563EB] text-white",
            "hover:bg-[#1D4ED8] hover:shadow-md",
            "active:bg-[#1e40af]",
            "focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:ring-offset-2",
            "transition-all duration-200",
            "disabled:opacity-50 disabled:cursor-not-allowed"
          )}
          style={{
            fontFamily: "Inter, sans-serif",
            fontWeight: 600,
            fontSize: "16px",
          }}
          aria-label="Позвонить"
        >
          <Phone className="w-5 h-5" />
          <span>Позвонить</span>
        </button>

        {/* Message Button */}
        <button
          onClick={handleMessage}
          className={cn(
            "w-full h-12 rounded-xl flex items-center justify-center gap-2",
            "bg-white text-[#2563EB] border-2 border-[#2563EB]",
            "hover:bg-[#DBEAFE]",
            "active:bg-[#BFE5FF]",
            "focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:ring-offset-2",
            "transition-all duration-200",
            "disabled:opacity-50 disabled:cursor-not-allowed"
          )}
          style={{
            fontFamily: "Inter, sans-serif",
            fontWeight: 600,
            fontSize: "16px",
          }}
          aria-label="Написать"
        >
          <MessageCircle className="w-5 h-5" />
          <span>Написать</span>
        </button>

        {/* Additional Info Badges */}
        {(hasSecurity || inRegistry) && (
          <div
            className="flex items-center gap-2 mt-2"
            style={{
              fontFamily: "Inter, sans-serif",
              fontWeight: 400,
              fontSize: "12px",
              color: "#10B981",
              marginTop: "8px",
            }}
          >
            {hasSecurity && (
              <div className="flex items-center gap-1">
                <Check className="w-4 h-4" />
                <span>Охранник</span>
              </div>
            )}
            {inRegistry && (
              <div className="flex items-center gap-1">
                <Check className="w-4 h-4" />
                <span>В реестре</span>
              </div>
            )}
          </div>
        )}
      </div>

      {/* Phone Modal */}
      <Dialog open={showPhoneModal} onOpenChange={setShowPhoneModal}>
        <DialogContent className="sm:max-w-sm">
          <DialogHeader>
            <DialogTitle>Контакт агента</DialogTitle>
            <DialogDescription>
              {agentName}
            </DialogDescription>
          </DialogHeader>

          <div className="space-y-4">
            {/* Phone Number */}
            <div className="p-4 bg-muted/30 rounded-xl text-center">
              <p className="text-sm text-muted-foreground mb-1">Номер телефона</p>
              <p className="text-2xl font-bold text-foreground">{phone}</p>
            </div>

            {/* Action Buttons */}
            <div className="grid grid-cols-2 gap-3">
              <a href={`tel:${formatPhoneForCall(phone)}`} className="w-full">
                <Button variant="primary" className="w-full gap-2">
                  <Phone className="w-4 h-4" />
                  Позвонить
                </Button>
              </a>
              <a
                href={`https://wa.me/${formatPhoneForCall(phone).replace("+", "")}?text=${encodeURIComponent(`Здравствуйте! Интересует объект: ${propertyTitle}`)}`}
                target="_blank"
                rel="noopener noreferrer"
                className="w-full"
              >
                <Button variant="secondary" className="w-full gap-2">
                  <MessageCircle className="w-4 h-4" />
                  WhatsApp
                </Button>
              </a>
            </div>
          </div>
        </DialogContent>
      </Dialog>

      {/* Message Modal */}
      <Dialog open={showMessageModal} onOpenChange={setShowMessageModal}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Написать сообщение</DialogTitle>
            <DialogDescription>
              Отправьте сообщение агенту по объекту
            </DialogDescription>
          </DialogHeader>

          <form onSubmit={handleMessageSubmit} className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="message-name">Ваше имя</Label>
              <Input
                id="message-name"
                name="name"
                value={messageForm.name}
                onChange={handleMessageChange}
                placeholder="Иван"
                required
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="message-phone">Телефон</Label>
              <Input
                id="message-phone"
                name="phone"
                type="tel"
                value={messageForm.phone}
                onChange={handleMessageChange}
                placeholder="+7 (900) 000-00-00"
                required
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="message-text">Сообщение</Label>
              <Textarea
                id="message-text"
                name="message"
                value={messageForm.message}
                onChange={handleMessageChange}
                placeholder="Здравствуйте! Интересует объект..."
                rows={4}
              />
            </div>

            <div className="flex gap-3">
              <Button
                type="button"
                variant="outline"
                onClick={() => setShowMessageModal(false)}
                className="flex-1"
              >
                Отмена
              </Button>
              <Button
                type="submit"
                variant="primary"
                className="flex-1"
                disabled={isSubmitting}
              >
                {isSubmitting ? "Отправка..." : "Отправить"}
              </Button>
            </div>
          </form>
        </DialogContent>
      </Dialog>
    </>
  );
};

export default PropertyMainCTAButtons;


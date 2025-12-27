import { useState } from "react";
import { z } from "zod";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import { Checkbox } from "@/components/ui/checkbox";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Phone, MessageCircle, User, CheckCircle, Loader2 } from "lucide-react";
import { toast } from "sonner";
import { Link } from "react-router-dom";

const callbackSchema = z.object({
  name: z.string().trim().min(2, "Введите ваше имя").max(100, "Имя слишком длинное"),
  phone: z.string().trim().min(10, "Введите корректный номер телефона").max(20, "Номер слишком длинный"),
  message: z.string().trim().max(500, "Сообщение слишком длинное").optional(),
});

interface CallbackModalProps {
  trigger?: React.ReactNode;
  title?: string;
  subtitle?: string;
  onSubmit?: (data: { name: string; phone: string; message?: string }) => Promise<void>;
}

const CallbackModal = ({
  trigger,
  title = "Обратный звонок",
  subtitle = "Оставьте номер телефона и мы перезвоним вам в течение 15 минут",
  onSubmit,
}: CallbackModalProps) => {
  const [open, setOpen] = useState(false);
  const [isSubmitted, setIsSubmitted] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [agreed, setAgreed] = useState(false);

  const [formData, setFormData] = useState({
    name: "",
    phone: "",
    message: "",
  });

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
    if (errors[name]) {
      setErrors((prev) => ({ ...prev, [name]: "" }));
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!agreed) {
      toast.error("Необходимо согласие с политикой конфиденциальности");
      return;
    }

    setIsLoading(true);
    setErrors({});

    try {
      const validatedData = callbackSchema.parse(formData) as { name: string; phone: string; message?: string };

      if (onSubmit) {
        await onSubmit(validatedData);
      } else {
        // Default behavior - simulate API delay
        await new Promise((resolve) => setTimeout(resolve, 800));

        // Store callback request in localStorage for demo
        const callbacks = JSON.parse(localStorage.getItem("livegrid_callbacks") || "[]");
        callbacks.push({
          ...validatedData,
          createdAt: new Date().toISOString(),
        });
        localStorage.setItem("livegrid_callbacks", JSON.stringify(callbacks));
      }

      setIsSubmitted(true);
      toast.success("Заявка на звонок отправлена!");

      // Reset after delay
      setTimeout(() => {
        setOpen(false);
        setIsSubmitted(false);
        setFormData({ name: "", phone: "", message: "" });
        setAgreed(false);
      }, 2000);
    } catch (error) {
      if (error instanceof z.ZodError) {
        const fieldErrors: Record<string, string> = {};
        error.errors.forEach((err) => {
          if (err.path[0]) {
            fieldErrors[err.path[0] as string] = err.message;
          }
        });
        setErrors(fieldErrors);
      } else {
        toast.error("Произошла ошибка при отправке заявки");
      }
    } finally {
      setIsLoading(false);
    }
  };

  const handleOpenChange = (newOpen: boolean) => {
    setOpen(newOpen);
    if (!newOpen) {
      setIsSubmitted(false);
      setErrors({});
    }
  };

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogTrigger asChild>
        {trigger || (
          <Button variant="outline" className="gap-2">
            <Phone className="w-4 h-4" />
            Заказать звонок
          </Button>
        )}
      </DialogTrigger>
      <DialogContent className="sm:max-w-md">
        {isSubmitted ? (
          <div className="py-8 text-center space-y-4">
            <div className="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto">
              <CheckCircle className="w-8 h-8 text-green-600" />
            </div>
            <div>
              <h3 className="text-xl font-semibold text-foreground">Заявка отправлена!</h3>
              <p className="text-muted-foreground mt-2">
                Мы перезвоним вам в ближайшее время
              </p>
            </div>
          </div>
        ) : (
          <>
            <DialogHeader>
              <DialogTitle className="flex items-center gap-2">
                <Phone className="w-5 h-5 text-primary" />
                {title}
              </DialogTitle>
              <DialogDescription>{subtitle}</DialogDescription>
            </DialogHeader>

            <form onSubmit={handleSubmit} className="space-y-4">
              {/* Name */}
              <div className="space-y-2">
                <Label htmlFor="callback-name" className="flex items-center gap-2">
                  <User className="w-4 h-4 text-muted-foreground" />
                  Ваше имя
                </Label>
                <Input
                  id="callback-name"
                  name="name"
                  value={formData.name}
                  onChange={handleChange}
                  placeholder="Иван"
                  className={errors.name ? "border-destructive" : ""}
                />
                {errors.name && <p className="text-xs text-destructive">{errors.name}</p>}
              </div>

              {/* Phone */}
              <div className="space-y-2">
                <Label htmlFor="callback-phone" className="flex items-center gap-2">
                  <Phone className="w-4 h-4 text-muted-foreground" />
                  Телефон
                </Label>
                <Input
                  id="callback-phone"
                  name="phone"
                  type="tel"
                  value={formData.phone}
                  onChange={handleChange}
                  placeholder="+7 (900) 000-00-00"
                  className={errors.phone ? "border-destructive" : ""}
                />
                {errors.phone && <p className="text-xs text-destructive">{errors.phone}</p>}
              </div>

              {/* Message (Optional) */}
              <div className="space-y-2">
                <Label htmlFor="callback-message" className="flex items-center gap-2">
                  <MessageCircle className="w-4 h-4 text-muted-foreground" />
                  Комментарий
                  <span className="text-muted-foreground text-xs">(необязательно)</span>
                </Label>
                <Textarea
                  id="callback-message"
                  name="message"
                  value={formData.message}
                  onChange={handleChange}
                  placeholder="Удобное время для звонка..."
                  rows={2}
                  className={`resize-none ${errors.message ? "border-destructive" : ""}`}
                />
              </div>

              {/* Consent */}
              <label className="flex items-start gap-2 text-xs text-muted-foreground cursor-pointer">
                <Checkbox
                  checked={agreed}
                  onCheckedChange={(checked) => setAgreed(checked as boolean)}
                  className="mt-0.5"
                />
                <span>
                  Я согласен с{" "}
                  <Link to="/privacy" className="text-primary hover:underline">
                    политикой конфиденциальности
                  </Link>
                </span>
              </label>

              <Button
                type="submit"
                disabled={!agreed || isLoading}
                className="w-full"
              >
                {isLoading ? (
                  <>
                    <Loader2 className="w-4 h-4 animate-spin mr-2" />
                    Отправка...
                  </>
                ) : (
                  "Заказать звонок"
                )}
              </Button>
            </form>
          </>
        )}
      </DialogContent>
    </Dialog>
  );
};

export default CallbackModal;

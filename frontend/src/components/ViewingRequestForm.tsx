import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { z } from "zod";
import { useAuth } from "@/hooks/useAuth";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Calendar, User, Phone, MessageSquare, CheckCircle } from "lucide-react";
import { toast } from "sonner";

const requestSchema = z.object({
  name: z.string().trim().min(2, "Имя должно содержать минимум 2 символа").max(100, "Имя слишком длинное"),
  phone: z.string().trim().min(10, "Введите корректный номер телефона").max(20, "Номер слишком длинный"),
  message: z.string().trim().max(500, "Сообщение слишком длинное").optional(),
});

interface ViewingRequestFormProps {
  propertyId: string;
  propertyTitle: string;
  propertyImage: string;
  propertyPrice: number;
  trigger?: React.ReactNode;
}

const ViewingRequestForm = ({
  propertyId,
  propertyTitle,
  propertyImage,
  propertyPrice,
  trigger,
}: ViewingRequestFormProps) => {
  const navigate = useNavigate();
  const { isAuthenticated, user, addApplication } = useAuth();
  const [open, setOpen] = useState(false);
  const [isSubmitted, setIsSubmitted] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});

  const [formData, setFormData] = useState({
    name: user?.name || "",
    phone: user?.phone || "",
    message: "",
  });

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
    // Clear error on change
    if (errors[name]) {
      setErrors((prev) => ({ ...prev, [name]: "" }));
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    setErrors({});

    try {
      const validatedData = requestSchema.parse(formData);

      // Simulate API delay
      await new Promise((resolve) => setTimeout(resolve, 800));

      // Add to applications
      addApplication({
        propertyId,
        propertyTitle,
        propertyImage,
        propertyPrice,
        message: validatedData.message,
      });

      setIsSubmitted(true);
      toast.success("Заявка успешно отправлена!");

      // Reset after delay
      setTimeout(() => {
        setOpen(false);
        setIsSubmitted(false);
        setFormData({ name: user?.name || "", phone: user?.phone || "", message: "" });
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
    if (newOpen && !isAuthenticated) {
      toast.error("Для отправки заявки необходимо авторизоваться");
      navigate("/auth");
      return;
    }
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
          <Button
            variant="outline"
            className="w-full h-12 border-primary text-primary hover:bg-primary/5 font-medium rounded-xl"
          >
            Оставить заявку
          </Button>
        )}
      </DialogTrigger>
      <DialogContent className="sm:max-w-md">
        {isSubmitted ? (
          <div className="py-8 text-center space-y-4">
            <div className="w-16 h-16 rounded-full bg-success/10 flex items-center justify-center mx-auto">
              <CheckCircle className="w-8 h-8 text-success" />
            </div>
            <div>
              <h3 className="text-xl font-semibold text-foreground">Заявка отправлена!</h3>
              <p className="text-muted-foreground mt-2">
                Наш менеджер свяжется с вами в ближайшее время
              </p>
            </div>
          </div>
        ) : (
          <>
            <DialogHeader>
              <DialogTitle className="text-xl font-display">Заявка на просмотр</DialogTitle>
              <DialogDescription className="text-muted-foreground">
                Оставьте свои контакты и мы свяжемся с вами для записи на просмотр
              </DialogDescription>
            </DialogHeader>

            {/* Property preview */}
            <div className="flex items-center gap-3 p-3 bg-muted/50 rounded-xl">
              <img
                src={propertyImage}
                alt={propertyTitle}
                className="w-16 h-16 rounded-lg object-cover"
              />
              <div className="flex-1 min-w-0">
                <p className="font-medium text-foreground text-sm truncate">{propertyTitle}</p>
                <p className="text-primary font-semibold">
                  {propertyPrice.toLocaleString("ru-RU")} ₽
                </p>
              </div>
            </div>

            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="name" className="flex items-center gap-2">
                  <User className="w-4 h-4 text-muted-foreground" />
                  Ваше имя
                </Label>
                <Input
                  id="name"
                  name="name"
                  value={formData.name}
                  onChange={handleChange}
                  placeholder="Иван Иванов"
                  className={errors.name ? "border-destructive" : ""}
                />
                {errors.name && <p className="text-xs text-destructive">{errors.name}</p>}
              </div>

              <div className="space-y-2">
                <Label htmlFor="phone" className="flex items-center gap-2">
                  <Phone className="w-4 h-4 text-muted-foreground" />
                  Телефон
                </Label>
                <Input
                  id="phone"
                  name="phone"
                  type="tel"
                  value={formData.phone}
                  onChange={handleChange}
                  placeholder="+7 (___) ___-__-__"
                  className={errors.phone ? "border-destructive" : ""}
                />
                {errors.phone && <p className="text-xs text-destructive">{errors.phone}</p>}
              </div>

              <div className="space-y-2">
                <Label htmlFor="message" className="flex items-center gap-2">
                  <MessageSquare className="w-4 h-4 text-muted-foreground" />
                  Комментарий
                  <span className="text-muted-foreground text-xs">(необязательно)</span>
                </Label>
                <Textarea
                  id="message"
                  name="message"
                  value={formData.message}
                  onChange={handleChange}
                  placeholder="Удобное время для звонка, вопросы..."
                  rows={3}
                  className={errors.message ? "border-destructive" : ""}
                />
                {errors.message && <p className="text-xs text-destructive">{errors.message}</p>}
              </div>

              <Button
                type="submit"
                disabled={isLoading}
                className="w-full min-h-[44px] h-12 gap-2 bg-primary hover:bg-primary/90 text-primary-foreground font-medium rounded-xl"
                aria-label={isLoading ? "Отправка заявки" : "Отправить заявку на просмотр"}
              >
                {isLoading ? (
                  <>
                    <div className="w-4 h-4 border-2 border-primary-foreground/30 border-t-primary-foreground rounded-full animate-spin" />
                    Отправка...
                  </>
                ) : (
                  <>
                    <Calendar className="w-5 h-5" />
                    Записаться на просмотр
                  </>
                )}
              </Button>

              <p className="text-xs text-muted-foreground text-center">
                Нажимая кнопку, вы соглашаетесь с обработкой персональных данных
              </p>
            </form>
          </>
        )}
      </DialogContent>
    </Dialog>
  );
};

export default ViewingRequestForm;

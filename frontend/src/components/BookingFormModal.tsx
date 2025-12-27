import { useState } from "react";
import { z } from "zod";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Calendar, User, Phone, Clock, CheckCircle, Loader2 } from "lucide-react";
import { toast } from "sonner";

const bookingSchema = z.object({
  name: z.string().trim().min(2, "Введите ваше имя").max(100, "Имя слишком длинное"),
  phone: z.string().trim().min(10, "Введите корректный номер телефона").max(20, "Номер слишком длинный"),
  date: z.string().min(1, "Выберите дату"),
  time: z.string().min(1, "Выберите время"),
});

interface BookingFormModalProps {
  propertyId?: string;
  propertyTitle?: string;
  trigger?: React.ReactNode;
  onSubmit?: (data: { name: string; phone: string; date: string; time: string }) => Promise<void>;
}

const timeSlots = [
  "10:00",
  "11:00",
  "12:00",
  "13:00",
  "14:00",
  "15:00",
  "16:00",
  "17:00",
  "18:00",
  "19:00",
];

const BookingFormModal = ({
  propertyId,
  propertyTitle,
  trigger,
  onSubmit,
}: BookingFormModalProps) => {
  const [open, setOpen] = useState(false);
  const [isSubmitted, setIsSubmitted] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});

  const [formData, setFormData] = useState({
    name: "",
    phone: "",
    date: "",
    time: "",
  });

  // Get minimum date (tomorrow)
  const getMinDate = () => {
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    return tomorrow.toISOString().split("T")[0];
  };

  // Get maximum date (30 days from now)
  const getMaxDate = () => {
    const maxDate = new Date();
    maxDate.setDate(maxDate.getDate() + 30);
    return maxDate.toISOString().split("T")[0];
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
    if (errors[name]) {
      setErrors((prev) => ({ ...prev, [name]: "" }));
    }
  };

  const handleTimeChange = (value: string) => {
    setFormData((prev) => ({ ...prev, time: value }));
    if (errors.time) {
      setErrors((prev) => ({ ...prev, time: "" }));
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    setErrors({});

    try {
      const validatedData = bookingSchema.parse(formData) as { name: string; phone: string; date: string; time: string };

      if (onSubmit) {
        await onSubmit(validatedData);
      } else {
        // Default behavior - simulate API delay
        await new Promise((resolve) => setTimeout(resolve, 800));

        // Store booking in localStorage for demo
        const bookings = JSON.parse(localStorage.getItem("livegrid_bookings") || "[]");
        bookings.push({
          ...validatedData,
          propertyId,
          propertyTitle,
          createdAt: new Date().toISOString(),
        });
        localStorage.setItem("livegrid_bookings", JSON.stringify(bookings));
      }

      setIsSubmitted(true);
      toast.success("Запись на просмотр подтверждена!");

      // Reset after delay
      setTimeout(() => {
        setOpen(false);
        setIsSubmitted(false);
        setFormData({ name: "", phone: "", date: "", time: "" });
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
        toast.error("Произошла ошибка при записи на просмотр");
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
          <Button variant="secondary" className="gap-2">
            <Calendar className="w-4 h-4" />
            Записаться на просмотр
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
              <h3 className="text-xl font-semibold text-foreground">Записано!</h3>
              <p className="text-muted-foreground mt-2">
                Мы свяжемся с вами для подтверждения
              </p>
            </div>
          </div>
        ) : (
          <>
            <DialogHeader>
              <DialogTitle className="flex items-center gap-2">
                <Calendar className="w-5 h-5 text-primary" />
                Записаться на просмотр
              </DialogTitle>
            </DialogHeader>

            {propertyTitle && (
              <p className="text-sm text-muted-foreground bg-muted/50 p-3 rounded-lg">
                {propertyTitle}
              </p>
            )}

            <form onSubmit={handleSubmit} className="space-y-4">
              {/* Name */}
              <div className="space-y-2">
                <Label htmlFor="booking-name" className="flex items-center gap-2">
                  <User className="w-4 h-4 text-muted-foreground" />
                  Ваше имя
                </Label>
                <Input
                  id="booking-name"
                  name="name"
                  value={formData.name}
                  onChange={handleChange}
                  placeholder="Иван Иванов"
                  className={errors.name ? "border-destructive" : ""}
                />
                {errors.name && <p className="text-xs text-destructive">{errors.name}</p>}
              </div>

              {/* Phone */}
              <div className="space-y-2">
                <Label htmlFor="booking-phone" className="flex items-center gap-2">
                  <Phone className="w-4 h-4 text-muted-foreground" />
                  Телефон
                </Label>
                <Input
                  id="booking-phone"
                  name="phone"
                  type="tel"
                  value={formData.phone}
                  onChange={handleChange}
                  placeholder="+7 (900) 000-00-00"
                  className={errors.phone ? "border-destructive" : ""}
                />
                {errors.phone && <p className="text-xs text-destructive">{errors.phone}</p>}
              </div>

              {/* Date */}
              <div className="space-y-2">
                <Label htmlFor="booking-date" className="flex items-center gap-2">
                  <Calendar className="w-4 h-4 text-muted-foreground" />
                  Дата просмотра
                </Label>
                <Input
                  id="booking-date"
                  name="date"
                  type="date"
                  value={formData.date}
                  onChange={handleChange}
                  min={getMinDate()}
                  max={getMaxDate()}
                  className={errors.date ? "border-destructive" : ""}
                />
                {errors.date && <p className="text-xs text-destructive">{errors.date}</p>}
              </div>

              {/* Time */}
              <div className="space-y-2">
                <Label className="flex items-center gap-2">
                  <Clock className="w-4 h-4 text-muted-foreground" />
                  Время
                </Label>
                <Select value={formData.time} onValueChange={handleTimeChange}>
                  <SelectTrigger className={errors.time ? "border-destructive" : ""}>
                    <SelectValue placeholder="Выберите время" />
                  </SelectTrigger>
                  <SelectContent>
                    {timeSlots.map((slot) => (
                      <SelectItem key={slot} value={slot}>
                        {slot}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {errors.time && <p className="text-xs text-destructive">{errors.time}</p>}
              </div>

              <Button
                type="submit"
                disabled={isLoading}
                className="w-full min-h-[44px]"
                aria-label={isLoading ? "Отправка заявки на просмотр" : "Подтвердить запись на просмотр"}
              >
                {isLoading ? (
                  <>
                    <Loader2 className="w-4 h-4 animate-spin mr-2" />
                    Отправка...
                  </>
                ) : (
                  "Подтвердить запись"
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

export default BookingFormModal;

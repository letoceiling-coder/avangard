import { useState } from "react";
import { z } from "zod";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Checkbox } from "@/components/ui/checkbox";
import { CheckCircle, Loader2 } from "lucide-react";
import { toast } from "sonner";
import { Link } from "react-router-dom";

const leadSchema = z.object({
  name: z.string().trim().min(2, "Введите ваше имя").max(100, "Имя слишком длинное"),
  phone: z.string().trim().min(10, "Введите корректный номер телефона").max(20, "Номер слишком длинный"),
  message: z.string().trim().max(500, "Сообщение слишком длинное").optional(),
});

interface LeadCaptureFormProps {
  title?: string;
  subtitle?: string;
  buttonText?: string;
  showMessage?: boolean;
  onSubmit?: (data: { name: string; phone: string; message?: string }) => Promise<void>;
  source?: string;
}

const LeadCaptureForm = ({
  title = "Получить консультацию",
  subtitle,
  buttonText = "Отправить заявку",
  showMessage = true,
  onSubmit,
  source = "website",
}: LeadCaptureFormProps) => {
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
      const validatedData = leadSchema.parse(formData) as { name: string; phone: string; message?: string };

      if (onSubmit) {
        await onSubmit(validatedData);
      } else {
        // Default behavior - simulate API delay
        await new Promise((resolve) => setTimeout(resolve, 800));
        
        // Store lead in localStorage for demo
        const leads = JSON.parse(localStorage.getItem("livegrid_leads") || "[]");
        leads.push({
          ...validatedData,
          source,
          createdAt: new Date().toISOString(),
        });
        localStorage.setItem("livegrid_leads", JSON.stringify(leads));
      }

      setIsSubmitted(true);
      toast.success("Заявка успешно отправлена!");

      // Reset after delay
      setTimeout(() => {
        setIsSubmitted(false);
        setFormData({ name: "", phone: "", message: "" });
        setAgreed(false);
      }, 3000);
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

  if (isSubmitted) {
    return (
      <Card>
        <CardContent className="py-8 text-center space-y-4">
          <div className="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto">
            <CheckCircle className="w-8 h-8 text-green-600" />
          </div>
          <div>
            <h3 className="text-xl font-semibold text-foreground">Спасибо!</h3>
            <p className="text-muted-foreground mt-2">
              Мы свяжемся с вами в ближайшее время
            </p>
          </div>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-xl">{title}</CardTitle>
        {subtitle && <p className="text-muted-foreground text-sm">{subtitle}</p>}
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit} className="space-y-4">
          {/* Name */}
          <div>
            <Input
              name="name"
              value={formData.name}
              onChange={handleChange}
              placeholder="Ваше имя"
              className={errors.name ? "border-destructive" : ""}
            />
            {errors.name && <p className="text-xs text-destructive mt-1">{errors.name}</p>}
          </div>

          {/* Phone */}
          <div>
            <Input
              name="phone"
              type="tel"
              value={formData.phone}
              onChange={handleChange}
              placeholder="+7 (900) 000-00-00"
              className={errors.phone ? "border-destructive" : ""}
            />
            {errors.phone && <p className="text-xs text-destructive mt-1">{errors.phone}</p>}
          </div>

          {/* Message (Optional) */}
          {showMessage && (
            <div>
              <Textarea
                name="message"
                value={formData.message}
                onChange={handleChange}
                placeholder="Ваш вопрос..."
                rows={3}
                className={`resize-none ${errors.message ? "border-destructive" : ""}`}
              />
              {errors.message && <p className="text-xs text-destructive mt-1">{errors.message}</p>}
            </div>
          )}

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

          {/* Submit */}
          <Button
            type="submit"
            disabled={!agreed || isLoading}
            className="w-full min-h-[44px]"
            aria-label={isLoading ? "Отправка формы" : buttonText}
          >
            {isLoading ? (
              <>
                <Loader2 className="w-4 h-4 animate-spin mr-2" />
                Отправка...
              </>
            ) : (
              buttonText
            )}
          </Button>
        </form>
      </CardContent>
    </Card>
  );
};

export default LeadCaptureForm;

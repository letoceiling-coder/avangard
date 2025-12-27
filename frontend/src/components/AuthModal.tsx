import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Checkbox } from "@/components/ui/checkbox";
import { useAuth } from "@/hooks/useAuth";
import { toast } from "sonner";
import { supabase } from "@/integrations/supabase/client";
import { Mail, Lock, User, Eye, EyeOff, X, MessageCircle, ArrowRight, Loader2 } from "lucide-react";
import { cn } from "@/lib/utils";

interface AuthModalProps {
  isOpen: boolean;
  onClose: () => void;
  defaultMode?: "login" | "signup";
}

const AuthModal = ({ isOpen, onClose, defaultMode = "login" }: AuthModalProps) => {
  const [mode, setMode] = useState<"login" | "signup">(defaultMode);
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [name, setName] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [agreeToTerms, setAgreeToTerms] = useState(false);
  const [errors, setErrors] = useState<{ email?: string; password?: string; name?: string; terms?: string }>({});
  
  const { login, register } = useAuth();
  const navigate = useNavigate();

  const resetForm = () => {
    setEmail("");
    setPassword("");
    setName("");
    setShowPassword(false);
    setErrors({});
    setAgreeToTerms(false);
  };

  const handleModeSwitch = (newMode: "login" | "signup") => {
    setMode(newMode);
    setErrors({});
  };

  const validateForm = (): boolean => {
    const newErrors: typeof errors = {};

    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!email.trim()) {
      newErrors.email = "Введите email";
    } else if (!emailRegex.test(email)) {
      newErrors.email = "Некорректный email";
    }

    // Password validation
    if (!password) {
      newErrors.password = "Введите пароль";
    } else if (password.length < 6) {
      newErrors.password = "Минимум 6 символов";
    }

    // Name validation (signup only)
    if (mode === "signup" && !name.trim()) {
      newErrors.name = "Введите имя";
    }

    // Terms validation (signup only)
    if (mode === "signup" && !agreeToTerms) {
      newErrors.terms = "Необходимо согласие";
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!validateForm()) return;

    setIsSubmitting(true);

    try {
      if (mode === "login") {
        const result = await login(email, password);
        if (result.success) {
          toast.success("Вы успешно вошли!");
          resetForm();
          onClose();
        } else {
          toast.error(result.error || "Ошибка входа");
        }
      } else {
        const result = await register(email, password, name);
        if (result.success) {
          toast.success("Регистрация успешна!");
          resetForm();
          onClose();
        } else {
          toast.error(result.error || "Ошибка регистрации");
        }
      }
    } catch (error) {
      toast.error("Произошла ошибка");
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleTelegramLogin = () => {
    toast.info("Вход через Telegram будет доступен скоро");
  };

  const handleClose = () => {
    resetForm();
    onClose();
  };

  return (
    <Dialog open={isOpen} onOpenChange={handleClose}>
      <DialogContent className="sm:max-w-[440px] p-0 gap-0 overflow-hidden">
        {/* Header with Tabs */}
        <div className="p-6 pb-0">
          <DialogHeader className="mb-6">
            <DialogTitle className="text-2xl font-display font-bold text-center">
              {mode === "login" ? "Вход в аккаунт" : "Регистрация"}
            </DialogTitle>
          </DialogHeader>

          {/* Tab Switcher */}
          <div className="flex gap-6 justify-center border-b border-border">
            <button
              onClick={() => handleModeSwitch("login")}
              className={cn(
                "pb-3 font-semibold text-base transition-colors relative",
                mode === "login" 
                  ? "text-primary" 
                  : "text-muted-foreground hover:text-foreground"
              )}
            >
              Вход
              {mode === "login" && (
                <div className="absolute bottom-0 left-0 right-0 h-0.5 bg-primary rounded-full" />
              )}
            </button>
            <button
              onClick={() => handleModeSwitch("signup")}
              className={cn(
                "pb-3 font-semibold text-base transition-colors relative",
                mode === "signup" 
                  ? "text-primary" 
                  : "text-muted-foreground hover:text-foreground"
              )}
            >
              Регистрация
              {mode === "signup" && (
                <div className="absolute bottom-0 left-0 right-0 h-0.5 bg-primary rounded-full" />
              )}
            </button>
          </div>
        </div>

        {/* Form Content */}
        <div className="p-6">
          <form onSubmit={handleSubmit} className="space-y-4">
            {/* Name Field (Signup only) */}
            {mode === "signup" && (
              <div className="space-y-2">
                <Label htmlFor="auth-name" className="text-foreground">Имя</Label>
                <div className="relative">
                  <User className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground" />
                  <Input
                    id="auth-name"
                    type="text"
                    value={name}
                    onChange={(e) => { setName(e.target.value); setErrors({ ...errors, name: undefined }); }}
                    placeholder="Ваше имя"
                    className={cn("pl-10 h-11", errors.name && "border-destructive")}
                  />
                </div>
                {errors.name && <p className="text-xs text-destructive">{errors.name}</p>}
              </div>
            )}

            {/* Email Field */}
            <div className="space-y-2">
              <Label htmlFor="auth-email" className="text-foreground">Email</Label>
              <div className="relative">
                <Mail className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground" />
                <Input
                  id="auth-email"
                  type="email"
                  value={email}
                  onChange={(e) => { setEmail(e.target.value); setErrors({ ...errors, email: undefined }); }}
                  placeholder="example@email.com"
                  className={cn("pl-10 h-11", errors.email && "border-destructive")}
                />
              </div>
              {errors.email && <p className="text-xs text-destructive">{errors.email}</p>}
            </div>

            {/* Password Field */}
            <div className="space-y-2">
              <Label htmlFor="auth-password" className="text-foreground">Пароль</Label>
              <div className="relative">
                <Lock className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground" />
                <Input
                  id="auth-password"
                  type={showPassword ? "text" : "password"}
                  value={password}
                  onChange={(e) => { setPassword(e.target.value); setErrors({ ...errors, password: undefined }); }}
                  placeholder="Минимум 6 символов"
                  className={cn("pl-10 pr-10 h-11", errors.password && "border-destructive")}
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
                >
                  {showPassword ? <EyeOff className="w-5 h-5" /> : <Eye className="w-5 h-5" />}
                </button>
              </div>
              {errors.password && <p className="text-xs text-destructive">{errors.password}</p>}
            </div>

            {/* Forgot Password (Login only) */}
            {mode === "login" && (
              <div className="text-right">
                <button 
                  type="button" 
                  onClick={async () => {
                    if (!email.trim()) {
                      toast.error("Введите email для восстановления пароля");
                      return;
                    }
                    if (!supabase) {
                      toast.error("Supabase не настроен");
                      return;
                    }
                    const { error } = await supabase.auth.resetPasswordForEmail(email.trim(), {
                      redirectTo: `${window.location.origin}/auth`,
                    });
                    if (error) {
                      toast.error("Ошибка отправки письма");
                    } else {
                      toast.success("Инструкции по восстановлению отправлены на email");
                    }
                  }}
                  className="text-sm text-primary hover:underline"
                >
                  Забыли пароль?
                </button>
              </div>
            )}

            {/* Terms Checkbox (Signup only) */}
            {mode === "signup" && (
              <div className="space-y-1">
                <div className="flex items-start gap-3">
                  <Checkbox
                    id="auth-terms"
                    checked={agreeToTerms}
                    onCheckedChange={(checked) => { 
                      setAgreeToTerms(checked as boolean); 
                      setErrors({ ...errors, terms: undefined }); 
                    }}
                    className="mt-0.5"
                  />
                  <label htmlFor="auth-terms" className="text-sm text-muted-foreground leading-tight">
                    Я согласен с{" "}
                    <Link to="/terms" onClick={handleClose} className="text-primary hover:underline">
                      условиями использования
                    </Link>
                    {" "}и{" "}
                    <Link to="/privacy" onClick={handleClose} className="text-primary hover:underline">
                      политикой конфиденциальности
                    </Link>
                  </label>
                </div>
                {errors.terms && <p className="text-xs text-destructive ml-7">{errors.terms}</p>}
              </div>
            )}

            {/* Submit Button */}
            <Button 
              type="submit" 
              variant="primary"
              size="lg"
              fullWidth
              disabled={isSubmitting}
            >
              {isSubmitting ? (
                <>
                  <Loader2 className="w-5 h-5 animate-spin" />
                  Загрузка...
                </>
              ) : (
                <>
                  {mode === "login" ? "Войти" : "Создать аккаунт"}
                  <ArrowRight className="w-4 h-4" />
                </>
              )}
            </Button>
          </form>

          {/* Divider */}
          <div className="flex items-center gap-4 my-6">
            <div className="flex-1 h-px bg-border" />
            <span className="text-sm text-muted-foreground">или</span>
            <div className="flex-1 h-px bg-border" />
          </div>

          {/* Telegram Button */}
          <Button
            type="button"
            variant="secondary"
            size="lg"
            fullWidth
            onClick={handleTelegramLogin}
            leftIcon={<MessageCircle className="w-5 h-5" />}
          >
            {mode === "login" ? "Войти через Telegram" : "Регистрация через Telegram"}
          </Button>

          {/* Switch Mode Link */}
          <p className="text-center text-sm text-muted-foreground mt-6">
            {mode === "login" ? "Нет аккаунта? " : "Уже есть аккаунт? "}
            <button
              type="button"
              onClick={() => handleModeSwitch(mode === "login" ? "signup" : "login")}
              className="text-primary hover:underline font-medium"
            >
              {mode === "login" ? "Зарегистрироваться" : "Войти"}
            </button>
          </p>
        </div>
      </DialogContent>
    </Dialog>
  );
};

export default AuthModal;

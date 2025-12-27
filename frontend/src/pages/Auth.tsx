import { useState } from "react";
import { Link, useNavigate, useLocation } from "react-router-dom";
import Header from "@/components/Header";
import Footer from "@/components/Footer";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useAuth } from "@/hooks/useAuth";
import { toast } from "sonner";
import { supabase } from "@/integrations/supabase/client";
import { Mail, Lock, User, ArrowRight, Eye, EyeOff } from "lucide-react";

const Auth = () => {
  const [isLogin, setIsLogin] = useState(true);
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [name, setName] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  
  const { login, register } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const from = (location.state as any)?.from?.pathname || "/profile";

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!email || !password || (!isLogin && !name)) {
      toast.error("Заполните все поля");
      return;
    }

    if (password.length < 6) {
      toast.error("Пароль должен быть не менее 6 символов");
      return;
    }

    setIsSubmitting(true);

    try {
      if (isLogin) {
        const result = await login(email, password);
        if (result.success) {
          toast.success("Вы успешно вошли!");
          navigate(from, { replace: true });
        } else {
          toast.error(result.error || "Ошибка входа");
        }
      } else {
        const result = await register(email, password, name);
        if (result.success) {
          toast.success("Регистрация успешна!");
          navigate(from, { replace: true });
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

  return (
    <div className="min-h-screen bg-background">
      <Header />

      <main className="container mx-auto px-4 py-12 md:py-20">
        <div className="max-w-md mx-auto">
          <div className="text-center mb-8">
            <h1 className="text-3xl font-display font-bold text-foreground mb-2">
              {isLogin ? "Вход в аккаунт" : "Регистрация"}
            </h1>
            <p className="text-muted-foreground">
              {isLogin 
                ? "Войдите, чтобы получить доступ к избранному и заявкам" 
                : "Создайте аккаунт для сохранения избранного"
              }
            </p>
          </div>

          <div className="bg-card rounded-2xl border border-border p-8 shadow-card">
            {/* Tabs */}
            <div className="flex mb-6 bg-muted rounded-lg p-1">
              <button
                onClick={() => setIsLogin(true)}
                className={`flex-1 py-2.5 text-sm font-medium rounded-md transition-colors ${
                  isLogin 
                    ? "bg-card text-foreground shadow-sm" 
                    : "text-muted-foreground hover:text-foreground"
                }`}
              >
                Вход
              </button>
              <button
                onClick={() => setIsLogin(false)}
                className={`flex-1 py-2.5 text-sm font-medium rounded-md transition-colors ${
                  !isLogin 
                    ? "bg-card text-foreground shadow-sm" 
                    : "text-muted-foreground hover:text-foreground"
                }`}
              >
                Регистрация
              </button>
            </div>

            <form onSubmit={handleSubmit} className="space-y-4">
              {!isLogin && (
                <div>
                  <Label htmlFor="name" className="text-foreground">Имя</Label>
                  <div className="relative mt-1.5">
                    <User className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground" />
                    <Input
                      id="name"
                      type="text"
                      value={name}
                      onChange={(e) => setName(e.target.value)}
                      placeholder="Ваше имя"
                      className="pl-10 h-12 lg-input"
                    />
                  </div>
                </div>
              )}

              <div>
                <Label htmlFor="email" className="text-foreground">Email</Label>
                <div className="relative mt-1.5">
                  <Mail className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground" />
                  <Input
                    id="email"
                    type="email"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    placeholder="example@email.com"
                    className="pl-10 h-12 lg-input"
                  />
                </div>
              </div>

              <div>
                <Label htmlFor="password" className="text-foreground">Пароль</Label>
                <div className="relative mt-1.5">
                  <Lock className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground" />
                  <Input
                    id="password"
                    type={showPassword ? "text" : "password"}
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    placeholder="Минимум 6 символов"
                    className="pl-10 pr-10 h-12 lg-input"
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword(!showPassword)}
                    className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                  >
                    {showPassword ? <EyeOff className="w-5 h-5" /> : <Eye className="w-5 h-5" />}
                  </button>
                </div>
              </div>

              {isLogin && (
                <div className="text-right">
                  <button 
                    type="button" 
                    onClick={async () => {
                      if (!email) {
                        toast.error("Введите email для восстановления пароля");
                        return;
                      }
                      if (!supabase) {
                        toast.error("Supabase не настроен");
                        return;
                      }
                      const { error } = await supabase.auth.resetPasswordForEmail(email, {
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

              <Button 
                type="submit" 
                className="w-full h-12 lg-btn-primary"
                disabled={isSubmitting}
              >
                {isSubmitting ? (
                  <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin" />
                ) : (
                  <>
                    {isLogin ? "Войти" : "Создать аккаунт"}
                    <ArrowRight className="w-4 h-4 ml-2" />
                  </>
                )}
              </Button>
            </form>

            <p className="text-center text-sm text-muted-foreground mt-6">
              {isLogin ? "Нет аккаунта? " : "Уже есть аккаунт? "}
              <button
                onClick={() => setIsLogin(!isLogin)}
                className="text-primary hover:underline font-medium"
              >
                {isLogin ? "Зарегистрироваться" : "Войти"}
              </button>
            </p>
          </div>

          <p className="text-center text-xs text-muted-foreground mt-6">
            Нажимая кнопку, вы соглашаетесь с{" "}
            <Link to="/privacy" className="text-primary hover:underline">
              политикой конфиденциальности
            </Link>
          </p>
        </div>
      </main>

      <Footer />
    </div>
  );
};

export default Auth;

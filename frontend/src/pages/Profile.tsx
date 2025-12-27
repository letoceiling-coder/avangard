import { useState, useEffect } from "react";
import { Link, useNavigate } from "react-router-dom";
import Header from "@/components/Header";
import Footer from "@/components/Footer";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Switch } from "@/components/ui/switch";
import { 
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { useAuth } from "@/hooks/useAuth";
import { useFavorites } from "@/hooks/useFavorites";
import PropertyCard from "@/components/PropertyCard";
import AuthModal from "@/components/AuthModal";
import { toast } from "sonner";
import { cn } from "@/lib/utils";
import { 
  User, Heart, Clock, FileText, Settings, LogOut, 
  ChevronRight, Trash2, Mail, Phone, Camera, Bookmark,
  Bell, Shield, AlertTriangle, ExternalLink, Search
} from "lucide-react";

interface SavedSearch {
  id: string;
  name: string;
  filters: string;
  createdAt: string;
  resultsCount: number;
}

interface NotificationSettings {
  emailNotifications: boolean;
  pushNotifications: boolean;
  newListingsAlerts: boolean;
  priceDropAlerts: boolean;
}

const Profile = () => {
  const navigate = useNavigate();
  const { user, isAuthenticated, logout, updateProfile, viewHistory, clearViewHistory, applications } = useAuth();
  const { favorites, clearFavorites } = useFavorites();
  
  const [activeTab, setActiveTab] = useState("profile");
  const [isEditing, setIsEditing] = useState(false);
  const [editName, setEditName] = useState(user?.name || "");
  const [editPhone, setEditPhone] = useState(user?.phone || "");
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [deleteConfirmation, setDeleteConfirmation] = useState("");
  const [showAuthModal, setShowAuthModal] = useState(false);
  
  // Saved searches (mock data + localStorage)
  const [savedSearches, setSavedSearches] = useState<SavedSearch[]>([]);
  
  // Notification settings
  const [notifications, setNotifications] = useState<NotificationSettings>({
    emailNotifications: true,
    pushNotifications: false,
    newListingsAlerts: true,
    priceDropAlerts: true,
  });

  // Load saved searches and notification settings
  useEffect(() => {
    const saved = localStorage.getItem("savedSearches");
    if (saved) {
      setSavedSearches(JSON.parse(saved));
    } else {
      // Mock data
      setSavedSearches([
        {
          id: "1",
          name: "2-комнатные в центре",
          filters: "Белгород, Центральный район, 2-комн., 3-7 млн ₽",
          createdAt: new Date().toISOString(),
          resultsCount: 24,
        },
        {
          id: "2",
          name: "Новостройки с отделкой",
          filters: "Белгород, Новостройки, Чистовая отделка",
          createdAt: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString(),
          resultsCount: 15,
        },
      ]);
    }
    
    const notifSettings = localStorage.getItem("notificationSettings");
    if (notifSettings) {
      setNotifications(JSON.parse(notifSettings));
    }
  }, []);

  // Update editName when user changes
  useEffect(() => {
    if (user) {
      setEditName(user.name || "");
      setEditPhone(user.phone || "");
    }
  }, [user]);

  const navItems = [
    { id: "profile", label: "Профиль", icon: User },
    { id: "searches", label: "Сохранённые поиски", icon: Bookmark },
    { id: "favorites", label: "Избранное", icon: Heart, count: favorites.length },
    { id: "history", label: "История", icon: Clock, count: viewHistory.length },
    { id: "applications", label: "Заявки", icon: FileText, count: applications.length },
    { id: "settings", label: "Настройки", icon: Settings },
  ];

  const handleLogout = () => {
    logout();
    toast.success("Вы вышли из аккаунта");
    navigate("/");
  };

  const handleSaveProfile = () => {
    updateProfile({ name: editName, phone: editPhone });
    setIsEditing(false);
    toast.success("Профиль обновлён");
  };

  const handleDeleteSearch = (id: string) => {
    const updated = savedSearches.filter((s) => s.id !== id);
    setSavedSearches(updated);
    localStorage.setItem("savedSearches", JSON.stringify(updated));
    toast.success("Поиск удалён");
  };

  const handleNotificationChange = (key: keyof NotificationSettings, value: boolean) => {
    const updated = { ...notifications, [key]: value };
    setNotifications(updated);
    localStorage.setItem("notificationSettings", JSON.stringify(updated));
  };

  const handleDeleteAccount = () => {
    if (deleteConfirmation.toLowerCase() !== "удалить") {
      toast.error("Введите слово 'удалить' для подтверждения");
      return;
    }
    
    logout();
    // Clear all user data
    localStorage.removeItem("livegrid_current_user");
    localStorage.removeItem("favorites");
    localStorage.removeItem("savedSearches");
    localStorage.removeItem("notificationSettings");
    
    toast.success("Аккаунт удалён");
    setShowDeleteModal(false);
    navigate("/");
  };

  const getStatusLabel = (status: string) => {
    switch (status) {
      case "pending": return { label: "На рассмотрении", color: "bg-amber-100 text-amber-700" };
      case "viewed": return { label: "Просмотрено", color: "bg-primary/10 text-primary" };
      case "accepted": return { label: "Принята", color: "bg-green-100 text-green-700" };
      case "rejected": return { label: "Отклонена", color: "bg-destructive/10 text-destructive" };
      default: return { label: status, color: "bg-muted text-muted-foreground" };
    }
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString("ru-RU", {
      day: "numeric",
      month: "long",
      year: "numeric",
    });
  };

  // Not authenticated state
  if (!isAuthenticated) {
    return (
      <div className="min-h-screen bg-background">
        <Header />
        <main className="container mx-auto px-4 py-20">
          <div className="max-w-md mx-auto text-center">
            <div className="w-20 h-20 rounded-full bg-primary/10 flex items-center justify-center mx-auto mb-6">
              <User className="w-10 h-10 text-primary" />
            </div>
            <h1 className="text-2xl font-display font-bold text-foreground mb-3">
              Войдите в аккаунт
            </h1>
            <p className="text-muted-foreground mb-6">
              Чтобы получить доступ к личному кабинету, избранному и истории просмотров
            </p>
            <Button variant="primary" size="lg" onClick={() => setShowAuthModal(true)}>
              Войти или зарегистрироваться
            </Button>
          </div>
        </main>
        <Footer />
        <AuthModal isOpen={showAuthModal} onClose={() => setShowAuthModal(false)} />
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-background">
      <Header />

      <main className="container mx-auto px-4 py-6 md:py-8">
        <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
          {/* Sidebar */}
          <aside className="lg:col-span-1">
            <div className="bg-card rounded-2xl border border-border shadow-card sticky top-24 overflow-hidden">
              {/* User Info Header */}
              <div className="p-6 border-b border-border bg-gradient-to-br from-primary/5 to-transparent">
                <div className="flex items-center gap-4">
                  <div className="relative w-16 h-16 flex-shrink-0">
                    <div className="w-full h-full rounded-full bg-primary/10 flex items-center justify-center">
                      {user?.avatar ? (
                        <img src={user.avatar} alt={user.name} className="w-full h-full rounded-full object-cover" />
                      ) : (
                        <User className="w-8 h-8 text-primary" />
                      )}
                    </div>
                    <button 
                      onClick={() => toast.info("Загрузка аватара будет доступна в следующей версии")}
                      className="absolute -bottom-1 -right-1 w-7 h-7 rounded-full bg-card border border-border flex items-center justify-center hover:bg-muted transition-colors shadow-sm"
                    >
                      <Camera className="w-3.5 h-3.5 text-muted-foreground" />
                    </button>
                  </div>
                  <div className="min-w-0">
                    <h2 className="font-display font-semibold text-foreground truncate">{user?.name}</h2>
                    <p className="text-sm text-muted-foreground truncate">{user?.email}</p>
                  </div>
                </div>
              </div>

              {/* Navigation */}
              <nav className="p-3">
                {navItems.map((item) => (
                  <button
                    key={item.id}
                    onClick={() => setActiveTab(item.id)}
                    className={cn(
                      "w-full flex items-center justify-between px-4 py-3 rounded-xl font-medium transition-colors mb-1",
                      activeTab === item.id
                        ? "bg-primary/10 text-primary"
                        : "text-muted-foreground hover:text-foreground hover:bg-muted"
                    )}
                  >
                    <span className="flex items-center gap-3">
                      <item.icon className="w-5 h-5" />
                      {item.label}
                    </span>
                    {item.count !== undefined && item.count > 0 && (
                      <span className={cn(
                        "text-xs font-semibold px-2 py-0.5 rounded-full",
                        activeTab === item.id ? "bg-primary text-primary-foreground" : "bg-muted"
                      )}>
                        {item.count}
                      </span>
                    )}
                  </button>
                ))}
              </nav>

              {/* Logout Button */}
              <div className="p-3 pt-0">
                <button
                  onClick={handleLogout}
                  className="w-full flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-destructive hover:bg-destructive/10 transition-colors"
                >
                  <LogOut className="w-5 h-5" />
                  Выйти
                </button>
              </div>
            </div>
          </aside>

          {/* Main Content */}
          <div className="lg:col-span-3 space-y-6">
            {/* Profile Tab */}
            {activeTab === "profile" && (
              <>
                <div className="flex items-center justify-between">
                  <h1 className="text-2xl font-display font-bold text-foreground">Профиль</h1>
                </div>

                <div className="bg-card rounded-2xl border border-border p-6 shadow-card space-y-6">
                  {/* Avatar Section */}
                  <div className="flex items-center gap-6 pb-6 border-b border-border">
                    <div className="relative w-24 h-24">
                      <div className="w-full h-full rounded-full bg-primary/10 flex items-center justify-center">
                        {user?.avatar ? (
                          <img src={user.avatar} alt={user.name} className="w-full h-full rounded-full object-cover" />
                        ) : (
                          <User className="w-12 h-12 text-primary" />
                        )}
                      </div>
                    </div>
                    <div>
                      <h3 className="font-semibold text-foreground">{user?.name}</h3>
                      <p className="text-sm text-muted-foreground">{user?.email}</p>
                      <Button 
                        variant="secondary" 
                        size="sm" 
                        className="mt-3"
                        onClick={() => toast.info("Загрузка аватара будет доступна в следующей версии")}
                      >
                        <Camera className="w-4 h-4 mr-2" />
                        Загрузить новый аватар
                      </Button>
                    </div>
                  </div>

                  {/* Edit Form */}
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <Label className="text-foreground">Имя</Label>
                      <div className="relative mt-1.5">
                        <User className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground" />
                        <Input
                          value={editName}
                          onChange={(e) => setEditName(e.target.value)}
                          disabled={!isEditing}
                          className="pl-10 h-11"
                        />
                      </div>
                    </div>
                    <div>
                      <Label className="text-foreground">Email</Label>
                      <div className="relative mt-1.5">
                        <Mail className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground" />
                        <Input
                          value={user?.email}
                          disabled
                          className="pl-10 h-11 bg-muted"
                        />
                      </div>
                    </div>
                    <div className="md:col-span-2">
                      <Label className="text-foreground">Телефон</Label>
                      <div className="relative mt-1.5">
                        <Phone className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground" />
                        <Input
                          value={editPhone}
                          onChange={(e) => setEditPhone(e.target.value)}
                          disabled={!isEditing}
                          placeholder="+7 (___) ___-__-__"
                          className="pl-10 h-11"
                        />
                      </div>
                    </div>
                  </div>

                  {/* Actions */}
                  <div className="flex gap-3 pt-4">
                    {isEditing ? (
                      <>
                        <Button variant="primary" onClick={handleSaveProfile}>
                          Сохранить изменения
                        </Button>
                        <Button 
                          variant="secondary"
                          onClick={() => { 
                            setIsEditing(false); 
                            setEditName(user?.name || ""); 
                            setEditPhone(user?.phone || ""); 
                          }}
                        >
                          Отмена
                        </Button>
                      </>
                    ) : (
                      <Button variant="secondary" onClick={() => setIsEditing(true)}>
                        Редактировать
                      </Button>
                    )}
                  </div>
                </div>

                {/* Quick Stats */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                  {[
                    { label: "Избранное", value: favorites.length, icon: Heart },
                    { label: "Просмотры", value: viewHistory.length, icon: Clock },
                    { label: "Заявки", value: applications.length, icon: FileText },
                    { label: "Поиски", value: savedSearches.length, icon: Bookmark },
                  ].map((stat, i) => (
                    <div key={i} className="bg-card rounded-xl border border-border p-4 text-center">
                      <stat.icon className="w-6 h-6 text-primary mx-auto mb-2" />
                      <p className="text-2xl font-bold text-foreground">{stat.value}</p>
                      <p className="text-sm text-muted-foreground">{stat.label}</p>
                    </div>
                  ))}
                </div>
              </>
            )}

            {/* Saved Searches Tab */}
            {activeTab === "searches" && (
              <>
                <div className="flex items-center justify-between">
                  <h1 className="text-2xl font-display font-bold text-foreground">Сохранённые поиски</h1>
                </div>

                {savedSearches.length > 0 ? (
                  <div className="space-y-3">
                    {savedSearches.map((search) => (
                      <div 
                        key={search.id}
                        className="bg-card rounded-xl border border-border p-4 flex flex-col sm:flex-row sm:items-center gap-4"
                      >
                        <div className="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
                          <Search className="w-6 h-6 text-primary" />
                        </div>
                        <div className="flex-1 min-w-0">
                          <h3 className="font-semibold text-foreground">{search.name}</h3>
                          <p className="text-sm text-muted-foreground truncate">{search.filters}</p>
                          <p className="text-xs text-muted-foreground mt-1">
                            Создано: {formatDate(search.createdAt)} • {search.resultsCount} результатов
                          </p>
                        </div>
                        <div className="flex gap-2">
                          <Link to="/catalog">
                            <Button variant="secondary" size="sm" leftIcon={<ExternalLink className="w-4 h-4" />}>
                              Перейти
                            </Button>
                          </Link>
                          <Button 
                            variant="ghost" 
                            size="icon"
                            onClick={() => handleDeleteSearch(search.id)}
                            className="text-muted-foreground hover:text-destructive"
                          >
                            <Trash2 className="w-4 h-4" />
                          </Button>
                        </div>
                      </div>
                    ))}
                  </div>
                ) : (
                  <div className="bg-card rounded-2xl border border-border p-12 text-center">
                    <Bookmark className="w-12 h-12 text-muted-foreground/30 mx-auto mb-4" />
                    <h3 className="font-semibold text-foreground mb-2">Нет сохранённых поисков</h3>
                    <p className="text-muted-foreground mb-6">
                      Сохраняйте поиски в каталоге, чтобы быстро возвращаться к ним
                    </p>
                    <Link to="/catalog">
                      <Button variant="primary">Перейти в каталог →</Button>
                    </Link>
                  </div>
                )}
              </>
            )}

            {/* Favorites Tab */}
            {activeTab === "favorites" && (
              <>
                <div className="flex items-center justify-between">
                  <h1 className="text-2xl font-display font-bold text-foreground">
                    Избранное ({favorites.length})
                  </h1>
                  {favorites.length > 0 && (
                    <Button 
                      variant="ghost" 
                      size="sm" 
                      className="text-muted-foreground hover:text-destructive"
                      onClick={() => { clearFavorites(); toast.success("Избранное очищено"); }}
                      leftIcon={<Trash2 className="w-4 h-4" />}
                    >
                      Очистить
                    </Button>
                  )}
                </div>

                {favorites.length > 0 ? (
                  <>
                    <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                      {favorites.slice(0, 6).map((property) => (
                        <PropertyCard key={property.id} property={property} />
                      ))}
                    </div>
                    {favorites.length > 6 && (
                      <div className="text-center">
                        <Link to="/favorites">
                          <Button variant="secondary">
                            Перейти к полному списку избранного →
                          </Button>
                        </Link>
                      </div>
                    )}
                  </>
                ) : (
                  <div className="bg-card rounded-2xl border border-border p-12 text-center">
                    <Heart className="w-12 h-12 text-muted-foreground/30 mx-auto mb-4" />
                    <h3 className="font-semibold text-foreground mb-2">Нет сохранённых объектов</h3>
                    <p className="text-muted-foreground mb-6">
                      Добавляйте понравившиеся объекты в избранное
                    </p>
                    <Link to="/catalog">
                      <Button variant="primary">Перейти в каталог</Button>
                    </Link>
                  </div>
                )}
              </>
            )}

            {/* History Tab */}
            {activeTab === "history" && (
              <>
                <div className="flex items-center justify-between">
                  <h1 className="text-2xl font-display font-bold text-foreground">История просмотров</h1>
                  {viewHistory.length > 0 && (
                    <Button 
                      variant="ghost" 
                      size="sm" 
                      className="text-muted-foreground hover:text-destructive"
                      onClick={() => { clearViewHistory(); toast.success("История очищена"); }}
                      leftIcon={<Trash2 className="w-4 h-4" />}
                    >
                      Очистить
                    </Button>
                  )}
                </div>

                {viewHistory.length > 0 ? (
                  <div className="space-y-3">
                    {viewHistory.map((item) => (
                      <Link 
                        key={item.id} 
                        to={`/property/${item.id}`}
                        className="flex items-center gap-4 p-4 bg-card rounded-xl border border-border hover:border-primary/30 transition-colors group"
                      >
                        <img 
                          src={item.image} 
                          alt={item.title} 
                          className="w-20 h-16 rounded-lg object-cover"
                        />
                        <div className="flex-1 min-w-0">
                          <h3 className="font-medium text-foreground truncate group-hover:text-primary transition-colors">
                            {item.title}
                          </h3>
                          <p className="text-sm text-muted-foreground">{item.address}</p>
                        </div>
                        <div className="text-right hidden sm:block">
                          <p className="font-semibold text-foreground">
                            {item.price.toLocaleString("ru-RU")} ₽
                          </p>
                          <p className="text-xs text-muted-foreground">
                            {formatDate(item.viewedAt)}
                          </p>
                        </div>
                        <ChevronRight className="w-5 h-5 text-muted-foreground group-hover:text-primary" />
                      </Link>
                    ))}
                  </div>
                ) : (
                  <div className="bg-card rounded-2xl border border-border p-12 text-center">
                    <Clock className="w-12 h-12 text-muted-foreground/30 mx-auto mb-4" />
                    <h3 className="font-semibold text-foreground mb-2">История просмотров пуста</h3>
                    <p className="text-muted-foreground mb-6">
                      Просматривайте объекты, чтобы они появились здесь
                    </p>
                    <Link to="/catalog">
                      <Button variant="primary">Начать просмотр</Button>
                    </Link>
                  </div>
                )}
              </>
            )}

            {/* Applications Tab */}
            {activeTab === "applications" && (
              <>
                <div className="flex items-center justify-between">
                  <h1 className="text-2xl font-display font-bold text-foreground">Мои заявки</h1>
                </div>

                {applications.length > 0 ? (
                  <div className="space-y-3">
                    {applications.map((app) => {
                      const status = getStatusLabel(app.status);
                      return (
                        <div 
                          key={app.id}
                          className="flex flex-col sm:flex-row sm:items-center gap-4 p-4 bg-card rounded-xl border border-border"
                        >
                          <img 
                            src={app.propertyImage} 
                            alt={app.propertyTitle} 
                            className="w-20 h-16 rounded-lg object-cover flex-shrink-0"
                          />
                          <div className="flex-1 min-w-0">
                            <h3 className="font-medium text-foreground truncate">
                              {app.propertyTitle}
                            </h3>
                            <p className="text-sm text-muted-foreground">
                              Заявка от {formatDate(app.createdAt)}
                            </p>
                          </div>
                          <span className={`inline-flex px-3 py-1 rounded-full text-xs font-medium ${status.color}`}>
                            {status.label}
                          </span>
                        </div>
                      );
                    })}
                  </div>
                ) : (
                  <div className="bg-card rounded-2xl border border-border p-12 text-center">
                    <FileText className="w-12 h-12 text-muted-foreground/30 mx-auto mb-4" />
                    <h3 className="font-semibold text-foreground mb-2">У вас пока нет заявок</h3>
                    <p className="text-muted-foreground mb-6">
                      Записывайтесь на просмотр объектов
                    </p>
                    <Link to="/catalog">
                      <Button variant="primary">Найти недвижимость</Button>
                    </Link>
                  </div>
                )}
              </>
            )}

            {/* Settings Tab */}
            {activeTab === "settings" && (
              <>
                <div className="flex items-center justify-between">
                  <h1 className="text-2xl font-display font-bold text-foreground">Настройки</h1>
                </div>

                {/* Notifications */}
                <div className="bg-card rounded-2xl border border-border p-6 shadow-card">
                  <div className="flex items-center gap-3 mb-6">
                    <div className="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">
                      <Bell className="w-5 h-5 text-primary" />
                    </div>
                    <h2 className="text-lg font-semibold text-foreground">Уведомления</h2>
                  </div>

                  <div className="space-y-4">
                    {[
                      { key: "emailNotifications", label: "Email уведомления", desc: "Получать новости и обновления на email" },
                      { key: "pushNotifications", label: "Push уведомления", desc: "Уведомления в браузере" },
                      { key: "newListingsAlerts", label: "Новые объекты", desc: "Оповещения о новых объектах по вашим поискам" },
                      { key: "priceDropAlerts", label: "Снижение цен", desc: "Оповещения о снижении цен в избранном" },
                    ].map((item) => (
                      <div key={item.key} className="flex items-center justify-between py-2">
                        <div>
                          <p className="font-medium text-foreground">{item.label}</p>
                          <p className="text-sm text-muted-foreground">{item.desc}</p>
                        </div>
                        <Switch
                          checked={notifications[item.key as keyof NotificationSettings]}
                          onCheckedChange={(checked) => 
                            handleNotificationChange(item.key as keyof NotificationSettings, checked)
                          }
                        />
                      </div>
                    ))}
                  </div>
                </div>

                {/* Security */}
                <div className="bg-card rounded-2xl border border-border p-6 shadow-card">
                  <div className="flex items-center gap-3 mb-6">
                    <div className="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">
                      <Shield className="w-5 h-5 text-primary" />
                    </div>
                    <h2 className="text-lg font-semibold text-foreground">Безопасность</h2>
                  </div>

                  <Button variant="secondary">
                    Изменить пароль
                  </Button>
                </div>

                {/* Danger Zone */}
                <div className="bg-card rounded-2xl border border-destructive/30 p-6 shadow-card">
                  <div className="flex items-center gap-3 mb-4">
                    <div className="w-10 h-10 rounded-xl bg-destructive/10 flex items-center justify-center">
                      <AlertTriangle className="w-5 h-5 text-destructive" />
                    </div>
                    <h2 className="text-lg font-semibold text-foreground">Опасная зона</h2>
                  </div>

                  <p className="text-sm text-muted-foreground mb-4">
                    Удаление аккаунта необратимо. Все ваши данные, избранное и история будут удалены навсегда.
                  </p>

                  <Button 
                    variant="danger"
                    onClick={() => setShowDeleteModal(true)}
                    leftIcon={<Trash2 className="w-4 h-4" />}
                  >
                    Удалить аккаунт
                  </Button>
                </div>
              </>
            )}
          </div>
        </div>
      </main>

      {/* Delete Account Modal */}
      <Dialog open={showDeleteModal} onOpenChange={setShowDeleteModal}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle className="text-destructive">Удаление аккаунта</DialogTitle>
            <DialogDescription>
              Это действие необратимо. Все ваши данные будут удалены навсегда.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4 pt-4">
            <p className="text-sm text-muted-foreground">
              Для подтверждения введите слово <strong className="text-foreground">удалить</strong>:
            </p>
            <Input
              value={deleteConfirmation}
              onChange={(e) => setDeleteConfirmation(e.target.value)}
              placeholder="удалить"
            />
            <div className="flex justify-end gap-2">
              <Button variant="secondary" onClick={() => setShowDeleteModal(false)}>
                Отмена
              </Button>
              <Button variant="danger" onClick={handleDeleteAccount}>
                Удалить аккаунт
              </Button>
            </div>
          </div>
        </DialogContent>
      </Dialog>

      <Footer />
    </div>
  );
};

export default Profile;

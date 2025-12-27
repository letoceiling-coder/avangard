import { Link, useLocation } from "react-router-dom";
import { Home, Search, Building2, Heart, User } from "lucide-react";
import { useFavorites } from "@/hooks/useFavorites";
import { useAuth } from "@/hooks/useAuth";

const MobileBottomNav = () => {
  const location = useLocation();
  const { favorites } = useFavorites();
  const { isAuthenticated } = useAuth();
  const favoritesCount = favorites.length;

  const isActive = (path: string) => {
    if (path === "/") return location.pathname === "/";
    return location.pathname.startsWith(path);
  };

  const navItems = [
    { path: "/", label: "Главная", icon: Home },
    { path: "/catalog", label: "Каталог", icon: Search },
    { path: "/residential-complex", label: "ЖК", icon: Building2 },
    { path: "/favorites", label: "Избранное", icon: Heart, badge: favoritesCount },
    { path: isAuthenticated ? "/profile" : "/contacts", label: isAuthenticated ? "Профиль" : "Контакты", icon: User },
  ];

  return (
    <>
      {/* Spacer to prevent content from going under fixed bottom nav */}
      <div className="h-[68px] md:hidden" />
      
      {/* Bottom Navigation Bar */}
      <nav className="fixed bottom-0 left-0 right-0 z-50 md:hidden bg-card/95 backdrop-blur-md border-t border-border shadow-[0_-4px_20px_rgba(0,0,0,0.08)]">
        <div className="flex items-center justify-around h-[68px] px-2 safe-area-pb">
          {navItems.map((item) => {
            const active = isActive(item.path);
            return (
              <Link
                key={item.path}
                to={item.path}
                className={`flex flex-col items-center justify-center gap-1 min-w-[64px] py-2 px-3 rounded-xl transition-all duration-200 ${
                  active 
                    ? "text-primary" 
                    : "text-muted-foreground hover:text-foreground"
                }`}
              >
                <div className="relative">
                  <div className={`p-1.5 rounded-xl transition-colors ${active ? "bg-primary/10" : ""}`}>
                    <item.icon 
                      className={`w-5 h-5 transition-all ${
                        active ? "text-primary" : ""
                      } ${item.path === "/favorites" && favoritesCount > 0 ? "fill-current" : ""}`} 
                    />
                  </div>
                  {/* Badge for favorites */}
                  {item.badge && item.badge > 0 && (
                    <span className="absolute -top-1 -right-1 min-w-[18px] h-[18px] flex items-center justify-center rounded-full bg-destructive text-destructive-foreground text-[10px] font-bold px-1">
                      {item.badge > 99 ? "99+" : item.badge}
                    </span>
                  )}
                </div>
                <span className={`text-[10px] font-medium leading-none ${active ? "text-primary" : ""}`}>
                  {item.label}
                </span>
              </Link>
            );
          })}
        </div>
      </nav>
    </>
  );
};

export default MobileBottomNav;

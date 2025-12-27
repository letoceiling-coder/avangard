import { useState, useEffect } from "react";
import { Link, useLocation, useNavigate } from "react-router-dom";
import { Heart, User, Menu, MapPin, ChevronDown, Search, Building2 } from "lucide-react";
import { Button } from "./ui/button";
import LiveGridLogo from "./LiveGridLogo";
import AuthModal from "./AuthModal";
import { useAuth } from "@/hooks/useAuth";
import { useFavorites } from "@/hooks/useFavorites";
import { useRegion } from "@/hooks/useRegion";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "./ui/dropdown-menu";
import { Sheet, SheetContent, SheetTrigger } from "./ui/sheet";

const Header = () => {
  const location = useLocation();
  const navigate = useNavigate();
  const { user, isAuthenticated } = useAuth();
  const { favorites } = useFavorites();
  const { selectedRegion, setSelectedRegion, regionName } = useRegion();
  
  // Regions list for dropdown
  const regions = [
    { id: "belgorod", name: "Белгород" },
    { id: "kursk", name: "Курск" },
    { id: "voronezh", name: "Воронеж" },
  ];
  const [authModalOpen, setAuthModalOpen] = useState(false);
  const [isScrolled, setIsScrolled] = useState(false);
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [scrollY, setScrollY] = useState(0);

  const favoritesCount = favorites.length;

  // Handle scroll for animations
  useEffect(() => {
    const handleScroll = () => {
      const currentScrollY = window.scrollY;
      setScrollY(currentScrollY);
      setIsScrolled(currentScrollY > 10);
    };
    window.addEventListener("scroll", handleScroll);
    return () => window.removeEventListener("scroll", handleScroll);
  }, []);

  // Calculate dynamic values based on scroll
  const scrollProgress = Math.min(scrollY / 100, 1); // 0 to 1 over 100px scroll
  const headerPadding = 16 - (scrollProgress * 8); // py-4 (16px) to py-2 (8px)
  const logoScale = 1 - (scrollProgress * 0.15); // 100% to 85%

  // Close mobile menu on route change
  useEffect(() => {
    setMobileMenuOpen(false);
  }, [location.pathname]);

  // Menu items
  const menuItems = [
    { label: "Покупка", href: "/catalog?tab=buy" },
    { label: "Продажа", href: "/catalog?tab=sell" },
    { label: "Аренда", href: "/catalog?tab=rent" },
    { label: "Новостройки", href: "/residential-complex" },
    { label: "Дома", href: "/catalog?type=house" },
    { label: "Участки", href: "/catalog?type=land" },
    { label: "Коммерция", href: "/catalog?type=commercial" },
    { label: "Ипотека", href: "/mortgage" },
    { label: "Блог", href: "/blog" },
    { label: "Контакты", href: "/contacts" },
  ];

  return (
    <>
      <header
        className="sticky top-0 z-50 bg-white/70 backdrop-blur shadow-sm transition-all duration-300"
      >
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3">
          <div className="flex justify-between items-center gap-3 sm:gap-4">
            {/* Left: Logo + Region */}
            <div className="flex items-center gap-3 flex-shrink-0">
              <Link to="/" className="flex-shrink-0">
                <LiveGridLogo size="md" />
              </Link>
              
              {/* Region Selector - Less prominent */}
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <button className="flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground transition-colors">
                    <MapPin className="w-3.5 h-3.5" />
                    <span>{regionName}</span>
                    <ChevronDown className="w-3 h-3" />
                  </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent 
                  align="start" 
                  className="min-w-[160px] w-fit py-1 px-2 rounded-xl shadow-md bg-white border space-y-1 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 origin-top"
                >
                  {regions.map((region) => {
                    const isSelected = selectedRegion === region.id;
                    return (
                      <DropdownMenuItem
                        key={region.id}
                        onClick={() => setSelectedRegion(region.id as "belgorod" | "kursk" | "voronezh")}
                        className={`flex items-center gap-2 text-sm px-3 py-2 rounded-lg transition hover:bg-muted/10 cursor-pointer ${
                          isSelected 
                            ? "bg-primary text-white font-medium hover:bg-primary/90" 
                            : ""
                        }`}
                      >
                        <MapPin className={`w-4 h-4 ${isSelected ? "text-white" : "text-muted"}`} />
                        {region.name}
                      </DropdownMenuItem>
                    );
                  })}
                </DropdownMenuContent>
              </DropdownMenu>
            </div>

            {/* Center: Menu Items */}
            <nav className="hidden lg:flex items-center gap-4 flex-1 justify-center overflow-x-auto scrollbar-hide">
              {menuItems.map((item) => {
                const isActive = location.pathname === item.href.split('?')[0] || 
                                 location.search.includes(item.href.split('?')[1]?.split('=')[1] || '');
                return (
                  <Link
                    key={item.href}
                    to={item.href}
                    className={`text-sm font-medium whitespace-nowrap transition-colors ${
                      isActive ? "text-primary" : "text-muted-foreground hover:text-foreground"
                    }`}
                  >
                    {item.label}
                  </Link>
                );
              })}
            </nav>

            {/* Right Actions */}
            <div className="flex items-center gap-2">
              {/* Favorites Icon */}
              <Link to="/favorites" className="relative">
                <Button variant="ghost" size="icon" className="relative h-9 w-9">
                  <Heart
                    className={`w-5 h-5 ${favoritesCount > 0 ? "fill-primary text-primary" : "text-muted-foreground"}`}
                  />
                  {favoritesCount > 0 && (
                    <span className="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] flex items-center justify-center rounded-full bg-destructive text-destructive-foreground text-[10px] font-semibold px-0.5">
                      {favoritesCount > 99 ? "99+" : favoritesCount}
                    </span>
                  )}
                </Button>
              </Link>

              {/* Login Button */}
              {isAuthenticated ? (
                <Button
                  variant="secondary"
                  size="sm"
                  onClick={() => navigate("/profile")}
                  className="gap-2"
                >
                  <User className="w-4 h-4" />
                  <span className="hidden sm:inline">{user?.name || "Профиль"}</span>
                </Button>
              ) : (
                <Button variant="primary" size="sm" onClick={() => setAuthModalOpen(true)}>
                  Войти
                </Button>
              )}

              {/* Mobile Menu Burger */}
              <Sheet open={mobileMenuOpen} onOpenChange={setMobileMenuOpen}>
                <SheetTrigger asChild>
                  <Button variant="ghost" size="icon" className="lg:hidden">
                    <Menu className="w-5 h-5" />
                  </Button>
                </SheetTrigger>
                <SheetContent side="right" className="w-[300px] sm:w-[400px]">
                  <div className="flex flex-col gap-4 mt-4">
                    <Link to="/" className="mb-4" onClick={() => setMobileMenuOpen(false)}>
                      <LiveGridLogo size="md" />
                    </Link>

                    <div className="flex flex-col gap-2">
                      <Link
                        to="/catalog"
                        className="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-accent"
                        onClick={() => setMobileMenuOpen(false)}
                      >
                        <Search className="w-4 h-4" />
                        Каталог
                      </Link>
                      <Link
                        to="/residential-complex"
                        className="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-accent"
                        onClick={() => setMobileMenuOpen(false)}
                      >
                        <Building2 className="w-4 h-4" />
                        Жилые комплексы
                      </Link>
                      <Link
                        to="/developers"
                        className="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-accent"
                        onClick={() => setMobileMenuOpen(false)}
                      >
                        <Building2 className="w-4 h-4" />
                        Застройщики
                      </Link>
                      <Link
                        to="/contacts"
                        className="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-accent"
                        onClick={() => setMobileMenuOpen(false)}
                      >
                        Контакты
                      </Link>
                      <Link
                        to="/about"
                        className="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-accent"
                        onClick={() => setMobileMenuOpen(false)}
                      >
                        О нас
                      </Link>
                    </div>

                    <div className="border-t pt-4 mt-4">
                      <Link
                        to="/favorites"
                        className="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-accent"
                        onClick={() => setMobileMenuOpen(false)}
                      >
                        <Heart className={`w-4 h-4 ${favoritesCount > 0 ? "fill-primary text-primary" : ""}`} />
                        Избранное
                        {favoritesCount > 0 && (
                          <span className="ml-auto bg-primary text-primary-foreground rounded-full px-2 py-0.5 text-xs">
                            {favoritesCount}
                          </span>
                        )}
                      </Link>
                    </div>
                  </div>
                </SheetContent>
              </Sheet>
            </div>
          </div>
        </div>
      </header>

      {/* Auth Modal */}
      <AuthModal open={authModalOpen} onOpenChange={setAuthModalOpen} />
    </>
  );
};

export default Header;

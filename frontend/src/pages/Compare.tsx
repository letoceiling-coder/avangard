import { Link } from "react-router-dom";
import Header from "@/components/Header";
import Footer from "@/components/Footer";
import { Button } from "@/components/ui/button";
import { useComparison } from "@/hooks/useComparison";
import { ArrowLeft, X, Scale, Heart, ExternalLink } from "lucide-react";
import { useFavorites } from "@/hooks/useFavorites";
import { toast } from "sonner";

const Compare = () => {
  const { compareItems, removeFromCompare, clearCompare } = useComparison();
  const { isFavorite, addToFavorites, removeFromFavorites } = useFavorites();

  const toggleFavorite = (property: typeof compareItems[0]) => {
    if (isFavorite(property.id)) {
      removeFromFavorites(property.id);
      toast.success("Удалено из избранного");
    } else {
      addToFavorites(property);
      toast.success("Добавлено в избранное");
    }
  };

  const characteristics = [
    { key: "price", label: "Цена", format: (v: number) => `${v.toLocaleString("ru-RU")} ₽` },
    { key: "pricePerMeter", label: "Цена за м²", format: (v: number) => v ? `${v.toLocaleString("ru-RU")} ₽` : "—" },
    { key: "rooms", label: "Комнат", format: (v: number) => v },
    { key: "area", label: "Площадь", format: (v: number) => `${v} м²` },
    { key: "floor", label: "Этаж", format: (v: number) => v },
    { key: "year", label: "Год сдачи", format: (v: number) => v || "—" },
    { key: "type", label: "Тип", format: (v: string) => v },
    { key: "address", label: "Адрес", format: (v: string) => v },
  ];

  // Find best values for highlighting
  const getBestValue = (key: string): string | null => {
    if (compareItems.length < 2) return null;
    
    const values = compareItems.map((item) => ({
      id: item.id,
      value: item[key as keyof typeof item],
    }));

    if (key === "price" || key === "pricePerMeter") {
      const validValues = values.filter((v) => v.value && typeof v.value === "number");
      if (validValues.length < 2) return null;
      const min = Math.min(...validValues.map((v) => v.value as number));
      return validValues.find((v) => v.value === min)?.id || null;
    }

    if (key === "area") {
      const max = Math.max(...values.map((v) => v.value as number));
      return values.find((v) => v.value === max)?.id || null;
    }

    return null;
  };

  if (compareItems.length === 0) {
    return (
      <div className="min-h-screen bg-background">
        <Header />
        <main className="container mx-auto px-4 py-16">
          <div className="text-center">
            <div className="w-20 h-20 rounded-full bg-primary/10 flex items-center justify-center mx-auto mb-6">
              <Scale className="w-10 h-10 text-primary" />
            </div>
            <h1 className="text-2xl font-display font-bold text-foreground mb-3">
              Сравнение пустое
            </h1>
            <p className="text-muted-foreground mb-6">
              Добавьте объекты для сравнения из каталога или карточки объекта
            </p>
            <Link to="/catalog">
              <Button className="bg-primary hover:bg-primary/90 text-primary-foreground">
                Перейти в каталог
              </Button>
            </Link>
          </div>
        </main>
        <Footer />
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-background">
      <Header />

      <main className="container mx-auto px-4 py-6 md:py-8">
        {/* Header */}
        <div className="flex items-center justify-between mb-8">
          <div className="flex items-center gap-4">
            <Link
              to="/catalog"
              className="flex items-center gap-2 text-muted-foreground hover:text-primary transition-colors"
            >
              <ArrowLeft className="w-5 h-5" />
              <span className="hidden sm:inline">Назад в каталог</span>
            </Link>
          </div>
          <Button
            variant="outline"
            size="sm"
            onClick={clearCompare}
            className="text-muted-foreground hover:text-foreground"
          >
            Очистить сравнение
          </Button>
        </div>

        <h1 className="text-2xl md:text-3xl font-display font-bold text-foreground mb-8">
          Сравнение объектов
        </h1>

        {/* Comparison Table */}
        <div className="overflow-x-auto">
          <div className="min-w-[600px]">
            {/* Property Headers */}
            <div className="grid gap-4 mb-6" style={{ gridTemplateColumns: `200px repeat(${compareItems.length}, 1fr)` }}>
              <div /> {/* Empty cell for labels column */}
              {compareItems.map((property) => (
                <div
                  key={property.id}
                  className="bg-card rounded-2xl border border-border p-4 shadow-card relative group"
                >
                  <button
                    onClick={() => removeFromCompare(property.id)}
                    className="absolute top-3 right-3 p-1.5 rounded-full bg-muted hover:bg-destructive hover:text-white transition-colors opacity-0 group-hover:opacity-100"
                  >
                    <X className="w-4 h-4" />
                  </button>
                  
                  <Link to={`/property/${property.id}`}>
                    <img
                      src={property.image}
                      alt={property.title}
                      className="w-full h-40 object-cover rounded-xl mb-4"
                    />
                  </Link>
                  
                  <Link 
                    to={`/property/${property.id}`}
                    className="text-lg font-semibold text-foreground hover:text-primary transition-colors line-clamp-2"
                  >
                    {property.title}
                  </Link>
                  
                  <div className="flex items-center gap-2 mt-3">
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => toggleFavorite(property)}
                      className={isFavorite(property.id) ? "border-primary text-primary" : ""}
                    >
                      <Heart className={`w-4 h-4 mr-1 ${isFavorite(property.id) ? "fill-primary" : ""}`} />
                      {isFavorite(property.id) ? "В избранном" : "В избранное"}
                    </Button>
                    <Link to={`/property/${property.id}`}>
                      <Button variant="outline" size="sm">
                        <ExternalLink className="w-4 h-4" />
                      </Button>
                    </Link>
                  </div>
                </div>
              ))}
            </div>

            {/* Characteristics Rows */}
            <div className="bg-card rounded-2xl border border-border overflow-hidden shadow-card">
              {characteristics.map((char, index) => {
                const bestId = getBestValue(char.key);
                return (
                  <div
                    key={char.key}
                    className={`grid gap-4 ${index !== characteristics.length - 1 ? "border-b border-border" : ""}`}
                    style={{ gridTemplateColumns: `200px repeat(${compareItems.length}, 1fr)` }}
                  >
                    <div className="p-4 bg-muted/30 font-medium text-foreground">
                      {char.label}
                    </div>
                    {compareItems.map((property) => {
                      const value = property[char.key as keyof typeof property];
                      const isBest = bestId === property.id;
                      return (
                        <div
                          key={property.id}
                          className={`p-4 ${isBest ? "bg-primary/5" : ""}`}
                        >
                          <span className={`${isBest ? "text-primary font-semibold" : "text-foreground"}`}>
                            {char.format(value as never)}
                          </span>
                          {isBest && char.key === "price" && (
                            <span className="ml-2 text-xs text-primary bg-primary/10 px-2 py-0.5 rounded-full">
                              Лучшая цена
                            </span>
                          )}
                          {isBest && char.key === "area" && (
                            <span className="ml-2 text-xs text-primary bg-primary/10 px-2 py-0.5 rounded-full">
                              Больше площадь
                            </span>
                          )}
                        </div>
                      );
                    })}
                  </div>
                );
              })}
            </div>
          </div>
        </div>

        {/* Add More CTA */}
        {compareItems.length < 3 && (
          <div className="mt-8 text-center">
            <p className="text-muted-foreground mb-4">
              Можно добавить ещё {3 - compareItems.length} {compareItems.length === 2 ? "объект" : "объекта"}
            </p>
            <Link to="/catalog">
              <Button variant="outline" className="border-primary text-primary hover:bg-primary/5">
                Добавить объекты
              </Button>
            </Link>
          </div>
        )}
      </main>

      <Footer />
    </div>
  );
};

export default Compare;

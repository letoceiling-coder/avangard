import { Link } from "react-router-dom";
import { X, Scale, Trash2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { useComparison } from "@/hooks/useComparison";

const CompareBar = () => {
  const { compareItems, removeFromCompare, clearCompare } = useComparison();

  if (compareItems.length === 0) return null;

  return (
    <div className="fixed bottom-0 left-0 right-0 z-40 bg-card border-t border-border shadow-elevated animate-in slide-in-from-bottom-4">
      <div className="container mx-auto px-4 py-4">
        <div className="flex items-center justify-between gap-4">
          <div className="flex items-center gap-2">
            <Scale className="w-5 h-5 text-primary" />
            <span className="font-medium text-foreground">
              Сравнение: {compareItems.length}/3
            </span>
          </div>

          <div className="flex-1 flex items-center gap-3 overflow-x-auto">
            {compareItems.map((item) => (
              <div
                key={item.id}
                className="flex items-center gap-2 bg-muted rounded-lg p-2 pr-3 flex-shrink-0"
              >
                <img
                  src={item.image}
                  alt={item.title}
                  className="w-12 h-12 rounded-md object-cover"
                />
                <div className="min-w-0">
                  <p className="text-sm font-medium text-foreground truncate max-w-[120px]">
                    {item.rooms}-комн., {item.area} м²
                  </p>
                  <p className="text-xs text-muted-foreground">
                    {(item.price / 1000000).toFixed(1)} млн ₽
                  </p>
                </div>
                <button
                  onClick={() => removeFromCompare(item.id)}
                  className="ml-1 p-1 rounded-full hover:bg-background text-muted-foreground hover:text-foreground transition-colors"
                >
                  <X className="w-4 h-4" />
                </button>
              </div>
            ))}

            {/* Empty slots */}
            {Array.from({ length: 3 - compareItems.length }).map((_, i) => (
              <div
                key={`empty-${i}`}
                className="w-[160px] h-16 border-2 border-dashed border-border rounded-lg flex items-center justify-center flex-shrink-0"
              >
                <span className="text-xs text-muted-foreground">Добавьте объект</span>
              </div>
            ))}
          </div>

          <div className="flex items-center gap-2">
            <Button
              variant="outline"
              size="sm"
              onClick={clearCompare}
              className="text-muted-foreground hover:text-foreground"
            >
              <Trash2 className="w-4 h-4 mr-1" />
              Очистить
            </Button>
            <Link to="/compare">
              <Button 
                size="sm" 
                className="bg-primary hover:bg-primary/90 text-primary-foreground"
                disabled={compareItems.length < 2}
              >
                Сравнить
              </Button>
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
};

export default CompareBar;

import { useState, useEffect } from "react";
import { Link } from "react-router-dom";
import Header from "@/components/Header";
import Footer from "@/components/Footer";
import PropertyCard from "@/components/PropertyCard";
import { useFavorites, Property } from "@/hooks/useFavorites";
import { useComparison } from "@/hooks/useComparison";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Checkbox } from "@/components/ui/checkbox";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog";
import { 
  Heart, Scale, FileDown, Trash2, Grid3X3, List, 
  MapPin, ExternalLink, StickyNote, X, Check, ArrowRight
} from "lucide-react";
import { toast } from "sonner";
import { cn } from "@/lib/utils";

// HTML escape function to prevent XSS
const escapeHtml = (text: string): string => {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
};

interface FavoriteNote {
  id: string;
  note: string;
}

const Favorites = () => {
  const { favorites, removeFromFavorites, clearFavorites } = useFavorites();
  const { addToCompare, isInCompare, canAddMore } = useComparison();
  const [viewMode, setViewMode] = useState<"grid" | "list">("grid");
  const [selectedForCompare, setSelectedForCompare] = useState<string[]>([]);
  const [notes, setNotes] = useState<FavoriteNote[]>([]);
  const [editingNote, setEditingNote] = useState<string | null>(null);
  const [noteText, setNoteText] = useState("");
  const [showNoteModal, setShowNoteModal] = useState(false);
  const [currentPropertyId, setCurrentPropertyId] = useState<string | null>(null);

  // Load notes from localStorage
  useEffect(() => {
    const savedNotes = localStorage.getItem("favoriteNotes");
    if (savedNotes) {
      setNotes(JSON.parse(savedNotes));
    }
  }, []);

  // Save notes to localStorage
  const saveNotes = (newNotes: FavoriteNote[]) => {
    setNotes(newNotes);
    localStorage.setItem("favoriteNotes", JSON.stringify(newNotes));
  };

  const getNote = (id: string) => {
    return notes.find((n) => n.id === id)?.note || "";
  };

  const handleSaveNote = () => {
    if (!currentPropertyId) return;
    
    const existingIndex = notes.findIndex((n) => n.id === currentPropertyId);
    let newNotes: FavoriteNote[];
    
    if (noteText.trim()) {
      if (existingIndex >= 0) {
        newNotes = notes.map((n) => 
          n.id === currentPropertyId ? { ...n, note: noteText } : n
        );
      } else {
        newNotes = [...notes, { id: currentPropertyId, note: noteText }];
      }
    } else {
      newNotes = notes.filter((n) => n.id !== currentPropertyId);
    }
    
    saveNotes(newNotes);
    setShowNoteModal(false);
    setNoteText("");
    setCurrentPropertyId(null);
    toast.success("Заметка сохранена");
  };

  const openNoteModal = (propertyId: string) => {
    setCurrentPropertyId(propertyId);
    setNoteText(getNote(propertyId));
    setShowNoteModal(true);
  };

  const toggleCompareSelection = (id: string) => {
    if (selectedForCompare.includes(id)) {
      setSelectedForCompare(selectedForCompare.filter((s) => s !== id));
    } else if (selectedForCompare.length < 5) {
      setSelectedForCompare([...selectedForCompare, id]);
    } else {
      toast.error("Можно сравнить максимум 5 объектов");
    }
  };

  const handleAddToCompare = () => {
    const selectedItems = favorites.filter((f) => selectedForCompare.includes(f.id));
    selectedItems.forEach((item) => {
      if (!isInCompare(item.id) && canAddMore) {
        addToCompare({
          id: item.id,
          title: item.title,
          price: item.price,
          image: item.image,
          area: item.area,
          rooms: item.rooms,
          floor: item.floor,
          address: item.address,
          type: item.type,
        });
      }
    });
    toast.success(`Добавлено ${selectedItems.length} объектов к сравнению`);
    setSelectedForCompare([]);
  };

  const handleExportPDF = () => {
    // Create printable content with XSS protection
    const printContent = favorites.map((item) => {
      const note = getNote(item.id);
      return `
        <div style="page-break-inside: avoid; margin-bottom: 30px; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px;">
          <h2 style="margin: 0 0 10px; font-size: 18px; color: #1a1a1a;">${escapeHtml(item.title)}</h2>
          <p style="margin: 0 0 5px; color: #666;">${escapeHtml(item.address)}</p>
          <p style="margin: 0 0 10px; font-size: 20px; font-weight: bold; color: #4AB3FF;">
            ${item.price.toLocaleString("ru-RU")} ₽
          </p>
          <div style="display: flex; gap: 20px; color: #666; font-size: 14px;">
            <span>${item.rooms}-комн.</span>
            <span>${item.area} м²</span>
            <span>${item.floor} этаж</span>
          </div>
          ${note ? `<div style="margin-top: 15px; padding: 10px; background: #f9fafb; border-radius: 8px;">
            <strong style="color: #374151;">Заметка:</strong>
            <p style="margin: 5px 0 0; color: #4b5563;">${escapeHtml(note)}</p>
          </div>` : ""}
        </div>
      `;
    }).join("");

    const printWindow = window.open("", "_blank");
    if (printWindow) {
      printWindow.document.write(`
        <!DOCTYPE html>
        <html>
          <head>
            <title>Избранные объекты - LiveGrid</title>
            <style>
              body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; padding: 40px; }
              h1 { color: #0A2342; margin-bottom: 30px; }
              @media print { body { padding: 20px; } }
            </style>
          </head>
          <body>
            <h1>Избранные объекты</h1>
            <p style="color: #666; margin-bottom: 30px;">Дата: ${new Date().toLocaleDateString("ru-RU")}</p>
            ${printContent}
            <p style="margin-top: 40px; color: #999; font-size: 12px; text-align: center;">
              Сформировано на LiveGrid.ru
            </p>
          </body>
        </html>
      `);
      printWindow.document.close();
      printWindow.print();
    }
    toast.success("Подготовка PDF для печати...");
  };

  const formatPrice = (price: number) => {
    return price.toLocaleString("ru-RU") + " ₽";
  };

  const handleRemove = (id: string) => {
    removeFromFavorites(id);
    setSelectedForCompare(selectedForCompare.filter((s) => s !== id));
    // Remove note as well
    saveNotes(notes.filter((n) => n.id !== id));
    toast.success("Удалено из избранного");
  };

  return (
    <div className="min-h-screen bg-background">
      <Header />

      <main className="container mx-auto px-4 py-8">
        {/* Header */}
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
          <div>
            <h1 className="text-3xl md:text-4xl font-display font-bold text-foreground">
              Избранное
            </h1>
            <p className="text-muted-foreground mt-1">
              {favorites.length} {favorites.length === 1 ? "объект" : favorites.length < 5 ? "объекта" : "объектов"} сохранено
            </p>
          </div>

          {favorites.length > 0 && (
            <div className="flex flex-wrap items-center gap-2">
              {/* View Mode Toggle */}
              <div className="flex rounded-lg border border-border overflow-hidden">
                <button
                  onClick={() => setViewMode("grid")}
                  className={cn(
                    "p-2 transition-colors",
                    viewMode === "grid" ? "bg-primary text-primary-foreground" : "bg-card hover:bg-muted"
                  )}
                >
                  <Grid3X3 className="w-5 h-5" />
                </button>
                <button
                  onClick={() => setViewMode("list")}
                  className={cn(
                    "p-2 transition-colors",
                    viewMode === "list" ? "bg-primary text-primary-foreground" : "bg-card hover:bg-muted"
                  )}
                >
                  <List className="w-5 h-5" />
                </button>
              </div>

              <Button
                variant="secondary"
                size="sm"
                onClick={handleExportPDF}
                leftIcon={<FileDown className="w-4 h-4" />}
              >
                Скачать PDF
              </Button>

              {selectedForCompare.length >= 2 && (
                <Button
                  variant="primary"
                  size="sm"
                  onClick={handleAddToCompare}
                  leftIcon={<Scale className="w-4 h-4" />}
                >
                  Сравнить ({selectedForCompare.length})
                </Button>
              )}
            </div>
          )}
        </div>

        {favorites.length === 0 ? (
          /* Empty State */
          <div className="bg-card rounded-2xl border border-border p-12 text-center shadow-card">
            <div className="w-16 h-16 rounded-2xl bg-primary/10 flex items-center justify-center mx-auto mb-4">
              <Heart className="w-8 h-8 text-primary" />
            </div>
            <h2 className="text-xl font-display font-semibold text-foreground mb-2">
              Избранное пусто
            </h2>
            <p className="text-muted-foreground mb-6 max-w-md mx-auto">
              Добавляйте понравившиеся объекты, нажимая на сердечко в карточке объекта
            </p>
            <Link to="/catalog">
              <Button variant="primary" size="lg">
                Перейти в каталог
              </Button>
            </Link>
          </div>
        ) : (
          <>
            {/* Selection Hint */}
            {selectedForCompare.length > 0 && selectedForCompare.length < 2 && (
              <div className="bg-primary/5 border border-primary/20 rounded-xl p-4 mb-6 flex items-center gap-3">
                <Scale className="w-5 h-5 text-primary flex-shrink-0" />
                <p className="text-sm text-foreground">
                  Выберите ещё минимум {2 - selectedForCompare.length} объект для сравнения
                </p>
              </div>
            )}

            {/* Grid View */}
            {viewMode === "grid" && (
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {favorites.map((property) => (
                  <div key={property.id} className="relative group">
                    {/* Selection Checkbox */}
                    <div 
                      className="absolute top-3 left-3 z-10"
                      onClick={(e) => e.stopPropagation()}
                    >
                      <Checkbox
                        checked={selectedForCompare.includes(property.id)}
                        onCheckedChange={() => toggleCompareSelection(property.id)}
                        className="w-5 h-5 bg-white/90 backdrop-blur-sm border-2"
                      />
                    </div>

                    {/* Note Indicator */}
                    {getNote(property.id) && (
                      <div className="absolute top-3 left-12 z-10">
                        <div className="w-6 h-6 rounded-full bg-amber-500 flex items-center justify-center">
                          <StickyNote className="w-3.5 h-3.5 text-white" />
                        </div>
                      </div>
                    )}

                    <PropertyCard property={property} />

                    {/* Actions Overlay */}
                    <div className="absolute bottom-4 right-4 flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                      <button
                        onClick={() => openNoteModal(property.id)}
                        className="w-8 h-8 rounded-full bg-white shadow-md flex items-center justify-center hover:bg-amber-50 transition-colors"
                        title="Заметка"
                      >
                        <StickyNote className="w-4 h-4 text-amber-600" />
                      </button>
                    </div>
                  </div>
                ))}
              </div>
            )}

            {/* List View */}
            {viewMode === "list" && (
              <div className="space-y-4">
                {favorites.map((property) => (
                  <div
                    key={property.id}
                    className={cn(
                      "bg-card rounded-2xl border shadow-card overflow-hidden transition-all",
                      selectedForCompare.includes(property.id) ? "border-primary ring-1 ring-primary" : "border-border"
                    )}
                  >
                    <div className="flex flex-col sm:flex-row">
                      {/* Selection & Image */}
                      <div className="relative sm:w-48 h-40 sm:h-auto flex-shrink-0">
                        <div 
                          className="absolute top-3 left-3 z-10"
                          onClick={(e) => e.stopPropagation()}
                        >
                          <Checkbox
                            checked={selectedForCompare.includes(property.id)}
                            onCheckedChange={() => toggleCompareSelection(property.id)}
                            className="w-5 h-5 bg-white/90 backdrop-blur-sm border-2"
                          />
                        </div>
                        <Link to={`/property/${property.id}`}>
                          <img
                            src={property.image}
                            alt={property.title}
                            className="w-full h-full object-cover"
                          />
                        </Link>
                      </div>

                      {/* Content */}
                      <div className="flex-1 p-4 sm:p-5 flex flex-col sm:flex-row gap-4">
                        {/* Info */}
                        <div className="flex-1 min-w-0">
                          <Link 
                            to={`/property/${property.id}`}
                            className="text-lg font-semibold text-foreground hover:text-primary transition-colors line-clamp-1"
                          >
                            {property.title}
                          </Link>
                          <div className="flex items-center gap-2 text-sm text-muted-foreground mt-1">
                            <MapPin className="w-4 h-4 flex-shrink-0" />
                            <span className="truncate">{property.address}</span>
                          </div>
                          <p className="text-xl font-bold text-primary mt-2">
                            {formatPrice(property.price)}
                          </p>
                          <div className="flex flex-wrap gap-3 text-sm text-muted-foreground mt-2">
                            <span>{property.rooms}-комн.</span>
                            <span>{property.area} м²</span>
                            <span>{property.floor} этаж</span>
                          </div>

                          {/* Note Display */}
                          {getNote(property.id) && (
                            <div className="mt-3 p-2 bg-amber-50 rounded-lg border border-amber-100">
                              <p className="text-sm text-amber-800 line-clamp-2">
                                <StickyNote className="w-3.5 h-3.5 inline mr-1" />
                                {getNote(property.id)}
                              </p>
                            </div>
                          )}
                        </div>

                        {/* Actions */}
                        <div className="flex sm:flex-col gap-2 sm:items-end justify-between sm:justify-start">
                          <div className="flex gap-2">
                            <button
                              onClick={() => openNoteModal(property.id)}
                              className={cn(
                                "w-9 h-9 rounded-lg flex items-center justify-center transition-colors",
                                getNote(property.id) 
                                  ? "bg-amber-100 text-amber-600" 
                                  : "bg-muted text-muted-foreground hover:text-amber-600"
                              )}
                              title="Заметка"
                            >
                              <StickyNote className="w-4 h-4" />
                            </button>
                            <Link to={`/property/${property.id}`}>
                              <button
                                className="w-9 h-9 rounded-lg bg-muted flex items-center justify-center text-muted-foreground hover:text-primary transition-colors"
                                title="Открыть"
                              >
                                <ExternalLink className="w-4 h-4" />
                              </button>
                            </Link>
                            <button
                              onClick={() => handleRemove(property.id)}
                              className="w-9 h-9 rounded-lg bg-muted flex items-center justify-center text-muted-foreground hover:text-destructive hover:bg-destructive/10 transition-colors"
                              title="Удалить"
                            >
                              <Trash2 className="w-4 h-4" />
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}

            {/* Bottom Actions */}
            <div className="mt-8 flex flex-col sm:flex-row items-center justify-between gap-4 pt-6 border-t border-border">
              <Link to="/compare" className="text-primary font-medium hover:underline flex items-center gap-1">
                Перейти к сравнению
                <ArrowRight className="w-4 h-4" />
              </Link>
              
              <Button
                variant="ghost"
                size="sm"
                onClick={() => {
                  clearFavorites();
                  setNotes([]);
                  localStorage.removeItem("favoriteNotes");
                  toast.success("Избранное очищено");
                }}
                className="text-destructive hover:text-destructive hover:bg-destructive/10"
                leftIcon={<Trash2 className="w-4 h-4" />}
              >
                Очистить всё
              </Button>
            </div>
          </>
        )}
      </main>

      {/* Note Modal */}
      <Dialog open={showNoteModal} onOpenChange={setShowNoteModal}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Заметка к объекту</DialogTitle>
            <DialogDescription>
              Добавьте личную заметку, которая поможет вам при выборе
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4 pt-4">
            <Textarea
              placeholder="Например: хороший вид из окна, рядом школа..."
              value={noteText}
              onChange={(e) => setNoteText(e.target.value)}
              rows={4}
            />
            <div className="flex justify-end gap-2">
              <Button variant="secondary" onClick={() => setShowNoteModal(false)}>
                Отмена
              </Button>
              <Button variant="primary" onClick={handleSaveNote}>
                Сохранить
              </Button>
            </div>
          </div>
        </DialogContent>
      </Dialog>

      <Footer />
    </div>
  );
};

export default Favorites;

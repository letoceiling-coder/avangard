import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { MapPin, Check } from "lucide-react";

interface City {
  id: string;
  name: string;
  region: string;
  isNew?: boolean;
}

const cities: City[] = [
  { id: "belgorod", name: "Белгород", region: "belgorod" },
  { id: "krasnodar", name: "Краснодарский край, Сочи, Республика Адыгея", region: "krasnodar" },
  { id: "rostov", name: "Ростов-на-Дону", region: "rostov" },
  { id: "moscow", name: "Москва", region: "moscow" },
  { id: "spb", name: "Санкт-Петербург", region: "spb" },
  { id: "crimea", name: "Крым", region: "crimea", isNew: true },
  { id: "kazan", name: "Казань", region: "kazan" },
];

const CityDetectionModal = () => {
  const [isOpen, setIsOpen] = useState(false);
  const [detectedCity, setDetectedCity] = useState<City | null>(null);
  const [selectedCity, setSelectedCity] = useState<string | null>(null);
  const navigate = useNavigate();

  useEffect(() => {
    const savedRegion = localStorage.getItem("selectedRegion");
    const cityConfirmed = localStorage.getItem("city_confirmed");
    
    // Only show if no region is saved or not confirmed
    if (!savedRegion || !cityConfirmed) {
      // Simulate city detection (in real app would use geolocation API)
      const timer = setTimeout(() => {
        // Default to Belgorod for demo
        setDetectedCity(cities[0]);
        setSelectedCity(cities[0].id);
        setIsOpen(true);
      }, 500);
      return () => clearTimeout(timer);
    }
  }, []);

  const handleConfirm = () => {
    if (selectedCity) {
      const city = cities.find(c => c.id === selectedCity);
      if (city) {
        localStorage.setItem("selectedRegion", city.region);
        localStorage.setItem("selectedCity", city.name);
        localStorage.setItem("city_confirmed", "true");
        setIsOpen(false);
      }
    }
  };

  const handleSelectCity = (cityId: string) => {
    setSelectedCity(cityId);
  };

  const handleChangeCity = () => {
    setIsOpen(false);
    navigate("/region-select");
  };

  return (
    <Dialog open={isOpen} onOpenChange={setIsOpen}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2 text-xl">
            <MapPin className="w-5 h-5 text-primary" />
            Выберите город
          </DialogTitle>
        </DialogHeader>

        <div className="py-4">
          {/* Detected city info */}
          {detectedCity && (
            <p className="text-sm text-muted-foreground mb-4">
              Мы определили ваше местоположение как <span className="font-medium text-foreground">{detectedCity.name}</span>. 
              Подтвердите или выберите другой город.
            </p>
          )}

          {/* City list */}
          <div className="space-y-1 max-h-[300px] overflow-y-auto">
            {cities.map((city) => (
              <button
                key={city.id}
                onClick={() => handleSelectCity(city.id)}
                className={`w-full flex items-center justify-between px-4 py-3 rounded-xl text-left transition-all ${
                  selectedCity === city.id
                    ? "bg-primary text-primary-foreground"
                    : "hover:bg-muted"
                }`}
              >
                <span className="flex items-center gap-2">
                  {city.name}
                  {city.isNew && (
                    <span className="px-2 py-0.5 text-xs font-medium bg-green-500 text-white rounded-full">
                      New
                    </span>
                  )}
                </span>
                {selectedCity === city.id && (
                  <Check className="w-5 h-5" />
                )}
              </button>
            ))}
          </div>
        </div>

        {/* Actions */}
        <div className="flex flex-col sm:flex-row gap-3">
          <Button
            variant="outline"
            onClick={handleChangeCity}
            className="flex-1"
          >
            Выбрать другой регион
          </Button>
          <Button
            variant="primary"
            onClick={handleConfirm}
            className="flex-1"
            disabled={!selectedCity}
          >
            Подтвердить
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
};

export default CityDetectionModal;

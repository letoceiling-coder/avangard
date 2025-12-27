import { Link, useSearchParams } from "react-router-dom";
import { Building2, Home, Store, MapPin, Landmark } from "lucide-react";

const propertyTypes = [
  { id: "apartment", label: "Квартиры", icon: Building2, count: "2 450" },
  { id: "room", label: "Комнаты", icon: Home, count: "180" },
  { id: "house", label: "Дома", icon: Home, count: "320" },
  { id: "land", label: "Участки", icon: MapPin, count: "95" },
  { id: "commercial", label: "Коммерция", icon: Store, count: "210" },
  { id: "newbuilding", label: "Новостройки", icon: Landmark, count: "85 ЖК" },
];

const QuickTypeSelection = () => {
  const [searchParams] = useSearchParams();
  const activeType = searchParams.get("type");

  return (
    <section className="py-6 md:py-8 bg-background">
      <div className="container mx-auto px-4">
1        <div className="flex flex-wrap justify-center gap-4 sm:gap-2">
          {propertyTypes.map((type) => {
            const isActive = activeType === type.id;
            const IconComponent = type.icon;
            
            return (
              <Link
                key={type.id}
                to={`/catalog?type=${type.id}`}
                aria-label={`Перейти к категории ${type.label}`}
                className={`
                  group w-[160px] h-[160px] sm:w-[140px] sm:h-[140px] 
                  bg-white/50 border border-blue-200 rounded-2xl 
                  flex flex-col justify-center items-center gap-1 
                  shadow-sm hover:shadow-md hover:bg-white/80 
                  transition-all duration-200 ease-in-out 
                  cursor-pointer hover:scale-[1.03] active:scale-[0.98]
                  focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary focus-visible:outline-offset-2
                  ${isActive 
                    ? "bg-blue-50 border-blue-400 shadow-md" 
                    : ""
                  }
                `}
              >
                <IconComponent 
                  className={`
                    text-blue-400 group-hover:text-blue-500 
                    transition duration-200 w-8 h-8
                    ${isActive ? "text-blue-600" : ""}
                  `} 
                />
                <p className={`
                  text-sm sm:text-xs font-medium text-gray-700 
                  group-hover:text-blue-600 transition-colors duration-200
                  ${isActive ? "text-blue-600" : ""}
                `}>
                  {type.label}
                </p>
                <p className="text-xs text-muted-foreground">
                  {type.count}
                </p>
              </Link>
            );
          })}
        </div>
      </div>
    </section>
  );
};

export default QuickTypeSelection;

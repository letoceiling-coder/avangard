import { createContext, useContext, useState, useEffect, ReactNode } from "react";

export interface CompareProperty {
  id: string;
  title: string;
  price: number;
  image: string;
  area: number;
  rooms: number;
  floor: number;
  address: string;
  type: string;
  year?: number;
  pricePerMeter?: number;
}

interface ComparisonContextType {
  compareItems: CompareProperty[];
  addToCompare: (property: CompareProperty) => boolean;
  removeFromCompare: (id: string) => void;
  isInCompare: (id: string) => boolean;
  clearCompare: () => void;
  canAddMore: boolean;
}

const ComparisonContext = createContext<ComparisonContextType | undefined>(undefined);

const MAX_COMPARE_ITEMS = 3;

export const ComparisonProvider = ({ children }: { children: ReactNode }) => {
  const [compareItems, setCompareItems] = useState<CompareProperty[]>(() => {
    const saved = localStorage.getItem("compareItems");
    return saved ? JSON.parse(saved) : [];
  });

  useEffect(() => {
    localStorage.setItem("compareItems", JSON.stringify(compareItems));
  }, [compareItems]);

  const addToCompare = (property: CompareProperty): boolean => {
    if (compareItems.length >= MAX_COMPARE_ITEMS) {
      return false;
    }
    if (compareItems.find((item) => item.id === property.id)) {
      return false;
    }
    setCompareItems((prev) => [...prev, property]);
    return true;
  };

  const removeFromCompare = (id: string) => {
    setCompareItems((prev) => prev.filter((item) => item.id !== id));
  };

  const isInCompare = (id: string): boolean => {
    return compareItems.some((item) => item.id === id);
  };

  const clearCompare = () => {
    setCompareItems([]);
  };

  const canAddMore = compareItems.length < MAX_COMPARE_ITEMS;

  return (
    <ComparisonContext.Provider
      value={{
        compareItems,
        addToCompare,
        removeFromCompare,
        isInCompare,
        clearCompare,
        canAddMore,
      }}
    >
      {children}
    </ComparisonContext.Provider>
  );
};

export const useComparison = () => {
  const context = useContext(ComparisonContext);
  if (!context) {
    throw new Error("useComparison must be used within a ComparisonProvider");
  }
  return context;
};

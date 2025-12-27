import { createContext, useContext, useState, ReactNode } from "react";

type Region = "belgorod" | "kursk" | "voronezh";

interface RegionContextType {
  selectedRegion: Region;
  setSelectedRegion: (region: Region) => void;
  regionName: string;
}

const regionNames: Record<Region, string> = {
  belgorod: "Белгороде",
  kursk: "Курске",
  voronezh: "Воронеже",
};

const RegionContext = createContext<RegionContextType | undefined>(undefined);

export const RegionProvider = ({ children }: { children: ReactNode }) => {
  const [selectedRegion, setSelectedRegion] = useState<Region>("belgorod");

  return (
    <RegionContext.Provider
      value={{
        selectedRegion,
        setSelectedRegion,
        regionName: regionNames[selectedRegion],
      }}
    >
      {children}
    </RegionContext.Provider>
  );
};

export const useRegion = () => {
  const context = useContext(RegionContext);
  if (!context) {
    throw new Error("useRegion must be used within a RegionProvider");
  }
  return context;
};

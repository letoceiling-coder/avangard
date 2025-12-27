export type PropertyType = "Новостройка" | "Вторичка" | "Коммерческая";

export interface Property {
  id: string;
  title: string;
  price: number;
  image: string;
  area: number;
  rooms: number;
  floor: number;
  address: string;
  type: PropertyType;
  complex?: string;
  developer?: string;
  status?: "available" | "booked" | "sold";
}

export interface ResidentialComplex {
  id: string;
  name: string;
  address: string;
  image: string;
  propertiesCount: number;
  priceFrom: number;
  developer: string;
}

export interface Developer {
  id: string;
  name: string;
  logo?: string;
  description?: string;
  complexesCount?: number;
}


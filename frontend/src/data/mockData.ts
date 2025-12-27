import type { Property, ResidentialComplex } from "@/types";

export const mockProperties: Property[] = [
  {
    id: "1",
    title: "3-комнатная квартира в ЖК «Белый город»",
    price: 6500000,
    image: "https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?w=800&h=600&fit=crop",
    area: 85,
    rooms: 3,
    floor: 12,
    address: "ул. Победы, 89",
    type: "Новостройка",
  },
  {
    id: "2",
    title: "2-комнатная квартира с видом на парк",
    price: 4800000,
    image: "https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=800&h=600&fit=crop",
    area: 62,
    rooms: 2,
    floor: 8,
    address: "пр. Славы, 45",
    type: "Вторичка",
  },
  {
    id: "3",
    title: "Пентхаус в элитном ЖК «Империал»",
    price: 15000000,
    image: "https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=800&h=600&fit=crop",
    area: 145,
    rooms: 4,
    floor: 25,
    address: "ул. Щорса, 2",
    type: "Новостройка",
  },
  {
    id: "4",
    title: "Студия в новом ЖК",
    price: 2800000,
    image: "https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=800&h=600&fit=crop",
    area: 28,
    rooms: 1,
    floor: 5,
    address: "ул. Белгородская, 12",
    type: "Новостройка",
  },
];

export const popularComplexes: ResidentialComplex[] = [
  {
    id: "1",
    name: "ЖК «Белый город»",
    address: "ул. Победы, 89",
    image: "https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?w=800&h=600&fit=crop",
    propertiesCount: 124,
    priceFrom: 3200000,
    developer: "СтройИнвест",
  },
  {
    id: "2",
    name: "ЖК «Империал»",
    address: "ул. Щорса, 2",
    image: "https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=800&h=600&fit=crop",
    propertiesCount: 89,
    priceFrom: 4500000,
    developer: "ЭлитСтрой",
  },
  {
    id: "3",
    name: "ЖК «Парковый»",
    address: "пр. Славы, 45",
    image: "https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=800&h=600&fit=crop",
    propertiesCount: 156,
    priceFrom: 2800000,
    developer: "КомфортСтрой",
  },
];


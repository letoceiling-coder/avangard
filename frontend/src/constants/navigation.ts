export interface NavLink {
  path: string;
  label: string;
}

export const NAV_LINKS: NavLink[] = [
  { path: "/catalog", label: "Каталог" },
  { path: "/residential-complex", label: "ЖК" },
  { path: "/developers", label: "Застройщики" },
  { path: "/about", label: "О компании" },
];

export interface FooterLink {
  to: string;
  label: string;
}

export const FOOTER_CATALOG_LINKS: FooterLink[] = [
  { to: "/catalog", label: "Все объекты" },
  { to: "/residential-complex", label: "Жилые комплексы" },
  { to: "/developers", label: "Застройщики" },
  { to: "/catalog?type=newbuilding", label: "Новостройки" },
  { to: "/catalog?type=secondary", label: "Вторичное жильё" },
];

export const FOOTER_DOCUMENT_LINKS: FooterLink[] = [
  { to: "/privacy", label: "Политика конфиденциальности" },
  { to: "/terms", label: "Пользовательское соглашение" },
  { to: "/consent", label: "Согласие на обработку ПД" },
  { to: "/about", label: "О компании" },
  { to: "/contacts", label: "Контакты" },
];

export const FOOTER_BOTTOM_LINKS: FooterLink[] = [
  { to: "/privacy", label: "Политика конфиденциальности" },
  { to: "/terms", label: "Условия использования" },
  { to: "/sitemap", label: "Карта сайта" },
];


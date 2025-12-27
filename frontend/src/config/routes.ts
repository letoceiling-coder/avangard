export const ROUTES = {
  HOME: "/",
  CATALOG: "/catalog",
  PROPERTY_DETAIL: "/property/:id",
  FAVORITES: "/favorites",
  CONTACTS: "/contacts",
  ABOUT: "/about",
  DEVELOPERS: "/developers",
  RESIDENTIAL_COMPLEX: "/residential-complex",
  COMPLEX_DETAIL: "/complex/:id",
  COMPARE: "/compare",
  AUTH: "/auth",
  PROFILE: "/profile",
  REGION_SELECT: "/region-select",
  PRIVACY: "/privacy",
  TERMS: "/terms",
  CONSENT: "/consent",
  SITEMAP: "/sitemap",
  NOT_FOUND: "*",
} as const;

export const getPropertyDetailRoute = (id: string) => `/property/${id}`;
export const getComplexDetailRoute = (id: string) => `/complex/${id}`;

